<?php

namespace Modules\AdminOffice\Http\Controllers\Support;

use App\Article;
use App\Helpers\RequestHelper;
use App\Machines\OptionalAttribute;
use App\Machines\Type;
use App\Option;
use App\Support\ArticleLocale;
use App\Support\AttributesLocales\OptionalAttributeLocale;
use App\Support\AttributesLocales\TypeLocale;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\AdminOffice\Entities\Filter;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Orders\Entities\Order;
use Modules\RestApi\Entities\CategoryAvgMarketPrice;

class SupportDirectoriesController extends Controller
{

    public function __construct(Request $request)
    {
        $data = $request->all();
        $data = array_map(function ($val) {
            return $val === 'null' || $val === 'undefined' ? '' : $val;
        }, $data);
        $request->merge($data);
    }

    function getCategories(Request $request, $id = null)
    {
        $types = Type::with('locale', 'optional_attributes', 'tariffs');

        if ($id) {
            $type = $types->findOrFail($id);
            $type->setAppends(['locales']);
            return $type;
        }
        $filter = new Filter($types);
        $filter->getLike([
            'name' => 'name',
        ]);

        if ($request->filled('has_attributes')) {
            $has_attributes = filter_var($request->input('has_attributes'), FILTER_VALIDATE_BOOLEAN);
            $has_attributes ? $types->whereHas('optional_attributes') : $types->whereDoesntHave('optional_attributes');
        }
        if ($request->filled('no_locale')) {
            $noLocale = toBool($request->input('no_locale'));
            if($noLocale) {
                $types->whereDoesntHave('locale', function ($q) {
                   $q->where('locale', 'it');
                });
            }
        }
        return $types->paginate($request->per_page ?: 10);
    }

    function addCategory(Request $request)
    {
        $request->validate([
            'service_plan_type' => 'required|in:rent_days,motor_hours',
            'type' => 'required|in:machine,equipment',
            'name' => 'required|string|min:2|max:255',
            'name_style' => 'required|string|min:2|max:255',
            'eng_alias' => 'required|unique:types,eng_alias',
            'alias' => 'nullable|unique:types,alias',
            'licence_plate' => 'nullable',
            'vin' => 'nullable|boolean',
            'rent_with_driver' => 'nullable|boolean',
            'photo' => 'required|string',
            'tariffs' => 'nullable|array',
            'tariffs.*' => 'exists:category_tariffs,id',
            'plans' => 'nullable|array',
            'plans.*.type' => ['required', Rule::in([
                'engine_hours',
                'days',
                'hours',
            ])],
            'plans.*.category_id' => 'required',
            'plans.*.duration' => 'required',
            'plans.*.duration_between_works' => 'required',
            'plans.*.duration_plan' => 'required',
        ]);

        if (!$request->input('alias')) {
            $request->merge([
                'alias' => generateChpu($request->input('name'))
            ]);
        }
        DB::beginTransaction();

        $type = Type::create([
            'type' => $request->input('type'),
            'name' => $request->input('name'),
            'name_style' => $request->input('name_style'),
            'eng_alias' => $request->input('eng_alias'),
            'alias' => $request->input('alias'),
            'photo' => $request->input('photo'),
            'service_plan_type' => $request->input('service_plan_type'),
            'amount_between_services' => $request->input('amount_between_services'),
            'service_duration' => $request->input('service_duration'),
            'amount_days_between_plan_services' => $request->input('amount_days_between_plan_services'),
            'vin' => $request->input('vin') ?: 0,
            'rent_with_driver' => $request->input('rent_with_driver') ?: 0,
            'licence_plate' => filter_var($request->input('licence_plate'), FILTER_VALIDATE_BOOLEAN),
        ]);

        $type->tariffs()->sync($request->input('tariffs'));

        DB::commit();

        return response()->json($type);
    }

