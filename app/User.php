<?php

namespace App;

use App\Ads\Advert;
use App\Ads\AdvertOffer;
use App\Directories\NotificationName;
use App\Finance\FinanceTransaction;
use App\Finance\TinkoffPayment;
use App\Helpers\RequestHelper;
use App\Jobs\AvitoNotificaion;
use App\Jobs\SendSmsNotification;
use App\Machines\Type;
use App\Marketing\FriendsList;
use App\Notifications\EmailConfirm;
use App\Notifications\ResetPassword;

use App\Service\EventNotifications;
use App\Service\Subscription;
use App\Service\TBC;
use App\Service\Widget;
use App\Support\Country;
use App\Support\Document;
use App\Support\Region;
use App\Support\SmsNotification;
use App\Support\Ticket;
use App\Support\TicketMessage;
use App\System\SpamEmail;
use App\User\Adverts\Entities\Moderation;
use App\User\Auth\SocialFacebookAccount;
use App\User\Auth\SocialVkontakteAccount;
use App\User\Balance;
use App\User\Commission;
use App\User\Contractor\ContractorService;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use App\User\NotificationHistory;
use App\User\PhoneConfirm;
use App\User\Subscribe;
use App\User\UserConfirm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use Modules\AdminOffice\Emails\DispatcherConection;
use Modules\AdminOffice\Entities\RpContact;
use Modules\AdminOffice\Entities\User\Note;
use Modules\AdminOffice\Entities\YandexPhoneCredential;
use Modules\AdminOffice\Services\RoleService;
use Modules\CompanyOffice\Entities\Company;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\CorpCustomer\Entities\CorpBrand;
use Modules\CorpCustomer\Entities\CorpCompany;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\FMSAPI\Entities\FmsApi;
use Modules\FMSAPI\Entities\FmsRestApi;
use Modules\Integrations\Entities\Amo\AmoAuthToken;
use Modules\Integrations\Entities\Integration;
use Modules\Integrations\Entities\Mails\MailConnector;
use Modules\Integrations\Entities\Wialon;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payment;
use Modules\Profiles\Entities\UserNotification;
use Modules\RestApi\Entities\Domain;
use OwenIt\Auditing\Auditable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 * @package App
 */
class User extends Authenticatable implements \OwenIt\Auditing\Contracts\Auditable
{


    use Auditable, Notifiable, SoftDeletes, HasApiTokens, HasRoles, HasContacts;
    /**
     * @var array
     */
    protected $auditInclude = [
        'name', 'email', 'password', 'phone', 'account_type', 'customer_requisite_type',
        'email_confirm', 'phone_confirm', 'active', 'native_region_id', 'native_city_id',
        'is_freeze', 'freeze_date', 'is_blocked', 'regional_representative_id',
        'contractor_alias_enable', 'contractor_alias', 'contact_person', 'passed_moderation'
    ];

    /**
     *
     */
    const REQUISITE_TYPE = [
        'entity',
        'individual',
    ];

    /**
     *
     */
    const API_MAP = [
        'id', 'email', 'phone', 'vehicles'
    ];

    /**
     *
     */
    const ACCOUNT_ROLES = [
        'customer',
        'contractor',
        'widget',
    ];

    /**
     *
     */
    const REQUISITE_TYPE_LNG = [
        'Юридическое лицо',
        'Физическое лицо',
    ];

    /**
     *
     */
    const GENDER = [
        'm',
        'w',
    ];
    /**
     *
     */
    const GENDER_LNG = [
        'Мужской',
        'Женский',
    ];


    /**
     * @return array
     */
    function getUpdateFields($request)
    {
        $id = Auth::user()->id;

        $rules = [
            'phone' => 'required|numeric|digits:' .RequestHelper::requestDomain()->options['phone_digits'] . '|unique:users,phone,' . $id,
            'region' => 'required|integer',
            'city_id' => 'required|integer',
        ];
        if (Auth::user()->isContractor()) {
            if ($request->filled('contractor_alias_enable')) {
                $rules = array_merge($rules, ['contractor_alias' => 'required|string|min:4|unique:users,contractor_alias,' . $id]);
            }
        }
        return $rules;

    }


