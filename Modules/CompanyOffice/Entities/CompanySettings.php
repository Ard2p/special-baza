<?php

namespace Modules\CompanyOffice\Entities;


use App\Overrides\Model;
use Stevebauman\Purify\Facades\Purify;

class CompanySettings extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'company_id';

    protected $fillable = [
        'indexing',
        'catalog_seo_text',
        'about_page_content',
        'contact_address',
        'contact_phone',
        'indexing',
        'contact_email'
    ];

    protected $casts = ['indexing' => 'boolean'];

    function setContactPhoneAttribute($val)
    {
        $this->attributes['contact_phone'] = trimPhone($val);
    }

    function setCatalogSeoTextAttribute($val)
    {
        $this->attributes['catalog_seo_text'] = Purify::clean($val);
    }

    function setAboutPageContentAttribute($val)
    {
        $this->attributes['about_page_content'] = Purify::clean($val);
    }

    function company()
    {
        return $this->belongsTo(Company::class);
    }

}
