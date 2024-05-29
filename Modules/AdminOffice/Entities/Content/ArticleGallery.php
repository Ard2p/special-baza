<?php

namespace Modules\AdminOffice\Entities\Content;

use App\Article;
use App\Overrides\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleGallery extends Model
{

    public $timestamps = false;

    static function boot()
    {
        parent::boot();

        self::created(function ($gallery) {

            $gallery->setImages();

        });

        self::updated(function ($gallery) {

            $gallery->setImages();

        });
    }

    protected $fillable = [
        'images',
        'article_id',
    ];

    protected $casts = [
        'images' => 'array'
    ];

    function article()
    {
        return $this->belongsTo(Article::class);
    }

    function setImages()
    {
        $gallery_folder = "images/articles/galleries/{$this->id}";


        $rand = str_random(4);
        $new_name = "{$gallery_folder}/{$rand}";

        $arr = [];
        $updating = false;

        $tmp_path = config('app.upload_tmp_dir');
        foreach ($this->images as $key => $image) {


            if (!Str::contains($image, [
                $tmp_path,
                $gallery_folder
            ])) {
                continue;
            }
            $ext = getFileExtensionFromString($image);

            $arr[$key] = $image;

            $from_tmp = Str::contains($image, [
                $tmp_path,
            ]);

            if ($from_tmp) {
                $current_exists = Storage::disk()->exists($image);

                if($current_exists){
                    $new_name = "{$new_name}_{$key}.{$ext}";
                    Storage::disk()->move($image, $new_name);
                    $arr[$key] = $new_name;

                    $updating = true;
                }
            }
        }
        if ($updating) {
            $this->update([
                'images' => $arr
            ]);
        }

        $files = Storage::disk()->files($gallery_folder);

        foreach ($files as $originalName) {

            $file = $originalName;

            if (!in_array($file, $arr)) {
                Storage::disk()->delete($originalName);
            }
        }
    }

    function remove()
    {
        $gallery_folder = "images/articles/galleries/{$this->id}";

        Storage::disk()->deleteDirectory($gallery_folder);



        return $this->delete();
    }
}
