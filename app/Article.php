<?php

namespace App;

use App\Content\StaticContent;
use App\Support\ArticleLocale;
use App\Support\FederalDistrict;
use App\User\SendingSubscribe;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use App\Overrides\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Modules\AdminOffice\Entities\Content\ArticleGallery;
use Modules\RestApi\Entities\Content\Tag;
use Modules\RestApi\Entities\Domain;

class Article extends Model
{

    const STATIC_PAGES = [
        'about',
        'for-customer',
        'for-contractor',
        'for-partner',
        'contacts',
    ];

    protected $fillable = [
        'title',
        'alias',
        'keywords',
        'description',
        'h1',
        'image_alt',
        'is_publish',
        'content',
        'image',
        'user_id',
        'is_news',
        'is_article',
        'is_static',
        'type',
        'domain_id',
    ];


    private $setLocale = false;

    protected $with = ['galleries'];

    protected $appends = ['thumbnail_image', 'image_url'];


    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (app()->getLocale() !== config('app.fallback_locale')) {
            $this->with[] = 'locale';
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($model) {
            $model->generateSeoPhoto();
        });

        static::created(function ($model) {
            $model->generateSeoPhoto();
        });

        static::deleted(function ($model) {

            $model->deletePhotos();
        });
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date instanceof \DateTimeImmutable ?
            CarbonImmutable::instance($date)->toDateTimeString() :
            Carbon::instance($date)->toDateTimeString();
    }

    /**
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'alias';
    }

    function subMenuArticles()
    {
        return $this->belongsToMany(StaticContent::class, 'menu_article');
    }

    function getPreviewTitleAttribute()
    {

        return mb_strlen($this->title) > 120 ? mb_substr($this->title, 0, 120) . '...' : $this->title;
    }

    function scopeWhereAlias($q, $alias)
    {
        return $q->where('alias', $alias);
    }

    function scopeActive($q)
    {
        return $q->where('is_publish', 1);
    }

    static function generateChpu($str)
    {
        $converter = [
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        ];
        $str = strtr($str, $converter);
        $str = strtolower($str);
        $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
        $str = trim(trim($str, "-"));
        return $str;
    }

    function locale()
    {
        return $this->hasMany(ArticleLocale::class);
    }

    function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    function galleries()
    {
        return $this->hasMany(ArticleGallery::class);
    }

    function scopeForDomain($q, $domain = null)
    {
        return $domain ? $q->where(function ($q) use ($domain) {
            return $q->whereHas('domain', function ($q) use ($domain) {
                return is_array($domain)
                    ? $q->whereIn('alias', $domain)
                    : $q->whereAlias($domain);
            })
                ->orWhereNull('domain_id');
        }) : $q;
    }

    function scopeContentType($q, $type = null)
    {

        if ($type) {
            $q->where('type', $type);

        } else {
            $q->whereIn('type', ['news', 'notes']);
        }
    }

    function scopeForDistrict($q, $city_id = null)
    {
        if ($city_id) {
            return $q->whereHas('federal_districts', function ($q) use ($city_id) {
                $q->whereHas('region', function ($q) use ($city_id) {
                    $q->whereHas('city', function ($q) use ($city_id) {
                        $q->where('city_id', $city_id);
                    });
                });
            })->orWhereDoesntHave('federal_districts');
        }
        return $q;
    }

    function getContentAttribute($content)
    {

        if (request()->filled('un_subscribe_sending_id')) {
            $send = SendingSubscribe::whereHash(request()->input('hash'))->find(request()->input('un_subscribe_sending_id'));

            if ($send) {
                $subscribe = $send->template->subscribe()->where('can_unsubscribe', 1)->first();
                $user = $send->user;
                if ($subscribe) {
                    $subscribe->unsubscribes()->syncWithoutDetaching([$user->id]);

                    $content = str_replace('[unsubscribe]', $subscribe->name, $content);
                } else {
                    $content = '';
                }

            }
        }
        return $content;
    }

    function localization()
    {
        if (!\App::isLocale('ru') && !$this->setLocale) {
            $en = $this->locale->where('locale', \App::getLocale())->first();
            if ($en) {
                $this->title = $en->title;
                $this->keywords = $en->keywords;
                $this->description = $en->description;
                $this->h1 = $en->h1;
                $this->image_alt = $en->image_alt;
                $this->content = $en->content;

                $this->setLocale = true;
            }
        }

        return $this;
    }

    function getIsStaticAttribute($val)
    {

        $this->localization();
        return $val;
    }

    function getIsNewsAttribute($val)
    {
        $this->localization();
        return $val;
    }

    function getAliasAttribute($val)
    {
        $this->localization();
        return $val;
    }

    function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    function generateSeoPhoto()
    {
        $new_name = "images/articles/{$this->alias}_{$this->id}";
        $updating = false;

        $piece = explode('.', $this->image);
        $ext = array_pop($piece);

        $current = "{$new_name}.{$ext}";


        if ($this->image !== $current) {

            $exists = Storage::disk()->exists($this->image);
            $exists2 = Storage::disk()->exists($current);

            if ($exists) {
                if ($exists2) {
                    Storage::disk()->delete($current);
                }
                $updating = true;
                Storage::disk()->move($this->image, $current);

                $this->generateThumbnail($current);
            }
        }

        if ($updating) {
            $this->update([
                'image' => $current
            ]);
        }


    }

    function federal_districts()
    {
        return $this->belongsToMany(FederalDistrict::class, 'articles_federal_districts');
    }

    function generateThumbnail($new = null)
    {
        $current = $new ?: $this->image;
        $thumb = str_replace('images/', 'thumbnail/', $current);
        if (Storage::disk()->exists($thumb)) {
            Storage::disk()->delete($thumb);
        }
        if (Storage::disk()->exists($current)) {

          // $image = Image::make(Storage::disk()->url($current))->widen(350,  function ($constraint) {
          //   //  $constraint->upsize();
          //     $constraint->aspectRatio();
          // });
            Storage::disk()->put($thumb, file_get_contents(Storage::disk()->url($current)));

        }


        return $this;
    }

    function deletePhotos()
    {

        $exists = Storage::disk()->exists($this->image);

        if ($exists) {
            Storage::disk()->delete($this->image);
        }

    }

 
    function getThumbnailImageAttribute()
    {
        return $this->image ? Storage::disk()->url(str_replace('images/', 'thumbnail/', $this->image)) : url('img/no_product.png');
    }

    function getImageUrlAttribute()
    {
        return $this->image ?  Storage::disk()->url($this->image) : url('img/no_product.png');
    }
}
