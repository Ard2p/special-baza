<?php

namespace App\Jobs;

use App\Support\SeoServiceDirectory;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateSeoServices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $timeout = 3600;
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      //  $all = SeoServiceDirectory::all()->pluck('id');
        foreach (\App\City::all() as $city) {
            foreach (\App\Directories\ServiceCategory::all() as $category) {
                $content = \App\Support\SeoServiceDirectory::where('service_category_id', $category->id)
                    ->whereCityId($city->id)
                   // ->whereNotIn('id', $all)
                    ->first();
                if (!$content) {
                    $data = [];
                    $codes = [
                        903, 905, 906, 909, 951, 953, 960, 961, 962, 963, 964, 965, 966, 967, 968, 910, 911, 912, 913, 914, 915,
                        916, 917, 918, 919, 980, 981, 982, 983, 984, 985, 987, 988, 989, 920, 921, 922, 923, 924, 925, 926, 927,
                        928, 929, 900, 901, 902, 904, 908, 950, 951, 952, 953, 958, 977, 991, 992, 993, 994, 995, 996, 999, 999,
                    ];

                    $i = rand(2, 5);

                    for ($a = 0; $a < $i; $a++) {
                        $code = $codes[array_rand($codes)];
                        $str = '';
                        for ($y = 0; $y < 7; $y++) {
                            $str .= rand(1, 9);
                        }
                        $data[$a]['phone'] = "7{$code}{$str}";
                        $data[$a]['name'] = \App\Support\SeoServiceDirectory::$names[array_rand(\App\Support\SeoServiceDirectory::$names)];
                    }
                    $content = \App\Support\SeoServiceDirectory::create([
                        'fields' => json_encode($data),
                        'city_id' => $city->id,
                        'service_category_id' => $category->id,
                    ]);
                }

            }
        }
    }
}
