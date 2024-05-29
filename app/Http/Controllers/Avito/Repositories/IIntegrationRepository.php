<?php

namespace App\Http\Controllers\Avito\Repositories;

use App\Http\Controllers\Avito\Dto\CreateOrderConditions;
use App\Http\Controllers\Avito\Dto\CreateOrderCustomerConditions;
use App\Machinery;
use App\Overrides\Model;
use Carbon\Carbon;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\Dispatcher\Entities\Customer;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;

interface IIntegrationRepository
{

    public function addCustomer(
        CreateOrderConditions $conditions,
        CompanyBranch $companyBranch,
        $customerInfo
    ): Customer|Model;

    public function findMachinery(string $avito_id,string $avitoOrderId, Carbon $date_from, int $order_duration, int $offset): array;

    public function attachDocuments(CreateOrderConditions $conditions, Customer|Model $customer, $customerInfo): array;

    public function createOrder(
        CreateOrderConditions $conditions,
        mixed $documents_pack_id,
        Customer|Model $customer,
        Machinery|Model $machinery
    ): Order|Model;

    public function attachMachinery(
        CreateOrderConditions $conditions,
        Order|Model $order,
        Model|Machinery $machinery,
        int $offset
    ): OrderComponent;

    public function attachContacts(
        CreateOrderConditions $conditions,
        Customer|Model $customer,
        Order|Model $order
    ): Contact|Model;

    public function addPayment(CreateOrderConditions $conditions, Order|Model $order): void;

}
