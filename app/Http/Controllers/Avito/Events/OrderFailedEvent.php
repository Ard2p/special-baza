<?php

namespace App\Http\Controllers\Avito\Events;

use App\Http\Controllers\Avito\Models\AvitoOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Mail;
use Modules\Orders\Entities\Order;

class OrderFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public AvitoOrder $avitoOrder;
    public int $oldStatus;
    public int $newStatus;
    public string $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(AvitoOrder $avitoOrder, int $cancelReason, string $message)
    {
        $this->avitoOrder = $avitoOrder;
        $this->oldStatus = $avitoOrder->status;
        $avitoOrder->update([
            'status' => AvitoOrder::STATUS_CANCELED,
            'cancel_reason' => $cancelReason
        ]);
        $this->newStatus = AvitoOrder::STATUS_CANCELED;
        $this->message = $message;


    }

}