    /**
     *
     */
    const UPDATE_PASSWORD = [

        'password' => 'required|string|min:6|confirmed',
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'account_type', 'customer_requisite_type',
        'email_confirm', 'phone_confirm', 'active', 'native_region_id', 'native_city_id',
        'is_freeze', 'freeze_date', 'is_blocked', 'regional_representative_id', 'last_activity',
        'contractor_alias_enable', 'contractor_alias', 'country_id', 'is_regional_representative', 'contact_person', 'passed_moderation', 'order_management',
        'sms_notify'
    ];

    protected $casts = [
        'email_confirm' => 'boolean',
        'passed_moderation' => 'boolean',
        'phone_confirm' => 'boolean',
        'order_management' => 'boolean',
        'is_blocked' => 'boolean',
        'sms_notify' => 'boolean',
    ];

    /**
     * @var array
     */
    protected $appends = ['avatar', 'id_with_email', /*'region_name', 'city_name',*/ 'name', 'public_page'];

    /**
     * @var array
     */
    //protected $with = ['region', 'city', 'roles'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * @var array
     */
    protected $dates = ['freeze_date', 'last_activity'];


    /**
     *
     */
    protected static function boot()
    {
        parent::boot();

        self::created(function (self $model) {
            Balance::insert(
                [
                    [
                        'user_id' => $model->id,
                        'type' => Balance::type('customer'),
                    ],
                    [
                        'user_id' => $model->id,
                        'type' => Balance::type('contractor'),
                    ],
                    [
                        'user_id' => $model->id,
                        'type' => Balance::type('widget'),
                    ],
                    [
                        'user_id' => $model->id,
                        'type' => Balance::type('tbc'),
                    ],
                ]
            );

            $model->update([
                'contractor_alias' => "contractor{$model->id}"
            ]);



           $model->sendToken();


            (new EventNotifications())->newUser($model);


            if (!$model->commission) {
                Commission::create([
                    'user_id' => $model->id
                ]);
            }

            /* if ($options->where('key', 'subscription_users')->first()->value == '1') {
                 //(new Subscription())->newUserNotification($model->refresh());
             }*/
        });
    }


    /**
     * @param $login
     * @return mixed
     */
    function findForPassport($login)
    {
        return $this->where('email', $login)
            ->orWhere('phone', $login)->first();
    }

    /**
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {

        (new Subscription())->resetPassword($this, $token);
        //  $this->notify(new ResetPassword($token));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function adminDomainAccess()
    {
        return $this->belongsToMany(Domain::class, 'user_domain_admin_access');
    }

    /**
     * @return HasOne
     */
    function facebookAccount()
    {
        return $this->hasOne(SocialFacebookAccount::class);
    }

    function mailConnector()
    {
        return $this->morphOne(MailConnector::class, 'owner');
    }

    /**
     * @return HasOne
     */
    function vkAccount()
    {
        return $this->hasOne(SocialVkontakteAccount::class);
    }

    /**
     * @return HasOne
     */
    function rp_contact()
    {
        return $this->hasOne(RpContact::class);
    }

