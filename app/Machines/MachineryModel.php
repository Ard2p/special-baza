<?php

namespace App\Machines;

use App\Machinery;
use App\Overrides\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\PartsWarehouse\Entities\Warehouse\Part;
use Modules\PartsWarehouse\Entities\Warehouse\PartMachineryModelPivot;

/**
 * Class MachineryModel
 * @package App\Machines
 */
class MachineryModel extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'category_id',
        'brand_id',
        'image',
        'name',
        'alias',
        'description',
        'market_price',
        'rent_cost',
        'insurance_cost',
        'insurance_without_collateral',
        'insurance_service',
        'insurance_overdue',
        'images',
    ];

    protected $casts = [
        'images' => 'array'
    ];

    /**
     * @var array
     */
    protected $appends = ['can_delete'];

    /**
     *
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($model) {
            $model->generateSeoPhoto();
        });

        static::created(function ($model) {
            $model->generateSeoPhoto();
        });

        self::deleted(function ($model) {

            if ($model->image && Str::contains($model->image, 'category_models/')) {
                $exists = Storage::disk()->exists($model->image);
                if ($exists) {
                    Storage::disk()->delete($model->image);
                }
            }

        });;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function category()
    {
        return $this->belongsTo(Type::class, 'category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function brand()
    {
        return $this->belongsTo(Brand::class);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    function characteristics()
    {
        return $this->belongsToMany(OptionalAttribute::class, 'machinery_models_attributes')->withPivot('value')->orderBy('priority');
    }

    /**
     * @param $attributes
     * @return $this
     */
    function setCharacteristics($attributes)
    {
        $this->characteristics()->sync(
            $this->prepareOptionalAttributes($attributes, $this->category)
        );

        return $this;
    }

    /**
     * @param $attributes
     * @param Type $category
     * @return array
     */
    private function prepareOptionalAttributes($attributes, Type $category)
    {
        $arr = [];
        foreach ($attributes as $id => $attribute) {
            if (!$attribute || !$category->optional_attributes()->find($id)) {
                continue;
            }
            $arr[$id] = ['value' => $attribute];
        }
        return $arr;
    }

    function machines()
    {
        return $this->hasMany(Machinery::class, 'model_id');
    }

    /**
     * @return bool
     */
    function getCanDeleteAttribute()
    {
        return !$this->machines()->exists();
    }

    /**
     *
     */
    function generateSeoPhoto()
    {
        $this->moveImages();

        if (!$this->image) {
            return;
        }
        $new_name = "images/category_models/{$this->alias}_{$this->id}";
        $updating = false;
        $ext = getFileExtensionFromString($this->image);

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
            }
        }

        if ($updating) {
            $this->update([
                'image' => $current
            ]);
        }


    }

    function moveImages()
    {
        $tmp_path = 'uploads/images';
        $models_folder = "images/category_models/{$this->id}/ext";

        $update = false;
        $img = $this->images ?: [];
        foreach ($img as $key => $scan) {

            $exist = Storage::disk()->exists($scan);

            if (!Str::contains($scan, [$tmp_path])) {
                continue;
            }

            $ext = getFileExtensionFromString($scan);
            $current = "img_{$key}.{$ext}";
            $new_name = "{$models_folder}/{$current}";


            if ($exist && $scan !== $new_name) {
                Storage::disk()->move($scan, $new_name);
                $img[$key] = $new_name;
                $update = true;
            }
        }

        if ($update) {
            $this->update(['images' => $img]);
        }

        $files = Storage::disk()->files($models_folder);

        foreach ($files as $originalName) {

            $file = $originalName;

            if (!in_array($file, $img)) {
                Storage::disk()->delete($originalName);
            }
        }
    }

    function parts()
    {
        return $this->belongsToMany(Part::class, 'warehouse_parts_machinery_models');
    }
}
