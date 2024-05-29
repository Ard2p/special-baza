<?php

namespace Modules\Profiles\Http\Controllers;

use App\Service\RequestBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Profiles\Transformers\NotificationCollection;

class NotificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return array
     */
    public function index(Request $request)
    {
        $notifications = Auth::user()
            ->notifications()
            ->orderBy('is_read')
            ->orderBy('created_at', 'desc');

        if(app(RequestBranch::class)->companyBranch) {
            $notifications->forBranch();
        }
        $notifications = $notifications->paginate(5);

        $unread = Auth::user()
            ->notifications()
            ->where('is_read', false);

        if(app(RequestBranch::class)->companyBranch) {
            $unread->forBranch();
        }

        $unread = $unread->count();
        return response()->json([
            'unread' => $unread,
            'data' => NotificationCollection::collection($notifications),
            'meta' => [
                'total' => $notifications->total(),
                'count' => $notifications->count(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'total_pages' => $notifications->lastPage(),
                'last_page' => $notifications->lastPage(),
                'next_page_url' => $notifications->nextPageUrl(),
            ],

        ]);;
    }

    function destroy($id)
    {
        Auth::user()
            ->notifications()->forBranch()->where('user_notifications.id', $id)->delete();

        return response()->json();
    }

    function settings(Request $request)
    {
       switch ($request->input('type')) {
           case 'mark-read':
               Auth::user()
                   ->notifications()->update(['is_read' => true]);
               break;
           case 'clear-all':
               Auth::user()
                   ->notifications()->delete();
               break;
       }

       return response()->json();
    }


}
