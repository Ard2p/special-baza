<?php


namespace App\Service\Insurance;

use App\Http\Controllers\Auth\LoginController;
use App\Rate;
use App\Service\RatesService;
use App\Service\Scoring\ScoringService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\InsCertificate;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\OrderComponentHistory;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\TemplateProcessor;

class InsuranceService
{
    protected ScoringService $scoringService;

    public function __construct()
    {
        $this->scoringService = new ScoringService();
    }

    public function getScoring(Order|Model $order)
    {
        $data = [];
        $legalRequisites = $order->customer->legal_requisites;
        $individualRequisites = $order->customer->individual_requisites;

        if ($legalRequisites && !empty($legalRequisites->inn)) {

            $data['type'] = 'legal';
            $data['result'] = $this->scoringService->checkLegal($legalRequisites->inn, $order->customer->id);

        } elseif ($individualRequisites) {
            if (!empty($individualRequisites->inn) && $individualRequisites->type == 'entrepreneur') {
                $data['type'] = 'legal';
                $data['result'] = $this->scoringService->checkLegal($individualRequisites->inn, $order->customer->id);
            } else {
                $data['type'] = 'physical';
                $data['result'] = $this->scoringService->checkPhisycal(
                    $individualRequisites->firstname,
                    $individualRequisites->surname,
                    $individualRequisites->middlename,
                    Carbon::parse($individualRequisites->birth_date)->format('d.m.Y'),
                    $individualRequisites->passport_number,
                    Carbon::parse($individualRequisites->passport_date)->format('d.m.Y'),
                    $order->customer->id
                );
            }
        }

        return $data;
    }

    public function createInsuranceCertificate(
        OrderComponent $component,
        string $type = 'legal',
        $dateFrom = null,
        $dateTo = null,
        $duration = null
    ) {
        if ($component->worker instanceof WarehousePartSet) {
            return;
        }

        $this->clearPrevious($dateFrom, $dateTo, $duration, $component);

        $prefix = $type == 'legal' ? 'b2b' : 'b2c';

        $amount = $component->worker->market_price / 100;
        $tariffSetting = $component->order->company_branch->ins_tariff_settings()
            ->where('market_price_min', '<=', $amount)
            ->where('market_price_max', '>', $amount)
            ->first();

        if (!$tariffSetting) {
            $component->histories()->save(new OrderComponentHistory([
                'type' => 'certificate_not_created',
                'description' => "Не выпущены страховые сертификаты из-за превышения лимита рыночной стоимости.",
            ]));
            return;
        }

        $percentage = $this->getPercentage($component, $prefix, $tariffSetting);
        $duration = ($duration) ?: $component->order_duration;
        $sum = ($component->cost_per_unit * $duration);
        $insuranceCost = $sum / 100 * $percentage;
        $insuranceCostPerUnit = $component->cost_per_unit / 100 * $percentage;

        $dateFrom = ($dateFrom) ?: $component->date_from;
        $dateTo = ($dateTo) ?: $component->date_to;

        $dateRange = CarbonPeriod::create($dateFrom, $dateTo);

        $currency = Str::upper($component->worker->market_price_currency);
        $rate = 1;
        if ($currency !== 'RUB') {
            $rate = Rate::query()
                ->where('to_currency', $currency)
                ->where('date', Carbon::now()->format('Y-m-d'))
                ->first();
            if (!$rate) {
                $rateService = new RatesService();
                $rateService->getRates(['EUR', 'USD', 'CNY']);
                $rate = Rate::query()
                    ->where('to_currency', $currency)
                    ->where('date', Carbon::now()->format('Y-m-d'))
                    ->first();
            }
            $rate = $rate->rate;
        }

        $insuranceAmount = $component->worker->market_price * $rate;

        foreach ($dateRange as $date) {
            InsCertificate::query()->create([
                'premium' => $insuranceCostPerUnit,
                'sum' => $insuranceAmount,
                'date_from' => Carbon::parse($date)->startOfDay(),
                'date_to' => Carbon::parse($date)->endOfDay(),
                'order_worker_id' => $component->id,
                'attachment' => '',
                'status' => 1,
                'company_branch_id' => $component->order->company_branch->id,
            ]);
        }

        $insCost = $component->order->company_branch->ins_setting->increase_rent_price ? $insuranceCost : 0;
        $insCostPerUnit = $component->order->company_branch->ins_setting->increase_rent_price ? $insuranceCostPerUnit : 0;

        $component->update([
            'insurance_cost' => $component->insurance_cost + $insCost,
            'insurance_cost_per_unit' => $component->insurance_cost_per_unit + $insCostPerUnit
        ]);

        $insuranceCostHistory = number_format($insuranceCost / 100, 2, ',', ' ');

        $insCertFrom = $dateRange->start->format('d.m.Y');
        $insCertTo = $dateRange->end->format('d.m.Y');

        $component->histories()->save(new OrderComponentHistory([
            'type' => 'certificate_created',
            'description' => "Выпущены страховые сертификаты за период с $insCertFrom по $insCertTo, на сумму $insuranceCostHistory р.",
        ]));
    }

