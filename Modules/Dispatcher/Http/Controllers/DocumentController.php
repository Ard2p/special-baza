<?php

namespace Modules\Dispatcher\Http\Controllers;

use Illuminate\Http\Request;
use App\Service\RequestBranch;
use Illuminate\Routing\Controller;
use Modules\Dispatcher\Entities\Customer\CustomerContract;
use Modules\Dispatcher\Http\Requests\DownloadContractRequest;
use Modules\Orders\Entities\Order;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Services\DocumentService;
use Modules\Orders\Services\OrderDocumentService;
use Modules\PartsWarehouse\Entities\PartsProvider;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Http\Requests\CreateContractRequest;

class DocumentController extends Controller
{
    private $companyBranch;

    public function __construct(
        Request       $request,
        RequestBranch $companyBranch
    )
    {
        $this->companyBranch = $companyBranch->companyBranch;
    }

    public function generateContract(CreateContractRequest $request, $id)
    {
        $contragentEntitie = match ($request->input('contragent_type')) {
            'customer' => Customer::find($id),
            'provider' => PartsProvider::find($id),
            'contractor' => Contractor::find($id)
        };
        $documentService = new DocumentService($request->validated(), $this->companyBranch);
        return DocumentService::generateContract($request, $contragentEntitie, $this->companyBranch); //TODO: убрать реквест и компанибренч
    }

    public function downloadContract(DownloadContractRequest $request, $id)
    {
        $customerContract = CustomerContract::find($id);
        $documentService = new DocumentService($request->validated(), $this->companyBranch);
        return $documentService->getOrderContractUrl($customerContract, $request); //TODO: убрать реквест
    }
}
