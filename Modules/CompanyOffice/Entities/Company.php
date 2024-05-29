<?php

namespace Modules\CompanyOffice\Entities;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\CompanyBranchSettings;
use Modules\CompanyOffice\Entities\Directories\SlangCategory;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Entities\Beeline\BeelineTelephony;
use Modules\Integrations\Entities\MegafonTelephony\MegafonAccount;
use Modules\Integrations\Entities\Uis\UisTelephony;
use Modules\RestApi\Entities\Domain;

class Company extends Model
{
    protected $fillable = [
        'name',
        'alias',
        'domain_id',
        'options',
        'creator_id'
    ];

  //  protected $with = ['branches'];

    protected $casts = [
        'options' => 'object'
    ];

    const AVAILABLE_STYLE_KEYS = [
        'color',
        'text_color',
        'logo',
        'title',
        'index_header'
    ];

    protected static function boot()
    {
        parent::boot();

        self::created(function (self $company) {
            $company->update([
                'name' => $company->name !== 'company' ? $company->name : "Компания #{$company->id}",
                //'alias' => "company{$company->id}",
                'options' => $company->getDefaultOptions()
            ]);
        });

        self::deleted(function (self $company){
            if($company->megafonTelephony) {
                $company->megafonTelephony->calls_history()->delete();

            }
            if($company->uisTelephony) {
                $company->uisTelephony->calls_history()->delete();

            }
        });
    }

    function setAliasAttribute($val)
    {
        $this->attributes['alias'] = generateChpu($val);
    }

    static function getStyleRules()
    {
        return [
            'style.title' => 'required|string|max:20',
            'style.index_header' => 'required|string|max:120',
        ];
    }

    function getDefaultOptions()
    {
        return [
            'style' => [
                'color' => '#333',
                'text_color' => '#fff',
                'logo' => null,
                'title' =>  $this->name !== 'company' ? $this->name : "Компания #{$this->id}",
                'index_header' => null,
            ]
        ];
    }

    function user()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    function branches()
    {
        return $this->hasMany(CompanyBranch::class);
    }

    function scopeForDomain($q, $domain = null)
    {
        $domain = $domain ?: request()->header('domain');

        return $q->whereHas('domain', function ($q) use ($domain) {
            $q->whereAlias($domain);
        });
    }

    function scopeUserHasAccess(Builder $q, $user_id = null, $role = CompanyRoles::ROLE_ADMINISTRATOR)
    {
        $user_id = $user_id ?: Auth::id(); 

        return $q->whereHas('branches', function ($q) use ($role, $user_id){
           $q->userHasAccess($user_id, $role);
        });
    }

    function megafonTelephony()
    {
        return $this->hasOne(MegafonAccount::class);
    }

    function uisTelephony()
    {
        return $this->hasOne(UisTelephony::class);
    }

    function beelineTelephony()
    {
        return $this->hasOne(BeelineTelephony::class);
    }


    function updateStyle($data)
    {
        foreach ($data as $key => $value)
        {
            if(!in_array($key, self::AVAILABLE_STYLE_KEYS)) {
                unset($data[$key]);
            }
        }

        $options = $this->options;
        $options->style = $data;

        $this->update([
            'options' => $options
        ]);

        return $this;
    }

    function updateSettings($data, $adminUpdate = false)
    {
        $fields = [
            'catalog_seo_text' => $data['catalog_seo_text'] ?? '',
            'about_page_content' => $data['about_page_content']?? '',
            'contact_address' => $data['contact_address']?? '',
            'contact_phone' => $data['contact_phone']?? '',
            'contact_email' => $data['contact_email']?? '',

        ];
        $fields =  $adminUpdate ? array_merge($fields, [
            'indexing' =>  toBool($data['indexing'] ?? false),
        ]) : $data;
        $this->settings->update($fields);

        return $this;
    }

    function settings()
    {
        return $this->hasOne(CompanySettings::class);
    }
    function getSettings()
    {
        return $this->settings ?: $this->settings()->save(new CompanySettings());
    }

    function getUrl($path = '', $params = [])
    {
        $domain = $this->domain;

        $path = $path ? '/' . trim($path, '/') : '';

        $params_string = http_build_query($params);

        $path = "https://{$this->alias}.{$domain->pure_url}" . $path;

        $path = $params_string ? "{$path}?{$params_string}" : $path;

        return $path;
    }


    function slangCategories()
    {
        return $this->hasMany(SlangCategory::class);
    }


}
