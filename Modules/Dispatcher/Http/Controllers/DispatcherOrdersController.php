<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jurosh\PDFMerge\PDFMerger;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\ContractorPay;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Dispatcher\Transformers\DispatcherOrderInfo;
use Modules\Dispatcher\Transformers\OrderList;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderDocument;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use PDF;

class DispatcherOrdersController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PAYMENTS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index',
        ]);
         $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['requitesForInvoice', 'paid']);
        //  $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy', 'closeLead']);

    }
    public function index(Request $request)
    {
        $orders = Order::query()->currentUser();


        $orders->orderBy('created_at', 'desc');

        return OrderList::collection($orders->paginate($request->input('per_page', 10)));
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return DispatcherOrderInfo::make(DispatcherOrder::query()->currentUser()->findOrFail($id));
    }


    function complete($id)
    {
        $order = DispatcherOrder::query()->whereStatus(DispatcherOrder::STATUS_ACCEPT)->currentUser()->findOrFail($id);

        DB::beginTransaction();

        $order->complete();

        DB::commit();

        return response()->json();
    }

    function paid($id)
    {
        $order = DispatcherOrder::query()->currentUser()->findOrFail($id);
        $order->update([
            'is_paid' => 1
        ]);

        return response()->json();
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    function requitesForInvoice(Request $request)
    {
        $order =
            $request->input('owner_type') === 'order'
                ? Order::query()->forBranch()->findOrFail($request->input('owner_id'))
        : PartsSale::query()->forBranch()->findOrFail($request->input('owner_id'));


        $branch = $this->companyBranch;
        $customer_entity_requisite = $order->customer->legal_requisites;

        $dispatcher_entity_requisite = $order->company_branch->company->domain->alias === 'ru'
            ? $branch->entity_requisites
            : $branch->international_legal_requisites;

        return response()->json(
            [
                'customer_legal_requisites' =>  $customer_entity_requisite,
                'customer_individual_requisites' => $order->customer->individual_requisites,
                'dispatcher_legal_requisites' => $dispatcher_entity_requisite,
                'dispatcher_individual_requisites' => $branch->individualRequisites,
            ]
        );
    }


    function addContractorPay(Request $request, $id)
    {
        $order = DispatcherOrder::query()->currentUser()->findOrFail($id);

        $request->validate([
            'type' => 'required|in:card|cashless',
            'date' => 'required|date',
            'sum' => 'required|numeric|min:1|max:' . (($order->contractor_sum - $order->contractor_paid_sum) / 100),
        ]);

        $pay = new ContractorPay([
            'type' => $request->input('type'),
            'date' => Carbon::parse($request->input('date')),
            'sum' => numberToPenny($request->input('sum')),
        ]);

        $pay->contractor()->associate($order->contractor);

        $order->contractor_pays()->save($pay);

        return \response()->json($pay);
    }

    function changeAmount(Request $request, $id)
    {
        $order = DispatcherOrder::query()
            ->currentUser()
            ->whereDoesntHave('invoices')
            ->findOrFail($id);
        $min = ($order->contractor_sum / 100);
        $request->validate([
            'sum' => "required|numeric|min:{$min}|max:9999999"
        ]);

        $order->update([
            'amount' => numberToPenny($request->input('sum'))
        ]);

        return response()->json();
    }


    function uploadDoc(Request $request, $id)
    {
        $order = DispatcherOrder::currentUser()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'doc' => 'required|string|max:255',
        ]);
        $tmp_dir = config('app.upload_tmp_dir');

        $tmp_file_path = "{$tmp_dir}/{$request->input('doc')}";

        $exists = Storage::disk()->exists($request->input('doc'));

        if (!$exists) {
            return response()->json(['doc' => ['Файл не найден. Попробуйте еще раз.']], 400);
        }


        return  $order->addDocument($request->input('name'), $tmp_file_path);
    }

    function getDocuments($id)
    {
        $order = DispatcherOrder::currentUser()->findOrFail($id);

        return $order->documents()->get();
    }

    function mergeDocx(Order $order, $documents)
    {
        $dt = Carbon::now()->format('d.m.Y H-i');
        $docxName = "Документы по сделке аренды № $order->internal_number от $dt";
        $docxPath = config('app.upload_tmp_dir') . "/$docxName.docx";

        $lastDoc = end($documents);

        $mainDoc = new \PhpOffice\PhpWord\PhpWord();
        $paths = [];
        foreach($documents as $document) {
            $path = config('app.upload_tmp_dir').'/'.time().$document['id'].'.docx';
            $paths[] = $path;
            Storage::disk('public_disk')
                ->put(
                    $path,
                    Storage::disk()->get($document['url'])
                );

            $appendDoc = \PhpOffice\PhpWord\IOFactory::load($path);
            foreach ($appendDoc->getSections() as $section) {
                $mainDoc->addSection($section);
            }

            // Add page break after every appended doc except the last one
            if($document['id'] != $lastDoc['id']) {
                $section = $mainDoc->addSection();
                $section->addPageBreak();
            }
        }
        $mainDoc->save($docxPath);

        Storage::disk()->put($docxPath, Storage::disk('public_disk')->get($docxPath));

        $document = $order->addDocument($docxName, $docxPath);

        Storage::disk('public_disk')->delete($docxPath);
        Storage::disk('public_disk')->delete($paths);

        return $document;
    }

    function mergePdf(Request $request, Order $order)
    {
        $dt = Carbon::now()->format('d.m.Y H-i');
        $pdfName = "Документы по сделке аренды № $order->internal_number от $dt (PDF)";
        $pdfPath = config('app.upload_tmp_dir') . "/$pdfName.pdf";

        $merger = new PDFMerger();
        $documents = OrderDocument::query()->whereIn('id',$request->documents_ids)->get();
        if($documents->contains(fn($doc) => pathinfo($doc->url, PATHINFO_EXTENSION) === 'docx')) {
            return  $this->mergeDocx($order, $documents->toArray());
        }
        $paths = [];
        foreach ($documents as $document) {
            $path = config('app.upload_tmp_dir').'/'.time().$document->id.'.pdf';
            $paths[] = $path;
            Storage::disk('public_disk')
                ->put(
                    $path,
                    Storage::disk()->get($document->url)
                );

            $merger->addPdf(public_path($path));
        }

        $merger->merge('file', public_path($pdfPath));

        Storage::disk()->put($pdfPath, Storage::disk('public_disk')->get($pdfPath));

        $document = $order->addDocument($pdfName, $pdfPath);

        Storage::disk('public_disk')->delete($paths);
        return $document;
    }
}