    /**
     * @return HasOne
     */
    function wialonAccount()
    {
        return $this->hasOne(Wialon::class);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function corpBrands()
    {
        return $this->hasMany(CorpBrand::class);
    }


    /**
     * @param $val
     */
    public function setPhoneAttribute($val)
    {
        $this->attributes['phone'] = trimPhone($val);
    }

    /**
     * @param $val
     */
    public function setEmailAttribute($val)
    {

        $this->attributes['email'] = mb_strtolower($val);
    }

    /**
     * @return mixed|string
     */
    public function getAvatarAttribute()
    {
/*        $path = 'avatars/' . $this->attributes['id'] . '/';
        $images = Storage::disk()->allFiles($path);

        if (count($images)) {
            $path = $images[0];
            $path = Storage::disk()->url($path);// str_replace('public/', '/storage/', $path);

        } else {
            $path = '/img/pic_header_user_placeholder.svg';
        }*/
        return '/img/pic_header_user_placeholder.svg';
    }

    /**
     * @return HasOne
     */
    function adminIntegation()
    {
        return $this->hasOne(Integration::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function country()
    {
        return $this->belongsTo(Country::class)->with('machine_masks');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function widgets()
    {
        return $this->hasMany(Widget::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function unsubscribes()
    {
        return $this->belongsToMany(Subscribe::class, 'unsubscribe_user');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function payments()
    {
        return $this->hasMany(Payment::class);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function offers()
    {
        return $this->hasMany(Offer::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function support()
    {
        return $this->hasMany(Ticket::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function region()
    {
        return $this->belongsTo(Region::class, 'native_region_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'native_city_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function machines()
    {
        return $this->hasMany(Machinery::class, 'creator_id');
    }

    function created_branches()
    {
        return $this->hasMany(CompanyBranch::class, 'creator_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function services()
    {
        return $this->hasMany(ContractorService::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function regional_representative()
    {
        return $this->hasOne(User::class, 'id', 'regional_representative_id');
    }

    function corporate_customer()
    {
        return $this->hasOne(\Modules\Dispatcher\Entities\Customer::class, 'corporate_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function regional_contractors()
    {
        return $this->hasMany(User::class, 'regional_representative_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function notification_histories()
    {
        return $this->hasMany(NotificationHistory::class);
    }

    function notifications()
    {
        return $this->hasMany(UserNotification::class, 'user_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function searches()
    {
        return $this->hasMany('App\Search', 'user_id', 'id');
    }





    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function balance_history()
    {
        return $this->hasMany('App\User\BalanceHistory', 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions_history()
    {
        return $this->hasMany(FinanceTransaction::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function individualRequisites()
    {
        return $this->morphMany(IndividualRequisite::class, 'requisite');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function entityRequisites()
    {
        return $this->morphMany(EntityRequisite::class, 'requisite');
    }

    function hasAccessTo($block, $method)
    {
        $permission = $block . ($method ? ".{$method}" : '');

        return $this->hasPermissionTo($permission, 'api') || $this->isSuperAdmin();
    }

    function hasAccessToCompany($company_id) :bool
    {
         return Company::query()->whereHas('branches', function ($q){
             $q->whereHas('employees', function ($q){
                 $q->where('users.id', $this->id);
             });
         })->where('id', $company_id)->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function internationalLegalRequisites()
    {
        return $this->morphMany(InternationalLegalDetails::class, 'requisite');
    }


    /**
     * @return HasOne
     */
    public function customer_balance()
    {
        return $this->hasOne(Balance::class)
            ->where('balances.type', '=', Balance::type('customer'));
    }

    /**
     * @return HasOne
     */
    public function contractor_balance()
    {
        return $this->hasOne(Balance::class)
            ->where('balances.type', '=', Balance::type('contractor'));
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function auto_moderation()
    {
        return $this->belongsToMany(Moderation::class, 'moderation_user');
    }


    /**
     * @return HasOne
     */
    public function widget_balance()
    {
        return $this->hasOne('App\User\Balance', 'user_id', 'id')
            ->where('balances.type', '=', Balance::type('widget'));
    }

    /**
     * @return HasOne
     */
    public function tbc_balance()
    {
        return $this->hasOne('App\User\Balance', 'user_id', 'id')
            ->where('balances.type', '=', Balance::type('tbc'));
    }

    /**
     * @return HasOne
     */
    function balances()
    {
        return $this->hasOne('App\User\Balance', 'user_id', 'id');
    }

    /**
     * @return HasOne
     */
    function ya_call()
    {
        return $this->hasOne(YandexPhoneCredential::class);
    }

    /**
     * @return mixed
     */
    function hasRealBalance()
    {

        return $this->balances()->whereReal(1)->first();
    }

    /**
     * @param $type
     * @return mixed
     */
    function setRealBalance($type)
    {

        switch ($type) {
            case 'customer':
                $model = $this->customer_balance;
                break;
            case 'contractor':
                $model = $this->contractor_balance;
                break;
            case 'widget':
                $model = $this->widget_balance;
                break;
        }

        return $model->update(['real' => 1]);
    }

    /**
     * @param $sum
     * @return $this
     */
    function incrementContractorBalance($sum)
    {
        $this->contractor_balance->increment('balance', $sum);

        return $this;
    }

    /**
     * @param $sum
     * @return $this
     */
    function decrementContractorBalance($sum)
    {
        $this->contractor_balance->decrement('balance', $sum);

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function commission()
    {
        return $this->hasOne(Commission::class, 'user_id', 'id');
    }

    /**
     * @param $sum
     * @return $this
     */
    function incrementCustomerBalance($sum)
    {
        $this->customer_balance->increment('balance', $sum);

        return $this;
    }

    /**
     * @param $sum
     */
    function decrementCustomerBalance($sum)
    {
        $this->customer_balance->decrement('balance', $sum);

        return $this;
    }

    /**
     * @param $sum
     * @return $this
     */
    function incrementWidgetBalance($sum)
    {
        $this->widget_balance->increment('balance', $sum);

        return $this;
    }

    /**
     * @param $sum
     */
    function decrementWidgetBalance($sum)
    {
        $this->widget_balance->decrement('balance', $sum);

        return $this;
    }

    /**
     * @param $sum
     * @return $this
     */
    function incrementTbcBalance($sum, $linkInstance = null, $admin = null, $reason = '')
    {
        $this->tbc_balance->increment('balance', $sum);
        (new TBC())->incrementHistory($this, $sum, $linkInstance, $admin, $reason);
        return $this;
    }

    /**
     * @param $sum
     */
    function decrementTbcBalance($sum, $linkInstance = null, $admin = null, $reason = '')
    {
        $this->tbc_balance->decrement('balance', $sum);
        (new TBC())->decrementHistory($this, $sum, $linkInstance, $admin, $reason);
        return $this;
    }


    /**
     * @return bool
     */
    function isContractor()
    {
        return $this->hasRole('contractor');
    }

    /**
     * @return HasOne
     */
    function spammer()
    {
        return $this->hasOne(SpamEmail::class, 'email', 'email');
    }


    /**
     * @return bool
     */
    function isWidget()
    {
        return $this->hasRole('widget');
    }


    /**
     * @param null $type
     * @return bool
     */
    function getActiveRequisite($type = null)
    {
        if (is_null($type)) {
            $type = ($this->isCustomer())
                ? 'customer'
                : 'contractor';
        }

        switch ($type) {
            case 'customer':
                switch ($this->customer_requisite_type) {
                    case 'entity':
                        $requisite = EntityRequisite::forCustomer()
                            ->forUser($this->id)
                            ->getActive()
                            ->first();
                        break;
                    case 'individual':
                        $requisite = IndividualRequisite::getActive()
                            ->forUser($this->id)
                            ->first();
                        break;
                    default:
                        return false;
                }
                break;
            case 'contractor':
                $requisite = EntityRequisite::forContractor()->forUser($this->id)->getActive()->first();
                break;
            default:
                return false;
        }


        return $requisite;
    }

    /**
     * @param null $account_type
     * @return bool|mixed|string
     */
    function getActiveRequisiteType($account_type = null)
    {
        if (is_null($account_type)) {
            $type = ($this->isCustomer())
                ? 'customer'
                : 'contractor';
        } else {
            $type = $account_type;
        }

        switch ($type) {
            case 'customer':
                return $this->customer_requisite_type;
                break;
            case 'contractor':
                return 'entity';
                break;
            default:
                return false;
        }
    }


    /**
     * @return string
     */
    function getRegionNameAttribute()
    {
        return $this->region->name ?? '';
    }

    /**
     * @return string
     */
    function getNameAttribute()
    {
        return $this->contact_person;
    }

    /**
     * @return string
     */
    function getCityNameAttribute()
    {
        return $this->city->name ?? '';
    }


    /**
     * @param $type
     * @return bool
     */
    function getBalance($type)
    {
        switch ($type) {
            case 'customer':
                return $this->customer_balance->balance;
            case 'contractor':
                return $this->contractor_balance->balance;
            case 'widget':
                return $this->widget_balance->balance;
            case 'tbc':
                return $this->tbc_balance->balance;
        }
        return false;
    }


    /**
     * @param $role
     * @return bool
     */
    function hasModerate($name)
    {
        return $this->auto_moderation()
            ->where('alias', $name)
            ->first();
    }


    /**
     * Заметки пользователя в админке
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function notes()
    {
        return $this->hasMany(Note::class);
    }


    /**
     * @return mixed
     */
    function checkUnreadMessages()
    {
        $m = TicketMessage::where('user_id', $this->id)
            ->supportMessages()
            ->where('is_read', 0)->count();
        return $m;
    }

    /**
     * @return bool
     */
    function isSuperAdmin()
    {
        return $this->hasRole(RoleService::SUPER_ADMIN, 'web');
    }

    /**
     * @return bool
     */
    function isContentAdmin()
    {
        return $this->hasRole('content-admin') || $this->isSuperAdmin();
    }

    /**
     * @return mixed
     */
    function isRegionalRepresentative()
    {
        return $this->hasRole('regional_manager');
    }


    /**
     * @param $key
     * @return false|int|string
     */
    static function requisite_type($key)
    {
        return array_search($key, self::REQUISITE_TYPE);
    }

    /**
     * @param $key
     * @return mixed
     */
    static function requisite_lng($key)
    {
        return self::REQUISITE_TYPE_LNG[$key];
    }

    /**
     * @param $key
     * @return false|int|string
     */
    static function gender_type($key)
    {
        return array_search($key, self::GENDER);
    }

    /**
     * @param $key
     * @return mixed
     */
    static function gender_lng($key)
    {
        return self::GENDER_LNG[$key];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function confirm()
    {
        return $this->hasOne('App\User\UserConfirm', 'email', 'email');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    function phone_confirm_rel()
    {
        return $this->hasOne(PhoneConfirm::class);

    }

    /**
     * @return bool|int
     */
    function sendNewToken()
    {
        if (!$this->email) {
            return false;
        }

        $model = $this->confirm;
        $token = str_random(32);
        if ($model) {
            $diff = $model->updated_at->diffInMinutes(Carbon::now());

            if ($diff < 15) {
                return 15 - $diff;
            }
            $model->update([
                'token' => $token
            ]);
        } else {
            $model = new UserConfirm([
                'email' => $this->email,
                'token' => $token]);

            $this->confirm()->save($model);
        }

        $this->load('confirm');
        (new Subscription())->sendRegisterToken($this);
        // $this->notify(new EmailConfirm($this));

        return true;
    }


    /**
     * @param $phone
     * @return bool|int
     */
    function sendSmsToken($phone)
    {

        $token = rand(1000, 9999);

        $model = PhoneConfirm::whereUserId($this->id)->wherePhone($phone)->first();

        if ($model) {
            $diff = $model->updated_at->diffInMinutes(Carbon::now());

            if ($diff < 5) {
                return 5 - $diff;
            }
            $model->update([
                'token' => $token
            ]);
        } else {
            $model = PhoneConfirm::create([
                'user_id' => $this->id,
                'phone' => $phone,
                'token' => $token,
            ]);
        }

        $this->phone = $phone;
        dispatch(new SendSmsNotification($this, "Код подтверждения TRANS-BAZA.RU : {$token}", false));

        return true;
    }


    /**
     *
     */
    function sendRegisterDetails()
    {
        if (!$this->email) {
            return;
        }
        $check = UserConfirm::where('email', $this->email)->first();

        if ($check) $check->delete();

        $token = str_random(32);

        $model = new UserConfirm([
            'email' => $this->email,
            'token' => $token]);

        $this->confirm()->save($model);
        //$this->notify(new EmailConfirm($this));
        // (new Subscription())->sendRegisterToken($this);

        $password = config()->has('tmp_password') ? config('tmp_password') : null;

        (new Subscription())->newUserFromForm($this, $password);


    }

    /**
     * @return bool
     */
    function sendToken($forWidget = false)
    {
        if (!$this->email) {
            return true;
        }
        $check = UserConfirm::where('email', $this->email)->first();

        if ($check) $check->delete();

        $token = str_random(32);

        $model = new UserConfirm([
            'email' => $this->email,
            'token' => $token]);

        $this->confirm()->save($model);
        //$this->notify(new EmailConfirm($this));
        (new Subscription())->sendRegisterToken($this);
        return true;
    }

    /**
     * @return bool
     */
    function canChangeRequisites()
    {
        return true;
    }


    /**
     * @param $q
     * @return mixed
     */
    function scopeCurrentIntegration($q)
    {
        return $q->whereHas('integrations', function ($q) {

            return $q->where('integrations.parent_id', Auth::user()->id);
        });
    }

    /**
     * @param $q
     * @param null $domain
     * @return mixed
     */
    function scopeForDomain($q, $domain = null)
    {
        $domain = $domain ?: request()->header('domain');

        if (!$domain) {
            return $q;
        }
        return $q->whereHas('country', function ($q) use ($domain) {
            $q->whereHas('domain', function ($q) use ($domain) {
                $q->whereAlias($domain);
            });
        });

    }

    function getDomainAttribute()
    {
        return $this->country->domain;
    }

    /**
     * @return $this
     */
    function freeze()
    {
        $this->update([
            'is_freeze' => 1,
            'freeze_date' => Carbon::now()->format('Y-m-d')
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    function unfreeze()
    {
        $this->update([
            'is_freeze' => 0,
            'freeze_date' => null
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    function block()
    {
        $this->update([
            'is_blocked' => 1
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    function unblock()
    {
        $this->update([
            'is_blocked' => 0
        ]);

        return $this;
    }


    /**
     * @return string
     */
    function getIdWithEmailAttribute()
    {
        return '#' . $this->id . ' ' . $this->email;
    }

    /**
     * @param $text
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    function sendSmsNotification($text, $url = true, $type = null)
    {
        if (!is_null($type) && $this->phone_confirm) {
            $this->addNotificationHistory($type, 'sms');
        }

        return dispatch(new SendSmsNotification($this, $text, $url))->delay(now()->addSeconds(5));

    }


    /**
     * @param $queueInstance
     * @param bool $needConfirm
     * @return bool|mixed
     */
    function sendEmailNotification($queueInstance, $needConfirm = true)
    {


        if ($needConfirm && !$this->email_confirm) {
            return false;
        }

        if ($this->email) {
            return Mail::to($this->email)->queue($queueInstance);
        }

        return false;
    }

    /**
     *
     * @return bool
     */
    function isOnline()
    {
        return $this->last_activity > Carbon::now()->subMinutes(5);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function getRolesForStatsAttribute()
    {
        return $this->roles()->whereIn('alias', ['performer', 'customer', 'widget'])->get();
    }

    /**
     * @return string
     */
    function getPhoneFormatAttribute()
    {
        $phone = $this->phone;
        return "+{$phone[0]} ({$phone[1]}{$phone[2]}{$phone[3]}) {$phone[4]}{$phone[5]}*-**-**";

    }

    /**
     * @return mixed|string
     */
    function getRpNameAttribute()
    {
        return $this->rp_contact ? "{$this->rp_contact->name} ({$this->rp_contact->region})" : $this->id_with_email;
    }


    /**
     * @param $email
     * @param $phone
     * @return mixed
     */
    static function register($email, $phone = null, $password = null, $contact_person = null, $country_id = null)
    {
        $password = $password ?: str_random(6);
        $arr = [
            'email' => $email,
            'contact_person' => $contact_person,
            'account_type' => 'individual',
            'contractor_alias_enable' => 0,
            'password' => Hash::make($password),
        ];

        if($country_id) {
            $arr = array_merge($arr, ['country_id' => $country_id]);
        }
        if ($phone) {
            $arr = array_merge($arr, ['phone' => $phone]);
        }
        $user = self::create($arr);


        return $user;
    }

    /**
     * @param $name
     * @param $type
     * @return $this
     */
    function addNotificationHistory($name, $type)
    {
        NotificationHistory::create([
            'user_id' => $this->id,
            'notification_name_id' => NotificationName::getId($name),
            'type' => $type,
        ]);

        return $this;
    }

    /**
     * @param $phone
     * @return int
     */
    static function trimPhone($phone)
    {
        return (int)str_replace([')', '(', ' ', '+', '-'], '', $phone);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function adverts()
    {
        return $this->hasMany(Advert::class);
    }

    /**
     * @param $advert_id
     * @return $this
     */
    function getAdvertChildren($advert_id)
    {
        $this->advert_children = $this->advertChildren()->with(['advertChildren' => function ($q) use ($advert_id) {
            $q->wherePivot('advert_id', '=', $advert_id);
            //    $q->where('user_id', '!=', $this->id)
            //->get();
        }])->wherePivot('advert_id', '=', $advert_id)->get();
        return $this;
        /*   ['advertChildren' => function ($q) use ($advert_id) {
               $q->wherePivot('advert_id', '=', $advert_id);
               //    $q->where('user_id', '!=', $this->id)
               //->get();
           }]);*/

    }

    function hasVehicles()
    {
        return $this->machines()->exists();
    }

    function isEntity()
    {
        return $this->account_type === 'company';
    }

    function branches()
    {
        return $this->belongsToMany(CompanyBranch::class, 'users_company_branches')->withPivot('role', 'machinery_base_id');
    }

    function getBranchRoles($branch_id = null)
    {
        return $branch_id
            ?  $this->branches->where('id', $branch_id)->pluck('pivot.role')
            : $this->branches->pluck('pivot.role');
    }

    function getCompanyRoles($company_id)
    {
        return $this->branches->where('company_id', $company_id)->pluck('pivot.role');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function advertChildren()
    {
        return $this->allAdvertChildren();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function allAdvertChildren()
    {
        return $this->belongsToMany(self::class, 'advert_agents', 'parent_id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function friends()
    {
        return $this->hasMany(FriendsList::class);
    }

    /**
     * @return mixed
     */
    function getMyFriendsAttribute()
    {
        return self::whereIn('email', $this->friends->pluck('email'))->orWhereIn('phone', $this->friends->pluck('phone'))->get();
    }

    /**
     * @return mixed
     */
    function getMySubmittedFriendsAttribute()
    {
        return self::where(function ($q) {
            $q->whereIn('email', $this->friends->pluck('email'))
                ->orWhereIn('phone', $this->friends->pluck('phone'));
        })
            ->whereHas('friends', function ($q) {
                $q->where('email', $this->email)
                    ->orWhere('phone', $this->phone);
            })
            ->get();
    }


    /**
     * @return bool
     */
    function getDashboardAccessAttribute()
    {
        return $this->hasPermissionTo('admin_dashboard_access', 'api') || $this->isSuperAdmin();
    }

    /**
     * @return mixed
     */
    function getVehiclesAttribute()
    {
        return $this->machines->map(function ($machine) {
            return Machinery::integrationMap($machine);
        });
    }

    function getAddressAttribute()
    {
        $city = $this->city ? $this->city->name : '';
        $region = $this->region ? $this->region->name : '';
        $country = $this->country ? $this->country->name : '';

        return "{$country} {$region} {$city}";
    }

    function orders()
    {
        return $this->hasMany(Order::class, 'creator_id');
    }

    function getContractorOrdersAttribute()
    {
        return Order::query()->contractorOrders($this->id)->get();
    }

    function getContractorOrdersCountAttribute()
    {
        return $this->contractor_orders->count();
    }


    /**
     * @return mixed
     */
    function getFeedbacks()
    {
        return AdvertOffer::where('rate', '!=', 0)->whereHas('advert', function ($q) {
            $q->where('user_id', $this->id);
        })->get();
    }


    /**
     * @return mixed
     */
    function getPublicPageAttribute()
    {
        return str_replace('.api.', '.', route('user_public_page', $this->contractor_alias));
    }


    /**
     * @param $q
     * @param int $val
     * @return mixed
     */
    function scopeRegionalRepresentative($q, $val = 1)
    {
        return $q->where('is_regional_representative', $val);
    }

    /**
     * @param $q
     * @param null $id
     * @return mixed
     */
    function scopeForRegionalRepresentative($q, $id = null)
    {
        if (is_null($id)) {
            $id = Auth::id();
        }
        return $q->where('regional_representative_id', $id);
    }



    function connectDispatcherModule()
    {
        if ($this->addRole('dispatcher')) {

            $this->sendEmailNotification(new DispatcherConection(), false);

            return $this;
        }
        return false;
    }

    function predicted_categories()
    {
        return $this->belongsToMany(Type::class, 'user_predicted_categories', 'user_id', 'category_id')->withPivot('count');
    }


}
