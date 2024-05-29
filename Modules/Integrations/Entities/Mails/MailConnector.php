<?php

namespace Modules\Integrations\Entities\Mails;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\PreLead;
use Modules\Orders\Entities\Order;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class MailConnector extends Model
{
    use BelongsToCompanyBranch, HasManager;

    private static $serviceUrl;

    public $timestamps = false, $lastStatusCode = 200, $client;

    protected $fillable = [
        'token',
        'email',
    ];

    protected $appends = ['service_connector'];

    protected $casts = [
        'token' => 'object'
    ];

    function owner()
    {
        return $this->morphTo();
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        self::$serviceUrl = config('tbmail.service_url');
        $this->client = new Client([
            'verify' => false,
            'base_uri' => self::$serviceUrl,
            'http_errors' => false
        ]);
    }

    function getServiceConnectorAttribute()
    {
        $request = $this->client->get("get/{$this->token->uuid}", [
            RequestOptions::JSON => [
                'secret' => $this->token->secret,
            ]
        ]);

        return json_decode($request->getBody()->getContents(), true);
    }

    static function createConnector($email, $password, $server, array $smtp, $owner, CompanyBranch $companyBranch)
    {
        if (self::query()->where('owner_type', get_class($owner))->where('owner_id', $owner->id)->exists()) {
            $error = ValidationException::withMessages([
                'email' => ['Почтовый ящик уже подключен']
            ]);

            throw $error;
        }
        $client = new Client([
            'base_uri' => self::$serviceUrl,
            'verify' => false
        ]);

        $request = $client->post('create', [
            RequestOptions::JSON => [
                'email' => $email,
                'password' => $password,
                'server' => $server,
                'smtp_server' => $smtp['server'] ?? null,
                'smtp_port' => $smtp['port'] ?? null,
            ]
        ]);

        $response = json_decode($request->getBody()->getContents(), true);

        if ($request->getStatusCode() !== 200) {
            $error = ValidationException::withMessages([
                'email' => ['Ошибка подключения. Проверьте настройки почты.']
            ]);

            throw $error;
        }
        $connector = new self([
            'token' => [
                'uuid' => $response['uuid'],
                'secret' => $response['secret'],
            ],
            'email' => $email
        ]);

        $connector->owner()->associate($owner);
        $connector->creator_id = Auth::id();
        $connector->company_branch_id = $companyBranch->id;
        $connector->save();

        return $connector;
    }

    function updateConnector($email, $password, $server, array $smtp)
    {
        $client = new Client([
            'base_uri' => self::$serviceUrl,
            'verify' => false
        ]);

        $request = $client->patch("mailbox/{$this->token->uuid}", [
            RequestOptions::JSON => [
                'email' => $email,
                'password' => $password,
                'secret' => $this->token->secret,
                'server' => $server,
                'smtp_server' => $smtp['server'] ?? null,
                'smtp_port' => $smtp['port'] ?? null,
            ]
        ]);

        $response = json_decode($request->getBody()->getContents(), true);

        if ($request->getStatusCode() !== 200) {
            $error = ValidationException::withMessages([
                'email' => ['Ошибка подключения. Проверьте настройки почты.']
            ]);

            throw $error;
        }
        $this->update([
            'email' => $email
        ]);

        return $this;
    }

    function deleteConnector()
    {
        $client = new Client([
            'base_uri' => self::$serviceUrl,
            'verify' => false
        ]);

        $request = $client->delete("mailbox/{$this->token->uuid}", [
            RequestOptions::JSON => [
                'secret' => $this->token->secret,
            ]
        ]);

        $response = json_decode($request->getBody()->getContents(), true);

        if ($request->getStatusCode() !== 200) {
            $error = ValidationException::withMessages([
                'email' => ['Ошибка подключения. Проверьте настройки почты.']
            ]);

            throw $error;
        }

        $this->delete();

        return $this;
    }

    function getMails($query = [])
    {
        /*   $stack = HandlerStack::create();
        $logger =  new Logger('Logger');
        $logger->pushHandler(new StreamHandler(storage_path('logs/guzzle.log')));
        $stack->push(
            Middleware::log(
                $logger,
                new MessageFormatter('{req_body} - {res_body}')
            )
        );*/

        if (!empty($query['pre_lead_internal_number'])) {
            $pre_lead = PreLead::where('internal_number', $query['pre_lead_internal_number'])->forBranch()->first();
            if ($pre_lead) $query['pre_lead_id'] = $pre_lead->id;
        }
        unset($query['pre_lead_internal_number']);

        if (!empty($query['lead_internal_number'])) {
            $lead = Lead::where('internal_number', $query['lead_internal_number'])->forBranch()->first();
            if ($lead) $query['lead_id'] = $lead->id;
        }
        unset($query['lead_internal_number']);

        if (!empty($query['order_internal_number'])) {
            $order = Order::where('internal_number', $query['order_internal_number'])->forBranch()->first();
            if ($order) $query['order_id'] = $order->id;
        }
        unset($query['order_internal_number']);

        $client = new Client([
            'base_uri' => self::$serviceUrl,
            'http_errors' => false,
            'verify' => false
            //'handler' => $stack,
        ]);
        $query['secret'] = $this->token->secret;

        $request = $client->get("get-mails/{$this->token->uuid}", [
            RequestOptions::JSON => $query
        ]);

        $mails = $request->getBody()->getContents();

        if (!empty($query['count_info']))
            return $mails;

        if (!empty($query['getCount']) || !empty($query['count'])) {
            return (int) $mails;
        } else {
            $mails = json_decode($mails, true);
        }
        $mails['data'] = $mails['data'] ?? [];
        $items = $mails['data'];

        if (!$items) {
            return $mails;
        }
        foreach ($items as &$mail) {

            $mail['customers'] = Customer::query()->with('contacts')->forBranch($this->company_branch_id)->hasEmail($mail['sender_address'])->get();

            if ($mail['bind_type']) {
                switch ($mail['bind_type']) {
                    case Order::class:
                        $bind  = 'order';
                        break;
                    case Lead::class:
                        $bind = 'lead';
                        break;
                    case PreLead::class:
                        $bind = 'prelead';
                        break;
                }
                $mail['bind'] = $bind ?? '';
            }
        }
        $mails['data'] = $items;
        return $mails;
    }

    function cloneMailing($uuid)
    {
        $data['secret'] = $this->token->secret;
        $request = $this->client->post("mailings/{$this->token->uuid}/{$uuid}/clone", [
            RequestOptions::JSON => $data
        ]);

        $this->lastStatusCode = $request->getStatusCode();
        return json_decode($request->getBody()->getContents(), true);
    }

    function sendRawEmail($recipients, $subject, $body, $files = [], $reply_to = null, $bindType = null, $bindId = null)
    {
        $data['secret'] = $this->token->secret;
        $data['recipients'] = $recipients;
        $data['files'] = $files;
        $data['subject'] = $subject;
        $data['body'] = $body;
        $data['reply_to'] = $reply_to;
        $data['bind_type'] = $bindType;
        $data['bind_id'] = $bindId;
        $request = $this->client->post("{$this->token->uuid}/send-email", [
            RequestOptions::JSON => $data
        ]);

        $data = $request->getBody()->getContents();

        $this->lastStatusCode = $request->getStatusCode();

        return json_decode($data, true);
    }

    function bindMail($entity, $mailId)
    {
        $client = new Client([
            'base_uri' => self::$serviceUrl,
            'verify' => false
        ]);

        $request = $client->post("bind-mail/{$this->token->uuid}", [
            'http_errors' => false,
            RequestOptions::JSON => [
                'email_uuid' => $mailId,
                'bind_type' => get_class($entity),
                'bind_id' => $entity->id,
                'secret' => $this->token->secret,
            ]
        ]);

        return $request->getBody()->getContents();
    }

    function unclip($mailId)
    {
        $client = new Client([
            'base_uri' => self::$serviceUrl,
            'verify' => false
        ]);

        $request = $client->post("bind-mail/{$this->token->uuid}", [
            'http_errors' => false,
            RequestOptions::JSON => [
                'email_uuid' => $mailId,
                'bind_type' => null,
                'bind_id' => null,
                'secret' => $this->token->secret,
            ]
        ]);

        return $request->getBody()->getContents();
    }

    function setStatus($type, $condition, $mailId)
    {
        $client = new Client([
            'base_uri' => self::$serviceUrl,
            'verify' => false
        ]);

        $request = $client->post("status/{$this->token->uuid}", [
            RequestOptions::JSON => [
                'email_uuid' => $mailId,
                'condition' => $condition,
                'type' => $type,
                'secret' => $this->token->secret,
            ]
        ]);

        return $request->getBody()->getContents();
    }

    function toSpam($email, $action = 'add')
    {
        $client = new Client([
            'base_uri' => self::$serviceUrl,
            'verify' => false
        ]);

        $request = $client->request($action === 'add' ? 'post' : 'delete', "mailbox/{$this->token->uuid}/spam", [
            RequestOptions::JSON => [
                'email' => $email,
                // 'action' => $action,
                'secret' => $this->token->secret,
            ]
        ]);

        return $request->getBody()->getContents();
    }

    function getMailings($query = [], $id = null)
    {
        $query['secret'] = $this->token->secret;
        $path = "mailings/{$this->token->uuid}";

        $path = $id ? "{$path}/{$id}" : $path;

        $request = $this->client->get($path, [
            RequestOptions::JSON => $query
        ]);
        // logger($request->getBody()->getContents());
        $this->lastStatusCode = $request->getStatusCode();
        return json_decode($request->getBody()->getContents(), true);
    }

    function addMailing($data)
    {
        $data['secret'] = $this->token->secret;
        $request = $this->client->post("mailings/{$this->token->uuid}", [
            RequestOptions::JSON => $data
        ]);
        $this->lastStatusCode = $request->getStatusCode();
        return json_decode($request->getBody()->getContents(), true);
    }

    function updateMailing($data, $mailingId)
    {
        $data['secret'] = $this->token->secret;
        $request = $this->client->patch("mailings/{$this->token->uuid}/{$mailingId}", [
            RequestOptions::JSON => $data
        ]);

        $this->lastStatusCode = $request->getStatusCode();
        return json_decode($request->getBody()->getContents(), true);
    }

    function addEmailsToMailing($data, $mailingId)
    {

        $data['secret'] = $this->token->secret;
        $data['action'] = 'add';

        $request = $this->client->patch("mailings/{$this->token->uuid}/{$mailingId}/emails", [
            RequestOptions::JSON => $data
        ]);

        $this->lastStatusCode = $request->getStatusCode();
        return json_decode($request->getBody()->getContents(), true) + ['data' => $data];
    }

    function getMailingEmails($data, $mailingId)
    {
        $data['secret'] = $this->token->secret;
        $request = $this->client->get("mailings/{$this->token->uuid}/{$mailingId}/emails", [
            RequestOptions::JSON => $data
        ]);

        $this->lastStatusCode = $request->getStatusCode();
        return json_decode($request->getBody()->getContents(), true);
    }

    function removeEmailsFromMailing($data, $mailingId)
    {
        $data['secret'] = $this->token->secret;
        $data['action'] = 'remove';
        $request = $this->client->patch("mailings/{$this->token->uuid}/{$mailingId}/emails", [
            RequestOptions::JSON => $data
        ]);

        $this->lastStatusCode = $request->getStatusCode();
        return json_decode($request->getBody()->getContents(), true);
    }

    function startMailing($id)
    {
        $request = $this->client->post("mailings/{$this->token->uuid}/$id/start", [
            RequestOptions::JSON => [
                'secret' => $this->token->secret,
            ]
        ]);

        $this->lastStatusCode = $request->getStatusCode();
        return json_decode($request->getBody()->getContents(), true);
    }
}
