<?php

namespace Modules\ContractorOffice\Http\Controllers\Scoring;

use App\Http\Controllers\Controller;
use App\Service\Scoring\Models\Scoring;
use App\Service\Scoring\ScoringService;
use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScoringController extends Controller
{
    private const SCORING_TTL = 864000;

    public $scoringService;

    public function __construct()
    {

        $this->scoringService = new ScoringService();
    }

    function index(Request $request)
    {
        $scorings = Scoring::query()->with([
            'company_branch',
            'creator'
        ]);
        if ($request->filled('type')) {
            $scorings = $scorings->whereIn('type', $request->input('type'));
        }
        if ($request->filled('company_branch_id')) {
            $scorings = $scorings->where('company_branch_id', $request->input('company_branch_id'));
        }
        if ($request->filled('date_from')) {
            $scorings = $scorings->where('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $scorings = $scorings->where('created_at', '<=', $request->input('date_to'));
        }
        return response()->json($scorings->paginate());
    }

    public function show(Request $request)
    {
        if ($request->type == Scoring::PHYSICAL) {
            return response()->json($this->scoringService->checkPhisycal(
                $request->input('firstname'),
                $request->input('lastname'),
                $request->input('midname'),
                Carbon::parse($request->input('birthdate'))->format('d.m.Y'),
                $request->input('passport_number'),
                Carbon::parse($request->input('issue_date'))->format('d.m.Y')
            ));
        } elseif ($request->type == Scoring::LEGAL) {
            return response()->json($this->scoringService->checkLegal($request->get('inn')));
        }
        return response()->json([]);

    }
}
