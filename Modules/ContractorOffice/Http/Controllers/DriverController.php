<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Helpers\RequestHelper;
use App\Imports\MachineryImport;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\MachineryModel;
use App\Machines\OptionalAttribute;
use App\Machines\Type;
use App\Service\RequestBranch;
use App\Support\Gmap;
use App\Support\Region;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel;
use Modules\AdminOffice\Entities\Filter;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Driver;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Http\Requests\CreateVehicle;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\ContractorOffice\Services\VehicleService;
use Modules\ContractorOffice\Transformers\Vehicle;
use Modules\ContractorOffice\Transformers\VehiclesCollection;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\LeadPosition;
use Modules\Dispatcher\Entities\PreLeadPosition;
use Modules\Dispatcher\Services\LeadService;
use Modules\Integrations\Rules\Coordinates;
use Modules\Integrations\Services\Appraiser\AppraiserService;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Rap2hpoutre\FastExcel\FastExcel;

class DriverController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
    }

    public function index(Request $request)
    {
       return  response()->json(Driver::query()->forBranch()->get());
    }
    public function store(Request $request)
    {
        $data = $request->all();
        $driver = Driver::query()->create($data);
        return  response()->json($driver);
    }
    public function update(Request $request, Driver $driver)
    {
        $data = $request->all();
        $driver->update($data);
        return  response()->json($driver->fresh());
    }
    public function destroy(Request $request, Driver $driver)
    {
        $driver->delete();
        return  response()->json([]);
    }
}
