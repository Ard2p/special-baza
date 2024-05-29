@switch($call->call_status)
    @case(\Modules\Telephony\Entities\YandexTelephony::CALL_IN_PROGRESS)
    <span class="badge badge-primary">Идет разговор....</span>
    @break
    @case(\Modules\Telephony\Entities\YandexTelephony::CALL_COMPLETE)
    <span class="badge badge-success">Завершен</span>
    @break
    @case(\Modules\Telephony\Entities\YandexTelephony::CALL_START)
    <span class="badge badge-warning">Подключение</span>
    @break
@endswitch