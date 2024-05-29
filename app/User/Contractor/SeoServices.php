<?php

namespace App\User\Contractor;

use App\City;
use App\Directories\ServiceCategory;
use App\Overrides\Model;

class SeoServices extends Model
{
    protected $fillable = [
        'fields', 'city_id', 'service_category_id',
    ];
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

        foreach ($fields as &$field){
            $phone = (string)$field['phone'];

            $field['phone'] = "+{$phone[0]} ({$phone[1]}{$phone[2]}{$phone[3]}) {$phone[4]}{$phone[5]}{$phone[6]}-{$phone[7]}{$phone[8]}-{$phone[9]}{$phone[10]}";
        }
        return $fields;
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function category()
    {
        return $this->belongsTo(ServiceCategory::class);
    }


}
