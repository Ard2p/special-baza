<?php

namespace Modules\AdminOffice\Http\Controllers\Marketing;

use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\AdminOffice\Entities\DownloadLink;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Entities\Subscribe\Subscriber;
use Rap2hpoutre\FastExcel\FastExcel;

class SubscribersController extends Controller
{


    public function __construct(Request $request)
    {
        if ($request->filled('phone')) {
            $request->merge([
                'phone' => trimPhone($request->input('phone'))
            ]);
        }
    }

    function get(Request $request)
    {
        $subscribers = Subscriber::with('user');

        return $subscribers->orderBy('created_at', 'DESC')->paginate($request->per_page ?: 10);
    }
}
