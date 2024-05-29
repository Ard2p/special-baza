<?php


namespace App\Service\AlfaBank\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class PaymentReceived extends DataTransferObject
{
//{
//"actionType": "create",
//"eventTime": "2023-08-02T14:59:37.583Z",
//"object": "ul_transaction_default",
//"data": {
//"amount": {
//"amount": 40,
//"currencyName": "RUR"
//},
//"amountRub": {
//    "amount": 40,
//      "currencyName": "RUR"
//    },
//    "correspondingAccount": "30101810200000000593",
//    "direction": "CREDIT",
//    "documentDate": "2023-08-02",
//    "filial": "АО \"АЛЬФА-БАНК\" г Москва",
//    "number": "240",
//    "operationCode": "01",
//    "operationDate": "2023-08-02T14:59:37Z",
//    "paymentPurpose": "В том числе НДС 20%, 2.17 руб. ТЕСТ JMON",
//    "priority": "5",
//    "uuid": "7d6ec193-50af-31d7-b650-9b233d891f29",
//    "transactionId": "1221207MOCO#DS4000010",
//    "debtorCode": "00000",
//    "extendedDebtorCode": "50012008",
//    "rurTransfer": {
//    "deliveryKind": "электронно",
//      "departmentalInfo": {
//        "uip": "18880077170010295651",
//        "drawerStatus101": "01",
//        "kbk": "39210202010061000160",
//        "oktmo": "11605000",
//        "reasonCode106": "0",
//        "taxPeriod107": "03.10.2020",
//        "docNumber108": "123",
//        "docDate109": "02.08.23",
//        "paymentKind110": "1"
//      },
//      "payeeAccount": "40702810102300000001",
//      "payeeBankBic": "044525593",
//      "payeeBankCorrAccount": "30101810200000000593",
//      "payeeBankName": "АО \"АЛЬФА-БАНК\" г Москва",
//      "payeeInn": "0665413230",
//      "payeeKpp": "011206020",
//      "payeeName": "Общество с ограниченной ответственностью \"Ромашка\"",
//      "payerAccount": "47423810601300000169",
//      "payerBankBic": "044525593",
//      "payerBankCorrAccount": "30101810200000000593",
//      "payerBankName": "АО \"АЛЬФА-БАНК\" г Москва",
//      "payerInn": "0140237176",
//      "payerKpp": "037186025",
//      "payerName": "Полное наименование Орг № 11329",
//      "payingCondition": "1",
//      "purposeCode": "2",
//      "receiptDate": "2023-08-02",
//      "valueDate": "2023-08-02"
//    }
//  }
//}
    public string $actionType;
    public string $eventTime;

    public string $object;
    public PaymentData $data;
}
