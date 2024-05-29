<?php

namespace Modules\Integrations\Http\Controllers;

use App\Machinery;
use App\Machines\FreeDay;
use App\User;
use Arhitector\Yandex\Disk;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Transformers\Vehicle;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\OrderComponentReportTimestamp;
use Modules\Orders\Entities\OrderMedia;
use Modules\Orders\Transformers\CustomerOrder;
use Modules\Profiles\Entities\UserNotification;
use Modules\RestApi\Transformers\VehicleSearch;

class TelegramBotController extends Controller
{

    function getDriver(Request $request)
    {
        return CompanyWorker::query()->whereHas('contacts', function ($q) use
        (
            $request
        ) {
            $q->whereHas('phones', function ($q) use
            (
                $request
            ) {
                $q->wherePhone($request->input('phone'));
            });
        })->firstOrFail();
    }

    function getDriverOrder(Request $request)
    {
        $driver =
            CompanyWorker::query()->whereHas('contacts', function ($q) use
            (
                $request
            ) {
                $q->whereHas('phones', function ($q) use
                (
                    $request
                ) {
                    $q->wherePhone($request->input('phone'));
                });
            })->firstOrFail();

        /** @var OrderComponent $component */
        $component =
            OrderComponent::query()->whereHas('driver', function (Builder $q) use
            (
                $driver
            ) {
                $q->where('company_workers.id', $driver->id);
            })->forPeriod(now()->startOfDay(), now()->endOfDay())->first();

        if (!$component) {
            return ['name' => $driver->name,];
        }
        /** @var OrderComponentReportTimestamp $timeStamp */
        $timeStamp = $component->reportsTimestamps()->whereDate('date', now()->format('Y-m-d'))->first();
        if (!$timeStamp) {
            return ['name' => $driver->name,];
        }

        $idleKey = "{$driver->id}_driver_start_idle";
        if (!$timeStamp->time_from) {
            $actions['start_work'] = trans('tg_bot.start_work');
        } else {
            if (!Cache::has($idleKey)) {
                $actions = [
                    'idle' => trans('tg_bot.idle')
                ];
            } else {
                $actions = [
                    'end_idle' => trans('tg_bot.stop_idle')
                ];
            }

        }


        if (!$timeStamp->time_to && $timeStamp->time_from && !Cache::has($idleKey)) {
            $actions['end_work'] = trans('tg_bot.stop_work');
        }

        if ($timeStamp->time_to) {
            $actions = [];
        }

        return response()->json($component
            ? [
                'id'        => $component->order->id,
                'name'      => $driver->name,
                'address'   => $component->order->address,
                'customer'  => $component->order->customer,
                'time_from' => $timeStamp->time_from,
                'time_to'   => $timeStamp->time_to,
                'machinery' => $component->worker->name,
                'actions'   => $actions
            ]
            : ['name' => $driver->name]);
    }

    function uploadMedia(
        Request $request,
        $driver)
    {
        $request->validate([
            'file' => 'required|url'
        ]);
        $driver = CompanyWorker::query()->findOrFail($driver);

        /** @var OrderComponent $component */
        $component =
            OrderComponent::query()->whereHas('driver', function (Builder $q) use
            (
                $driver
            ) {
                $q->where('company_workers.id', $driver->id);
            })->forPeriod(now()->startOfDay(), now()->endOfDay())->first();
        $file = file_get_contents($request->input('file'));
        $dir = "companies/{$driver->company_branch->company_id}/branch-{$driver->company_branch->id}/driver_uploads/";
        $ext = getFileExtensionFromString($request->input('file'));
        $fileName = $dir . uniqid() . "_photo.{$ext}";

        Storage::disk()->put($fileName, $file);
        $media = new OrderMedia([
            'url'  => Storage::disk()->url($fileName),
            'name' => 'Фото водителя',
        ]);
        $media->initiator()->associate($driver);

        $component->media()->save($media);
    }

    function uploadOrderMedia(
        Request $request,
        $id)
    {
        $request->validate([
            'file'  => 'required|url',
            'phone' => 'required'
        ]);
        $user = User::query()->where('phone', trimPhone($request->input('phone')))->firstOrFail();

        /** @var Order $order */
        $order =
            Order::query()->whereHas('company_branch', function ($q) use
            (
                $user
            ) {
                $q->userHasAccess($user->id);
            })->findOrFail($id);

        $file = file_get_contents($request->input('file'));
        $dir = "orders/{$order->id}/";
        $ext = getFileExtensionFromString($request->input('file'));
        $fileName = $dir . uniqid() . "_photo.{$ext}";

        $settings = $order->company_branch->getSettings();

        config()->set('filesystems.disks.yandex-disk.token', $settings->ya_disk_oauth);

       Storage::disk('yandex-disk')->put($fileName, $file);

        $disk = new Disk($settings->ya_disk_oauth);

        $resource = $disk->getResource("/transbaza/$fileName");

        if (!$resource->isPublish()) {
            $resource->setPublish(true);
        }
        $media = new OrderMedia([
            'url'           => stripParamFromUrl($resource->getLink(), 'etag'),
            'name'          => 'Фото водителя',
            'original_path' => $fileName,
        ]);
        $media->initiator()->associate($user);

        $order->media()->save($media);
    }