    /**
     * @param  OrderComponent  $component
     * @param  string  $prefix
     * @param $tariffSetting
     * @return mixed
     */
    private function getPercentage(OrderComponent $component, string $prefix, $tariffSetting): mixed
    {
        if ($component->order_type == 'hour') {
            $now = Carbon::now();
            $days = $now->diffInDays($now->copy()->addHours($component->order_duration));
        } else {
            $days = $component->order_duration;
        }

        if ($days < 5) {
            $percentage = $tariffSetting->{$prefix.'_tariff_1_5'};
        } elseif ($days < 21) {
            $percentage = $tariffSetting->{$prefix.'_tariff_5_21'};
        } elseif ($days < 60) {
            $percentage = $tariffSetting->{$prefix.'_tariff_21_60'};
        } else {
            $percentage = $tariffSetting->{$prefix.'_tariff_60'};
        }
        return $percentage;
    }

    /**
     * @param  InsCertificate|Model  $insCertificate
     * @param  OrderComponent  $component
     * @return string
     * @throws FileNotFoundException
     * @throws CopyFileException
     * @throws CreateTemporaryFileException
     */
    public function generateDocument(InsCertificate|Model $insCertificate, OrderComponent|Model $component)
    {
        if (!empty($insCertificate->attachment)) {
            return $insCertificate->attachment;
        }

        $branch = $component->order->company_branch;
        $insSetting = $branch->ins_setting;

        $legal = $component->order->customer->legal_requisites !== null;
        $requisites = $component->order->customer->getRequisites();

        $sum = number_format(($insCertificate->sum / 100), 2, ",", " ").' р.';
        $premium = number_format(($insCertificate->premium / 100), 2, ",", " ").' р.';

        if (!Storage::disk()->exists('certificates')) {
            Storage::disk()->makeDirectory('certificates');
        }

        if (!Storage::disk('public')->exists('certificates')) {
            Storage::disk('public')->makeDirectory('certificates');
        }

        $data = [
            'ins_settings_contract_number' => $insSetting->contract_number,
            'ins_settings_contract_date' => Carbon::parse($insSetting->date_from)->format('d.m.Y'),
            'ins_certificates_number' => $insCertificate->name,
            'company_branches_name' => $component->order->contractorRequisite->short_name,
            'ins_certificates_sum' => $sum,
            'ins_certificates_premium' => $premium,
            'ins_certificates_date_from' => Carbon::parse($insCertificate->date_from)->format('d.m.Y H:i'),
            'ins_certificates_date_to' => Carbon::parse($insCertificate->date_to)->format('d.m.Y H:i'),
            'legal_name' => $legal ? $requisites->name : '',
            'legal_address' => $legal ? $requisites->register_address : '',
            'legal_inn' => $legal ? $requisites->inn : '',
            'legal_phone' => $legal ? $requisites->phone : '',
            'physical_name' => !$legal ? $requisites->name : '',
            'physical_phone' => !$legal ? $requisites->phone : '',
            'physical_passport' => !$legal ? $requisites->passport_number : '',
            'physical_address' => !$legal ? $requisites->register_address : '',
            'type' => $component->worker->_type?->name,
            'brand' => $component->worker->brand?->name,
            'model' => $component->worker->model?->name,
            'serial' => $component->worker->serial_number,
            'rent_contract_number' => $component->order->customer->contract->current_number,
            'rent_date' => Carbon::parse($component->date_from)->format('d.m.Y H:i'),
        ];

        $templateProcessor = new TemplateProcessor(Storage::disk('local')->path('data/templates/ins_certificate.docx'));
        $templateProcessor->setValues($data);
        $file_name = str_replace('/', '_', $insCertificate->name);
        $file_name = str_replace(' ', '_', $file_name);
        $file_name .= '__'.time();
        $path = "certificates/$file_name.docx";
        $templateProcessor->saveAs(Storage::disk('public')->path($path));
        Storage::disk()->put($path, Storage::disk('public')->get($path));
        Storage::disk('public')->delete($path);

        $insCertificate->attachment = $path;

        $insCertificate->save();

        return $path;
    }

