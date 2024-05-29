<?php


namespace Modules\ContractorOffice\Services;


use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\Workers\DriverDocument;
use Modules\ContractorOffice\Entities\Workers\DrivingLicence;
use Modules\ContractorOffice\Entities\Workers\MachineryLicence;

class DriverService
{
    public $worker;

    private $driverDocument;

    public function __construct(CompanyWorker $worker)
    {
        $this->worker = $worker;
    }

    private function createdDriverDocumentIfNotExists(): DriverDocument
    {
        if (!$this->worker->driverDocument) {
            $this->worker->driverDocument = $this->worker->driverDocument()->save(new DriverDocument());
        }

        return $this->worker->driverDocument;
    }

    function saveDocument($data)
    {
        $document = $this->createdDriverDocumentIfNotExists();
        $document->update($data);
        return $this;
    }

    function setDrivingCategories($data)
    {

        $document = $this->createdDriverDocumentIfNotExists();

        foreach ($data as $category) {
            /** @var DrivingLicence $drivingLicence */
            $drivingLicence = !empty($category['id'])
                ? $document->drivingCategories()->findOrFail($category['id'])
                : new DrivingLicence();

            $drivingLicence->fill($category);
            $drivingLicence->document()->associate($document);
            $drivingLicence->save();
        }

        return $this;
    }

    function setMachineryCategories($data)
    {
        $document = $this->createdDriverDocumentIfNotExists();

        foreach ($data as $category) {
            /** @var MachineryLicence $machineryLicence */
            $machineryLicence = !empty($category['id'])
                ? $document->machineryCategories()->findOrFail($category['id'])
                : new MachineryLicence();

            $machineryLicence->fill($category);
            $machineryLicence->document()->associate($document);
            $machineryLicence->save();
        }

        return $this;
    }

}