    function findOrder(
        Request $request,
        $orderNumber)
    {
        $request->validate([
            'phone' => 'required'
        ]);

        $user = User::query()->where('phone', trimPhone($request->input('phone')))->firstOrFail();

        $order =
            Order::query()->where('internal_number', $orderNumber)->whereHas('company_branch', function ($q) use
            (
                $user
            ) {
                $q->userHasAccess($user->id);
            })->firstOrFail();

        return CustomerOrder::make($order);
    }

    function driverAction(
        Request $request,
        $action)
    {
        $driver =
            CompanyWorker::query()->whereHas('contacts', function ($q) use
            (
                $request
            ) {
                $q->whereHas('phones', function ($q) use
                (
                    $request
                ) {
                    $q->wherePhone($request->input('phone'));
                });
            })->firstOrFail();

        \Log::info($action);
        /** @var OrderComponent $component */
        $component =
            OrderComponent::query()->whereHas('driver', function (Builder $q) use
            (
                $driver
            ) {
                $q->where('company_workers.id', $driver->id);
            })->forPeriod(now()->startOfDay(), now()->endOfDay())->first();

        /** @var OrderComponentReportTimestamp $timeStamp */
        $timeStamp = $component->reportsTimestamps()->whereDate('date', now()->format('Y-m-d'))->first();

        $idleKey = "{$driver->id}_driver_start_idle";
        $companyService = new CompaniesService($component->order->company_branch->company);
        $text = null;
        if ($timeStamp->time_to) {
            return;
        }
        switch ($action) {
            case 'start_work':
                if (!$timeStamp->time_from) {
                    $timeStamp->update([
                        'time_from' => now()->format('H:i')
                    ]);
                    $text =
                        trans('tg_bot.driver_start_work', ['id' => $driver->id, 'order_id' => $component->order->internal_number]);
                }
                break;
            case 'idle':
                if (Cache::has($idleKey)) {
                    return;
                }
                \Cache::put("{$driver->id}_driver_start_idle", (string)now(), now()->endOfDay()->diffInSeconds(now()));
                $text =
                    trans('tg_bot.driver_start_idle', ['id' => $driver->id, 'order_id' => $component->order->internal_number]);

                break;
            case 'end_idle':
                $startIdle = \Cache::get($idleKey);
                if (!$startIdle) {
                    return;
                }
                $startIdle = Carbon::parse($startIdle);
                $timeStamp->increment('idle_duration', now()->diffInMinutes($startIdle) / 60);
                Cache::forget($idleKey);
                $text =
                    trans('tg_bot.driver_end_idle', ['id' => $driver->id, 'order_id' => $component->order->internal_number]);
                break;
            case 'end_work':
                if ($timeStamp->time_to) {
                    return;
                }
                $timeStamp->update([
                    'time_to'  => now()->format('H:i'),
                    'duration' => now()->diffInMinutes(Carbon::parse("{$timeStamp->date->format('Y-m-d')} {$timeStamp->time_from}")) / 60
                ]);
                $text =
                    trans('tg_bot.driver_end_work', ['id' => $driver->id, 'order_id' => $component->order->internal_number]);
                break;
        }

        if ($text) {
            $companyService->addUsersNotification(
                $text,
                null,
                UserNotification::TYPE_INFO,
                $component->order->generateCompanyLink(),
                $component->order->company_branch);
        }

    }

    function mechanicAuth(Request $request)
    {
        $phone = trimPhone($request->phone);

        /** @var User $manager */
        $manager = User::query()->where('phone', $phone)->first();

        if ($manager) {
            $manager = [
                'id'    => $manager->id,
                'name'  => $manager->name,
                'email' => $manager->email,
                'company_branch_id' => $manager->branches()->first()->id,
                'type'  => 'manager'
            ];
        }
        if (!$manager) {
            $worker = CompanyWorker::query()->whereHas('contacts', function (Builder $q) use ($phone) {
                $q->whereHas('phones', function (Builder $q) use ($phone) {
                    $q->where('phone', $phone);
                });
            })->firstOrFail();

            $worker->type = 'mechanic';
        }


        return $worker ?? $manager;

    }

