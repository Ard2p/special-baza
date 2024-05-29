<?php

namespace App\Support;

use App\City;
use App\Directories\ServiceCategory;
use App\Machines\Type;
use App\Overrides\Model;

class SeoServiceDirectory extends Model
{

    protected $fillable = [
        'fields', 'city_id', 'service_category_id',
    ];
    protected $with = ['city', 'type'];
    static $names = [
        'Александр',
        'Алексей',
        'Анатолий',
        'Андрей',
        'Антон',
        'Аркадий',
        'Артём',
        'Артур',
        'Богдан',
        'Борис',
        'Вадим',
        'Валентин',
        'Валерий',
        'Василий',
        'Виктор',
        'Виталий',
        'Владислав',
        'Вячеслав',
        'Глеб',
        'Григорий',
        'Денис',
        'Дмитрий',
        'Евгений',
        'Егор',
        'Иван',
        'Игорь',
        'Илья',
        'Кирилл',
        'Константин',
        'Кузьма',
        'Леонид',
        'Максим',
        'Матвей',
        'Михаил',
        'Назар',
        'Никита',
        'Николай',
        'Олег',
        'Павел',
        'Пётр',
        'Родион',
        'Ростислав',
        'Руслан',
        'Святослав',
        'Семён',
        'Сергей',
        'Станислав',
        'Степан',
        'Тарас',
        'Тимофей',
        'Тимур',
        'Фёдор',
        'Эдуард',
        'Юрий',
        'Ярослав',
    ];

    function getContentAttribute()
    {
        $fields = json_decode($this->fields, true);
        if(!$fields){
            $data = $this->generateData();
            $this->fields = json_encode($data);
            $this->save();
            $fields = $data;
        }
        foreach ($fields as &$field){
            $phone = (string)$field['phone'];

            $field['phone'] = "+{$phone[0]} ({$phone[1]}{$phone[2]}{$phone[3]}) {$phone[4]}{$phone[5]}{$phone[6]}-{$phone[7]}{$phone[8]}-{$phone[9]}{$phone[10]}";
        }
        return $fields;
    }

    function getEditableContentAttribute()
    {
        return json_decode($this->fields, true);
    }

    function city()
    {
        return $this->belongsTo(City::class)->with('region');
    }

    function getRegionNameAttribute()
    {
        return $this->city->region->name ?? '';
    }

    function type()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    function generateData()
    {
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
            $data[$a]['name'] = self::$names[array_rand(self::$names)];
        }

        return $data;
    }
}