    /**
     * @param  mixed  $dateFrom
     * @param  mixed  $dateTo
     * @param  mixed  $duration
     * @param  OrderComponent  $component
     * @return void
     */
    private function clearPrevious(mixed $dateFrom, mixed $dateTo, mixed $duration, OrderComponent $component): void
    {
        if ($dateFrom == null && $dateTo == null && $duration == null) {

            if (!$component->ins_certificates()->exists()) {
                return;
            }

            foreach ($component->ins_certificates as $certificate) {
                $certificate->update([
                    'status' => 2
                ]);
            }
            $clearFrom = Carbon::parse($component->ins_certificates->first()->date_from)->format('d.m.Y');
            $clearTo = Carbon::parse($component->ins_certificates->last()->date_to)->format('d.m.Y');

            $component->histories()->save(new OrderComponentHistory([
                'type' => 'certificate_canceled',
                'description' => "Сертификаты на период с $clearFrom по $clearTo аннулированы",
            ]));
            $component->update([
                'insurance_cost' => 0,
                'insurance_cost_per_unit' => 0,
            ]);
        }
    }

    public function export(string $date_from, string $date_to, CompanyBranch $branch = null)
    {
        if (!Storage::disk('public')->exists('exports/insurance')) {
            Storage::disk('public')->makeDirectory('exports/insurance');
        }
        $certs = InsCertificate::query()
            ->with([
                'order_worker',
                'order_worker.order',
                'order_worker.order.customer',
                'order_worker.order.customer.contract',
                'order_worker.order.customer.individual_requisites',
                'order_worker.order.customer.entity_requisites',
            ])
            ->where('status', InsCertificate::STATUS_ACTIVE);

        if ($date_from) {
            $certs = $certs->where(DB::raw('DATE(date_from)'), '>=', $date_from);
        }
        if ($date_to) {
            $certs = $certs->where(DB::raw('DATE(date_to)'), '<=', $date_to);
        }
        if ($branch) {
            $certs = $certs->where('company_branch_id', $branch->id);
        }

        $certs = $certs->get();


        $spreadsheet = IOFactory::load(storage_path('app/data/templates/insurance_export_template.xlsx'));

        $worksheet = $spreadsheet->getActiveSheet();
        $position = 3;

        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $styleBoldArray = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ];