    function updateCategory(Request $request, $id)
    {
        $category = Type::with('locale')->findOrFail($id);
        $request->validate([
            'type' => 'required|in:machine,equipment',
            'name' => 'required|string|min:2|max:255',
            'name_style' => 'required|string|min:2|max:255',
            'eng_alias' => 'required|unique:types,eng_alias,' . $id,
            'alias' => 'required|unique:types,alias,' . $id,
            'photo' => 'required|string',
            'locale' => 'array',
            'tariffs' => 'nullable|array',
            'tariffs.*' => 'exists:category_tariffs,id',
            'service_plan_type' => 'required|in:rent_days,motor_hours',
        ]);

        DB::beginTransaction();

        $category->update([
            'type' => $request->input('type'),
            'name' => $request->input('name'),
            'name_style' => $request->input('name_style'),
            'eng_alias' => $request->input('eng_alias'),
            'alias' => $request->input('alias'),
            'photo' => $request->input('photo'),
            'vin' => $request->input('vin'),
            'rent_with_driver' => $request->input('rent_with_driver'),
            'licence_plate' => filter_var($request->input('licence_plate'), FILTER_VALIDATE_BOOLEAN),
            'service_plan_type' => $request->input('service_plan_type'),
            'amount_between_services' => $request->input('amount_between_services'),
            'service_duration' => $request->input('service_duration'),
            'amount_days_between_plan_services' => $request->input('amount_days_between_plan_services'),
        ]);
        foreach ($request->input('locales') as $locale => $value) {
            if(!$value) {
                continue;
            }
            $loc = $category->locale()->where('locale', $locale)->first();
            if ($loc) {
                $loc->update(['name' =>$value]);
            } else {
                TypeLocale::create([
                    'name' => $value,
                    'locale' => $locale,
                    'type_id' => $category->id,
                ]);
            }

        }

        $category->tariffs()->sync($request->input('tariffs'));

        DB::commit();

        return response()->json($category);
    }

    function getAttributes($id)
    {
        return OptionalAttribute::where('type_id', $id)->orderBy('priority')->get();
    }

    function setAttributes(Request $request, $id)
    {
        $request->validate([
            '*.name' => 'required|string',
            '*.unit_id' => 'nullable|exists:units,id',
            '*.min' => 'required|numeric',
            '*.max' => 'required|numeric',
            '*.interval' => 'required|numeric|min:0,1',
            '*.field' => 'required|in:' . trim(implode(',', OptionalAttribute::$types), ','),
            '*.priority' => 'integer|min:1|max:100',
        ]);

        $data = $request->all();
        $ids = OptionalAttribute::whereTypeId($id)->get()->pluck('id')->toArray();
        foreach ($data as $array) {
            if (!isset($array['id'])) {

                $option = OptionalAttribute::create([
                    'type_id' => $id,
                    'name' => $array['name'],
                    'unit_id' => $array['unit_id'] ?? null,
                    'priority' => $array['priority'],
                    'min' => $array['min'],
                    'max' => $array['max'],
                    'interval' => $array['interval'],
                    'require' => toBool($array['require'] ?? false),
                    'field_type' => OptionalAttribute::type($array['field'])
                ]);
            } else {

                $option = OptionalAttribute::findOrFail($array['id']);
                $key = array_search($array['id'], $ids);
                if ($key !== false) {
                    unset($ids[$key]);
                }
                $option->update([
                    'type_id' => $id,
                    'name' => $array['name'],
                    'unit_id' => $array['unit_id'] ?? 0,
                    'priority' => $array['priority'],
                    'min' => $array['min'],
                    'max' => $array['max'],
                    'interval' => $array['interval'],
                    'require' => toBool($array['require'] ?? false),
                    'field_type' => OptionalAttribute::type($array['field'])
                ]);
            }
            if (isset($array['locales'])) {
                $option->refresh();
                foreach (Option::$systemLocales as $locale) {
                    if (!isset($array['locales'][$locale])) {
                        continue;
                    }
                    $loc = $option->locale()->whereLocale($locale)->first();
                    if ($loc) {
                        $loc->update([
                            'name' => $array['locales'][$locale]
                        ]);
                    } else {
                        OptionalAttributeLocale::create([
                            'optional_attribute_id' => $option->id,
                            'name' => $array['locales'][$locale],
                            'locale' => $locale
                        ]);
                    }
                }
            }
        }
        $options = OptionalAttribute::whereTypeId($id)->whereIn('id', $ids)->get();
        foreach ($options as $option) {
            $option->machines()->detach();
            $option->delete();
        }
    }


    function setAvgMarketPrice(Request $request, $id)
    {
        $category = Type::query()->findOrFail($id);

        $request->validate([
            'price' => 'required|numeric|min:0|max:99999999',
            'type' => 'required|in:' . implode(',', Price::getTypes()),
        ]);
        $market = $category->avg_prices()->where('country_id', RequestHelper::requestDomain()->country->id)
            ->where('type', $request->input('type'))->first();

        $market
            ? $market->update(
                [
                    'price' => numberToPenny($request->input('price')),
                    'type' => $request->input('type')
                ])
            : CategoryAvgMarketPrice::create([
                'category_id' => $id,
                'country_id' => RequestHelper::requestDomain()->country->id,
                'price' => numberToPenny($request->input('price')),
                'type' => $request->input('type')
            ]);

        return \response()->json();
    }


}
