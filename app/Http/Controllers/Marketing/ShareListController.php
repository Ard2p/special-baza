<?php

namespace App\Http\Controllers\Marketing;

use App\Helpers\RequestHelper;
use App\Jobs\SmsShareNotification;
use App\Mail\Share;
use App\Marketing\EmailLink;
use App\Marketing\SendingSms;
use App\Marketing\ShareList;
use App\Option;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ShareListController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->filled('phone')) {
            $request->merge(
                ['phone' => (int)str_replace(
                    [')', '(', ' ', '+', '-'],
                    '',
                    $request->input('phone'))
                ]);
        }
        $data = $request->all();
        $rules = [];
        if ($request->filled('type') && $request->input('type') === 'email') {
            $rules = [
                'email' => 'required|string|email|max:255',
            ];

        } elseif ($request->filled('type') && $request->input('type') === 'phone') {
            $rules = [
                'phone' => 'required|numeric|digits:' .  RequestHelper::requestDomain()->options['phone_digits'],
            ];

        }
        $rules = array_merge($rules, [
            'g-recaptcha-response' => 'required|captcha',
            'type' => 'required|in:email,phone',
            'url' => 'required|string|max:255',
        ]);
        $validator = Validator::make($data, $rules, [
            'email.email' => 'Некорректный email.',
            'email.required' => 'Некорректный email.',
            'phone.required' => 'Некорректный телефон.',
            'phone.digits' => 'Некорректный телефон.',
            'phone.int' => 'Некорректный телефон.',
        ]);

        $errors = $validator
            ->errors()
            ->all();


        if ($errors) return response()->json(['error' => implode(' ', $errors)], 419);

        $share = ShareList::create([
            'email' => $request->email,
            'phone' => $request->phone,
            'url' => $request->input('url'),
            'type' => $request->input('type'),
        ]);
        if ($share->type === 'email' && Option::get('send_share_by_email')) {

            Mail::to($share->email)->queue(new Share('TRANS-BAZA.RU - ссылка от друга', $share));
        }
        if ($share->type === 'phone' && Option::get('send_share_by_phone')) {
            dispatch(new SmsShareNotification($share));
        }
        $share->refresh();
        return response()->json(['message' => 'Успешно отправлено на ' .
            ($share->type === 'phone'
                ? $share->phone_format
                : $share->email)
        ]);
    }

    function sorryPage(Request $request)
    {

        if ($request->filled('dis_id')) {
            $share = ShareList::whereConfirmStatus(0)->find($request->dis_id);
            if ($share) {
                $share->update([
                    'confirm_status' => 2,
                    'confirm_at' => Carbon::now()
                ]);
            }

        }
    }

    private function pixel()
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
    }


    function getPixel($id)
    {
        $share = ShareList::findOrFail($id);

        if (!$share->is_watch && $share->type === 'email') {
            $share->update([
                'is_watch' => 1,
                'watch_at' => Carbon::now(),
            ]);
        }
        $headers = [
            'Content-Type' => 'image/png'
        ];

        return response()->stream(function () {
            echo $this->pixel();
        }, 200, $headers);
    }


    function getLiveCounter()
    {
        $headers = [
            'Content-Type' => 'image/gif'
        ];
        $config_code_color = "FFAA00";
        $config_font = 1;
        $base = base64_decode('/9j/4AAQSkZJRgABAgEASABIAAD/4QULRXhpZgAATU0AKgAAAAgABwESAAMAAAABAAEAAAEaAAUAAAABAAAAYgEbAAUAAAABAAAAagEoAAMAAAABAAMAAAExAAIAAAAUAAAAcgEyAAIAAAAUAAAAhodpAAQAAAABAAAAnAAAAMgAAAAcAAAAAQAAABwAAAABQWRvYmUgUGhvdG9zaG9wIDcuMAAyMDA3OjA5OjI4IDAwOjM2OjQxAAAAAAOgAQADAAAAAf//AACgAgAEAAAAAQAAAFigAwAEAAAAAQAAAA8AAAAAAAAABgEDAAMAAAABAAYAAAEaAAUAAAABAAABFgEbAAUAAAABAAABHgEoAAMAAAABAAIAAAIBAAQAAAABAAABJgICAAQAAAABAAAD3QAAAAAAAABIAAAAAQAAAEgAAAAB/9j/4AAQSkZJRgABAgEASABIAAD/7QAMQWRvYmVfQ00AAv/uAA5BZG9iZQBkgAAAAAH/2wCEAAwICAgJCAwJCQwRCwoLERUPDAwPFRgTExUTExgRDAwMDAwMEQwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwBDQsLDQ4NEA4OEBQODg4UFA4ODg4UEQwMDAwMEREMDAwMDAwRDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDP/AABEIAA8AWAMBIgACEQEDEQH/3QAEAAb/xAE/AAABBQEBAQEBAQAAAAAAAAADAAECBAUGBwgJCgsBAAEFAQEBAQEBAAAAAAAAAAEAAgMEBQYHCAkKCxAAAQQBAwIEAgUHBggFAwwzAQACEQMEIRIxBUFRYRMicYEyBhSRobFCIyQVUsFiMzRygtFDByWSU/Dh8WNzNRaisoMmRJNUZEXCo3Q2F9JV4mXys4TD03Xj80YnlKSFtJXE1OT0pbXF1eX1VmZ2hpamtsbW5vY3R1dnd4eXp7fH1+f3EQACAgECBAQDBAUGBwcGBTUBAAIRAyExEgRBUWFxIhMFMoGRFKGxQiPBUtHwMyRi4XKCkkNTFWNzNPElBhaisoMHJjXC0kSTVKMXZEVVNnRl4vKzhMPTdePzRpSkhbSVxNTk9KW1xdXl9VZmdoaWprbG1ub2JzdHV2d3h5ent8f/2gAMAwEAAhEDEQA/APSMPDxHYlDnUVlxrYSSxpJJaPJG+w4X/cer/Mb/AHIOHlVNxKGltkitgMVWEfRHcMRvtlX7tv8A21Z/6TVDFm5H24XPBfDG7lj7NicOY4pVHJuek1fYcL/uPV/mN/uS+w4X/cer/Mb/AHJfbKv3bf8Atqz/ANJpfbKv3bf+2rP/AEmpPe5D/OYP8bEt9vmP3cn2TV9hwv8AuPV/mN/uS+w4X/cer/Mb/cl9sq/dt/7as/8ASaX2yr923/tqz/0ml73If5zB/jYle3zH7uT7Jq+w4X/cer/Mb/cl9hwv+49X+Y3+5L7ZV+7b/wBtWf8ApNL7ZV+7b/21Z/6TS97kP85g/wAbEr2+Y/dyfZNX2HC/7j1f5jf7kDLw2taHY9GKGgE2G5kAAeGwI/2yr923/tqz/wBJqtnWYt1bfXfbXQxwNjfTsa1w/Na93p/vqLPl5M45CE8PF+jw5MUP+f8A9JfjhnEhxRyV1uM5fg59NzchuSx+NQ0Nossrsrr2zt9gewvSVnJtxHOttqc6TiOrZUKngbeW2btv83+b+4ks6o3wfeMXF/nuLFw8PF/e/wCZ/ONrX5vbnX7lT3p//9n/7QnGUGhvdG9zaG9wIDMuMAA4QklNBCUAAAAAABAAAAAAAAAAAAAAAAAAAAAAOEJJTQPtAAAAAAAQAEgAAAACAAIASAAAAAIAAjhCSU0EJgAAAAAADgAAAAAAAAAAAAA/gAAAOEJJTQQNAAAAAAAEAAAAeDhCSU0EGQAAAAAABAAAAB44QklNA/MAAAAAAAkAAAAAAAAAAAEAOEJJTQQKAAAAAAABAAA4QklNJxAAAAAAAAoAAQAAAAAAAAACOEJJTQP1AAAAAABIAC9mZgABAGxmZgAGAAAAAAABAC9mZgABAKGZmgAGAAAAAAABADIAAAABAFoAAAAGAAAAAAABADUAAAABAC0AAAAGAAAAAAABOEJJTQP4AAAAAABwAAD/////////////////////////////A+gAAAAA/////////////////////////////wPoAAAAAP////////////////////////////8D6AAAAAD/////////////////////////////A+gAADhCSU0EAAAAAAAAAgABOEJJTQQCAAAAAAAEAAAAADhCSU0ECAAAAAAAEAAAAAEAAAJAAAACQAAAAAA4QklNBB4AAAAAAAQAAAAAOEJJTQQaAAAAAANJAAAA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ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAo8P3hwYWNrZXQgZW5kPSd3Jz8+/+4ADkFkb2JlAGRAAAAAAf/bAIQAAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQICAgICAgICAgICAwMDAwMDAwMDAwEBAQEBAQEBAQEBAgIBAgIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMD/8AAEQgADwBYAwERAAIRAQMRAf/dAAQAC//EAaIAAAAGAgMBAAAAAAAAAAAAAAcIBgUECQMKAgEACwEAAAYDAQEBAAAAAAAAAAAABgUEAwcCCAEJAAoLEAACAQMEAQMDAgMDAwIGCXUBAgMEEQUSBiEHEyIACDEUQTIjFQlRQhZhJDMXUnGBGGKRJUOhsfAmNHIKGcHRNSfhUzaC8ZKiRFRzRUY3R2MoVVZXGrLC0uLyZIN0k4Rlo7PD0+MpOGbzdSo5OkhJSlhZWmdoaWp2d3h5eoWGh4iJipSVlpeYmZqkpaanqKmqtLW2t7i5usTFxsfIycrU1dbX2Nna5OXm5+jp6vT19vf4+foRAAIBAwIEBAMFBAQEBgYFbQECAxEEIRIFMQYAIhNBUQcyYRRxCEKBI5EVUqFiFjMJsSTB0UNy8BfhgjQlklMYY0TxorImNRlUNkVkJwpzg5NGdMLS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');
        $img = imagecreatefromstring($base);
        $koliakincounter = Option::get('live_counter');
        $color = imagecolorallocate($img,
            hexdec(substr($config_code_color, 1, 2)),
            hexdec(substr($config_code_color, 3, 2)),
            hexdec(substr($config_code_color, 5, 2))
        );
        imagestring($img, $config_font, 5, 3, $koliakincounter, $color);
        return response()->stream(function () use ($img) {

            imagegif($img);
        }, 200, $headers);
    }

    function getFriendPixel($id)
    {
        $share = EmailLink::findOrFail($id);

        if (!$share->is_watch) {
            $share->update([
                'is_watch' => 1,
                'watch_at' => Carbon::now(),
            ]);
        }
        $headers = [
            'Content-Type' => 'image/png'
        ];

        return response()->stream(function () {
            echo $this->pixel();
        }, 200, $headers);
    }

    function getFsk(Request $request)
    {
        $sending = SendingSms::whereHash($request->hash)->findOrFail($request->sending_id);

        if (!$sending->watch_at) {
            $sending->update([
                'watch_at' => Carbon::now()
            ]);
        }
        $confirm_link = route('article_index', ['article' => 'thankyou', 'fsk_sending_id' => $sending->id, 'hash' => $sending->hash]);
        $cancel_link = route('article_index', ['article' => 'verysorry', 'dis_fsk_sending_id' => $sending->id, 'hash' => $sending->hash]);
        return view('marketing.fck', compact('sending', 'confirm_link', 'cancel_link'));
    }


}