        $insuranceSum = 0;
        $insurancePremium = 0;
        foreach ($certs as $cert) {
            $type = 'person';

            $component = $cert->order_worker;
            $customer = $component->order->customer;
            $requisites = $customer->getRequisites();

            if ($component->order->customer->legal_requisites !== null) {
                $type = 'legal';
            } elseif ($requisites->type == 'entrepreneur') {
                $type = 'entrepreneur';
            }

            $sum = number_format(($cert->sum / 100), 2, ",", " ").' р.';
            $premium = number_format(($cert->premium / 100), 2, ",", " ").' р.';

            $worksheet->getStyle("A$position:Q$position")
                ->getAlignment()->setWrapText(true);

            $worksheet->getStyle("A$position:Q$position")->applyFromArray($styleArray);

            $name = null;
            $id = null;
            $address = null;
            $phone = null;

            switch ($type) {
                case 'person':
                    $name = $requisites->name;
                    $id = $requisites->passport_number;
                    $address = $requisites->register_address;
                    $phone = $requisites->phone;
                    break;
                case 'entrepreneur':
                    $name = $customer->company_name;
                    $id = $requisites->inn;
                    $address = $requisites->register_address;
                    $phone = $customer->phone;
                    break;
                case 'legal':
                    $name = $requisites->name;
                    $id = $requisites->inn;
                    $address = $requisites->register_address;
                    $phone = $requisites->phone;
                    break;
            }

            $worksheet->getCell('A'.$position)->setValue($cert->number);
            $worksheet->getCell('B'.$position)->setValue($type === 'preson' ? "$name, $id" : '');
            $worksheet->getCell('C'.$position)->setValue($type === 'preson' ? "$address, $phone" : '');

            $worksheet->getCell('D'.$position)->setValue($type !== 'preson' ? "$name" : '');
            $worksheet->getCell('E'.$position)->setValue($type !== 'preson' ? "$id " : '');
            $worksheet->getCell('F'.$position)->setValue($type !== 'preson' ? "$address" : '');
            $worksheet->getCell('G'.$position)->setValue($type !== 'preson' ? "$phone" : '');
            $worksheet->getCell('H'.$position)->setValue($component->worker->_type?->name);
            $worksheet->getCell('I'.$position)->setValue($component->worker->brand?->name);
            $worksheet->getCell('J'.$position)->setValue($component->worker->model?->name);
            $worksheet->getCell('K'.$position)->setValue($component->worker->serial_number);
            $worksheet->getCell('L'.$position)->setValue($component->order->customer->contract->current_number);
            $worksheet->getCell('M'.$position)->setValue($sum);
            $worksheet->getCell('N'.$position)->setValue($premium);
            $worksheet->getCell('O'.$position)->setValue(Carbon::parse($component->date_from)->format('d.m.Y H:i'));
            $worksheet->getCell('P'.$position)->setValue(Carbon::parse($cert->date_from)->format('d.m.Y H:i'));
            $worksheet->getCell('Q'.$position)->setValue(Carbon::parse($cert->date_to)->format('d.m.Y H:i'));

            $insuranceSum += $cert->sum;
            $insurancePremium += $cert->premium;

            $position++;
        }

        $sum = number_format(($insuranceSum / 100), 2, ",", " ").' р.';
        $premium = number_format(($insurancePremium / 100), 2, ",", " ").' р.';

        $worksheet->getStyle("A$position:Q$position")->applyFromArray($styleArray);

        $worksheet->mergeCells("A$position:B$position");
        $worksheet->mergeCells("C$position:L$position");

        $worksheet->getCell('M'.$position)->setValue($sum);
        $worksheet->getCell('N'.$position)->setValue($premium);

        $worksheet->getStyle("C$position")->applyFromArray($styleBoldArray);

        $worksheet->getCell("C$position")->setValue('ИТОГО:');

        $position += 2;

        $worksheet->mergeCells("A$position:Q$position");
        $worksheet->getCell("A$position")->setValue("Итого общая страховая премия по Реестру застрахованных Устройств, подлежащая перечислению Страхователем Страховщику, составляет: _______________________ (________) рублей ___ копеек.");
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $name = '';
        if ($branch) {
            if (!Storage::disk('public')->exists('exports/insurance/'.$branch->id)) {
                Storage::disk('public')->makeDirectory('exports/insurance/'.$branch->id);
            }
            $name .= $branch->id.'/';
        }
        $name .= 'Выгрузка_реестра_с_'.$date_from.'_по_'.$date_to.'.xls';
        $path = '/exports/insurance/'.$name;
        $writer->save(Storage::disk('public')->path($path));

        return $path;
    }

}
