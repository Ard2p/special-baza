<?php

namespace Modules\AdminOffice\Http\Controllers\Support;

use App\Directories\Unit;
use App\Option;
use App\Support\AttributesLocales\TypeLocale;
use App\Support\AttributesLocales\UnitLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\AdminOffice\Entities\Filter;

class UnitsController extends Controller
{
    function getUnits($id = null)
    {
        $units = Unit::query()->with('locale');

        return $units->get();
    }

    function getAll(Request $request, $id = null)
    {
        $units = Unit::query()->with('locale');

        if ($id) {
            return $units->findOrFail($id);
        }

        $filter = new Filter($units);
        $filter->getLike([
            'name' => 'name'
        ]);

        return $units->paginate($request->input('per_page', 10));
    }

    function addUnit(Request $request)
    {
        $errors = Validator::make($request->all(), [
            'name' => 'required|unique:units,name',
            'locales' => 'nullable|array'
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 400);
        }

        DB::beginTransaction();
        $unit = Unit::create($request->only(
            [
                'name',
            ]));
        foreach ($request->input('locales') as $loc => $locale) {
            UnitLocale::create([
                'name' => $locale,
                'locale' => $loc,
                'unit_id' => $unit->id,
            ]);
        }

        DB::commit();

        $unit->load('locale');
        return response()->json($unit);
    }

    function updateUnit(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);

        $errors = Validator::make($request->all(), [
            'name' => 'required|unique:units,name,' . $id,
            'locales' => 'nullable|array'
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 400);
        }

        DB::beginTransaction();
        $unit->update($request->only(
            [
                'name',
            ]));
        $array = $request->all();
        foreach (Option::$systemLocales as $locale) {
            if (!isset($array['locales'][$locale])) {
                continue;
            }
            $loc = $unit->locale()->whereLocale($locale)->first();
            if ($loc) {
                $loc->update([
                    'name' => $array['locales'][$locale]
                ]);
            } else {
                UnitLocale::create([
                    'unit_id' => $unit->id,
                    'name' => $array['locales'][$locale],
                    'locale' => $locale
                ]);
            }
        }

        DB::commit();

      return response()->json($unit);
    }

    function deleteUnit()
    {

    }
}
