<?php

namespace Modules\RestApi\Emails;

use App\Option;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;
use Modules\AdminOffice\Entities\Marketing\Mailing\Template;

class DBMail extends Mailable
{
    use Queueable, SerializesModels;

    public $template, $data, $locale, $attach;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Template $template, array $data, $attachments = [])
    {
        $this->locale = App::getLocale();
        $this->attach = $attachments;
        $this->data = $data;
        $this->template = $template;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        App::setLocale($this->locale);
        $this->template->parse($this->data);
        foreach ($this->attach as $attachment) {

            $this->attachFromStorageDisk(null, $attachment['path']);
        }
        return $this->view('email.main_layout', ['template' => $this->template])
            ->subject($this->template->name);
    }
}