    function searchMachinery(Request $request)
    {
        $request->validate([
            'search'            => 'required|string|max:255',
            'phone' => 'required'
        ]);

        $phone = trimPhone($request->input('phone'));

        /** @var CompanyWorker $worker */
        $worker = CompanyWorker::query()->whereHas('contacts', function (Builder $q) use ($phone) {
            $q->whereHas('phones', function (Builder $q) use ($phone) {
                $q->where('phone', $phone);
            });
        })->firstOrFail();

        $machinery = $worker->company_branch->machines()
            ->whereReadOnly(false)
            /*->where(function ($q) use ($request) {
            $q->where('board_number', 'like', "%{$request->input('search')}%")
                ->orWhere('name', 'like', "%{$request->input('search')}%");
        })*/
            ->where('board_number', '=', $request->input('search'))
            ->firstOrFail();

        return Vehicle::make($machinery);
    }

    function machineryEvent(Request $request)
    {
        $request->validate([
            'machinery_id'      => 'required|numeric',
            'phone' => 'required',
            'date_from'         => 'required|date',
            'date_to'           => 'required|date',
        ]);
        $phone = trimPhone($request->input('phone'));
        $worker = CompanyWorker::query()->whereHas('contacts', function (Builder $q) use ($phone) {
            $q->whereHas('phones', function (Builder $q) use ($phone) {
                $q->where('phone', $phone);
            });
        })->firstOrFail();
        //logger(Carbon::parse($request->input('date_from'))->setTimezone(config('app.timezone')) . ' ' . Carbon::parse($request->input('date_to'))->setTimezone(config('app.timezone')));
        $machinery = $worker->company_branch->machines()
            ->whereReadOnly(false)
            // ->checkAvailable(Carbon::parse($request->input('date_from'))->setTimezone(config('app.timezone')), Carbon::parse($request->input('date_to'))->setTimezone(config('app.timezone')))
            ->findOrFail($request->input('machinery_id'));
        $request->merge([
            'mechanics'        => [
                ['id' => $worker->id]
            ],
            'event-start-date' => $request->input('date_from'),
            'event-end-date'   => $request->input('date_to'),
        ]);
        $event = FreeDay::pushEvent($machinery, $request);

        return response()->json($event, 200);
    }

    function addReportToEvent(Request $request)
    {
        $request->validate([
            'machinery_id'      => 'required|numeric',
            'event_id'          => 'required|numeric',
            'company_worker_id' => 'required|numeric',
            'report_data'       => 'required',
        ]);

        $worker = CompanyWorker::query()->findOrFail($request->input('company_worker_id'));
        $machinery = $worker->company_branch->machines()
            ->whereReadOnly(false)
            ->findOrFail($request->input('machinery_id'));

        /** @var FreeDay $event */
        $event = $machinery->freeDays()->findOrFail($request->input('event_id'));

        if ($event->technicalWork) {
            $report = $request->input('report_data');
            $event->technicalWork->update([
                'report_data' => [
                    'id'   => $report['id'],
                    'name' => $report['name'],
                ]
            ]);
        }

        return response('OK');
    }


    function eventValidation(Request $request)
    {
        $request->validate([
            'type'  => 'required|string',
            'value' => 'required'
        ]);

        $phone = trimPhone($request->input('phone'));
        $worker = CompanyWorker::query()->whereHas('contacts', function (Builder $q) use ($phone) {
            $q->whereHas('phones', function (Builder $q) use ($phone) {
                $q->where('phone', $phone);
            });
        })->firstOrFail();


        switch ($request->input('type')) {

            case "engine_hours":


                /** @var Machinery $machinery */
                $machinery = $worker->company_branch->machines()
                    ->whereReadOnly(false)
                    ->findOrFail($request->input('machinery_id'));

                $max = $machinery->technicalWorks()->selectRaw('*, CAST(engine_hours as DECIMAL)')->max('engine_hours');

                if ($max > (float)$request->input('value')) {
                    return response()->json([], 400);
                }
                break;
        }

        return response('OK');
    }


    function getMedia(
        Request $request,
        $id)
    {
        $media = OrderMedia::query()->findOrFail($id);

        $name = explode('/', $media->original_path);

        $settings = $media->owner->company_branch->getSettings();

        config()->set('filesystems.disks.yandex-disk.token', $settings->ya_disk_oauth);

        return $request->filled('download')
            ?
            response()->streamDownload(function () use
            (
                $media
            ) {
                echo Storage::disk('yandex-disk')->get($media->original_path);
            }, array_pop($name), [
                'Content-type' => Storage::disk('yandex-disk')->getMimetype($media->original_path)
            ])
            : response()->stream(function () use
            (
                $media
            ) {
                echo Storage::disk('yandex-disk')->get($media->original_path);
            }, 200, [
                'Content-type' => Storage::disk('yandex-disk')->getMimetype($media->original_path)
            ]);
    }
}
