<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Machinery;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Entities\SiteFeedback;

class FeedbackController extends Controller
{
    function getFeedbacks(Request $request, $id = null)
    {
        $feedback = SiteFeedback::query()->forDomain();

        $filter = new Filter($feedback);
        $filter->getLike([
            'name' => 'name',
            'content' => 'content',
        ]);


        return $feedback->ordered()->paginate($request->per_page ?: 10);
    }

    function createFeedBack(Request $request)
    {
        $errors = \Validator::make($request->all(), [
            'name' => 'required|string',
            'content' => 'required|string',
            'rate' => 'required|integer|min:1|max:5',
            'country_id' => 'required|exists:countries,id',
        ])->errors()->getMessages();
        if($errors){
            return response()->json($errors, 419);
        }

        SiteFeedback::create($request->only(['name', 'content', 'rate', 'country_id']));

        return response()->json();
    }

    function updateFeedBack(Request $request, $id)
    {
        $feedback = SiteFeedback::findOrFail($id);
        $errors = \Validator::make($request->all(), [
            'name' => 'required|string',
            'content' => 'required|string',
            'rate' => 'required|integer|min:1|max:5',
            'country_id' => 'required|exists:countries,id',
        ])->errors()->getMessages();
        if($errors){
            return response()->json($errors, 419);
        }

        $feedback->update($request->only(['name', 'content', 'rate', 'country_id']));

        return response()->json();
    }

    function sortIt(Request $request) {
        $errors = \Validator::make($request->all(), [
            'action' => 'required|in:up,down',
        ])->errors()->getMessages();
        if($errors){
            return response()->json($errors, 419);
        }

        $feedback = SiteFeedback::findOrFail($request->id);
        if($request->input('action') === 'up'){

            $feedback->moveOrderUp();
        }else {
            $feedback->moveOrderDown();
        }
    }

    function deleteFeedBack($id)
    {
        SiteFeedback::findOrFail($id)->delete();
    }
}
