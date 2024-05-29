<?php

namespace App\Service;

use Illuminate\Support\Facades\DB;

class Parser
{


    private $url = "http://www.rakurs-ug.ru";
    public $cats =[], $result = [];

    function __construct($return_url = null, $fail_url = null)
    {

    }

    function parseRakurs()
    {
        $url = "{$this->url}/rent/stroitelnaya-tekhnika/";

        $data = file_get_contents($url);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(($data));
         $cats = [];
        $li = $dom->getElementsByTagName('li');
        $i = 0;
        foreach ($li as $item){
            if($item instanceof \DOMText){
                continue;
            }
            if($item->getAttribute('class') !== 'catalog-section-item-list-item'){
                continue;
            }

            foreach ($item->getElementsByTagName('a') as $link){
              $cats[$i]['title'] = $link->textContent;
              $cats[$i]['href'] = $link->getAttribute('href');

            }
            ++$i;
        }
        $collection = collect($cats);

        $this->cats = $collection;


        return $this;

    }

    private function parsePage($data, $cat)
    {


        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(($data));
        $tr = $dom->getElementsByTagName('tr');

        foreach ($tr as $item){

            if($item instanceof \DOMText){
                continue;
            }
            if($item->getAttribute('class') !== 'catalog-list-item '){
                continue;
            }
            $o =  0;
            foreach ($item->getElementsByTagName('td') as $td){
                if($o === 0){
                    $a = $td->getElementsByTagName('a')->item(0);
                    $title = $a->textContent;
                    $href = $a->getAttribute('href');
                }
                if($o === 2){
                    $cost = strip_tags($td->textContent);
                    break;
                }
                ++$o;

            }


            $details = $this->parsePhones($href);
              $res = [
                  'category' => $cat['title'],
                  'title' => $title,
                  'phone' => $details['phone'],
                  'name' => $details['name'],
                  'city' => $details['city'],
                  'content' => $details['content'],
                  'cost' => $cost ?? ''
              ];
              DB::table('test_table')->insert([
                  [
                      'content' => json_encode($res)
                  ]
              ]);
            $this->result[] = $res;
        }
    }

    private function parseCatPage($cat, $url)
    {

        $data = file_get_contents($url);
        $next = $this->setPagesForCat($data);
        $this->parsePage($data, $cat);
        if($next){
            $this->parseCatPage($cat, $this->url . $next);
        }
/*        foreach ($pages as $page){
            $url = $this->url . $page;

            $data = file_get_contents($url);
            $this->parsePage($data, $cat);
        }*/
    }


    function parseList()
    {
        foreach ($this->cats as $cat){

            $url = $this->url . $cat['href'];

            $this->parseCatPage($cat, $url);

           // break;
        }
         return $this;
    }

    function downloadResult()
    {
        $colection = collect($this->result);
        (new \Rap2hpoutre\FastExcel\FastExcel($colection))->export('result.xlsx', function ($machine) {
            return [
                'Категория' => $machine['category'],
                'Город' => $machine['city'],
                'Наименование' => $machine['title'],
                'Телефон' => $machine['phone'],
                'Владелец' => $machine['name'],
                'Цена' => $machine['cost'],
                'Описание' => $machine['content'],

            ];
        });
    }

    private function setPagesForCat($data)
    {
        $pages = [];
        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(($data));
        $divs = $dom->getElementsByTagName('div');
        foreach ($divs as $div){
            if($div->getAttribute('class') !== 'pager'){
                continue;
            }
            $a =  $div->getElementsByTagName('a');
            foreach ($a as $item){
                if($item->getAttribute('class') !== 'pager-item pager-item--next'){
                    continue;
                }
                return $item->getAttribute('href');
            }
        }
        unset($dom);

        return false;
    }

    function parsePhones($link)
    {
        $url = $this->url . $link;

        $data = file_get_contents($url);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(($data));
        $dt = $dom->getElementsByTagName('dd');

        $phone = '';
        $name = '';
        $i = 0;
        foreach ($dt as $item){
            if($item->getAttribute('class') !== 'hidden product-features-hidden'){
            continue;
            }
             if($i === 0){
                 ++$i;
                 $phone = $item->textContent;
                 continue;

             }
            $name = $item->textContent;
             break;
        }
        $dl = $dom->getElementsByTagName('dl');
        foreach ($dl as $value){
            if($value->getAttribute('class') !== 'product-info-list'){
                continue;
            }
            $k = 0;
            foreach ($value->getElementsByTagName('dd') as $dd){
                if($k === 2){
                    $city = trim($dd->textContent);

                }
                ++$k;
            }
        }

        $page = $dom->getElementById('product-description');
        $content = str_replace("\t", '', strip_tags($page->textContent));
          unset($dom);
        return ['phone' => $phone, 'name' => $name, 'content' => $content, 'city' => $city ?? ''];
    }

    function downLoadCats()
    {
         (new \Rap2hpoutre\FastExcel\FastExcel($this->cats))->download('Cats.xlsx', function ($machine) {
           return [
               'Категория' => $machine['title'],

           ];
       });
    }



    function parse24()
    {
        $machines  = [];
        $i = 0;
        libxml_use_internal_errors(false);
        for ($o = 0; $o <= 1; ++$o){
            if($o == 0){
                $c = file_get_contents('http://www.raise.ru/rent/construction/excavators/');
            } else{
                $c = file_get_contents('http://www.raise.ru/rent/construction/excavators/page_2/');
            }
            $dom = new \DOMDocument('1.0', 'UTF-8');
            @$dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . ($c));
            $uls = $dom->getElementsByTagName('ul');

            echo '<pre>';
            foreach ($uls as $ul) {
                if($ul->getAttribute('class') == 'items'){
                    foreach ($ul->childNodes as $li){

                        if($li instanceof \DOMText){
                            continue;
                        }
                        foreach ($li->getElementsByTagName('div') as $item){

                            if($item->getAttribute('class') === 'box_title'){
                                $val = trim(strip_tags($item->textContent));
                                $machines[$i]['title'] = $val;
                            }
                            if($item->getAttribute('class') === 'preview_text'){
                                $val = trim(strip_tags($item->textContent));
                                $machines[$i]['description'] = $val;
                            }
                            if($item->getAttribute('class') === 'price_item_desc'){
                                $val = trim(strip_tags($item->textContent));
                                $machines[$i]['price'] = $val;
                            }
                            if($item->getAttribute('class') === 'firm_phone'){
                                $a = $item->childNodes->item(1)->childNodes->item(1);
                                $val = trim(strip_tags($a->textContent));
                                $machines[$i]['phone'] = $val;
                            }

                            if($item->getAttribute('class') === 'details'){
                                $val = trim(strip_tags($item->textContent));
                                $machines[$i]['city'] = $val;
                            }

                        }
                        ++$i;
                    }

                }


            }
        }
        // dd($machines);
        $collection = collect($machines);
        (new \Rap2hpoutre\FastExcel\FastExcel($collection))->download('Machinery.xlsx', function ($machine) {
            return [
                'Наименование' => $machine['title'],
                'Описание' => $machine['description'],
                'Цена' => $machine['price'],
                'Телефон' => $machine['phone'],
                'Местонахождения' => $machine['city'],
            ];
        });
    }


}