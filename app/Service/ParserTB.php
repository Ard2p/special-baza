<?php

namespace App\Service;

use Illuminate\Support\Facades\DB;

class ParserTB
{


    private $url = "http://transbaza.com";
    public $cats =[], $result = [];

    function __construct($return_url = null, $fail_url = null)
    {

    }

    function parseRakurs()
    {
        $url = "{$this->url}/phonebook_uslugi";

        $data = file_get_contents($url);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(($data));
         $cats = [];
         $div = $dom->getElementById('table-special');
        $li = $div->getElementsByTagName('li');
        $i = 0;
        foreach ($li as $item){
            if($item instanceof \DOMText){
                continue;
            }
            foreach ($item->getElementsByTagName('a') as $link){
             // $cats[$i]['title'] = $link->textContent;
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

            foreach ($item->getElementsByTagName('td') as $td){

                    $a = $td->getElementsByTagName('a')->item(0);
                    if($a){
                        $href = $a->getAttribute('href');

                        $this->parseCatPage('', $href);
                    }

                }
            }

    }

    private function parseCatPage($cat, $url)
    {

        $data = file_get_contents($url);
        $this->parsePage($data, $cat);

    }


    function parseList()
    {
        foreach ($this->cats as $cat){

            $url = $cat['href'];

            $this->parseCatPage($cat, $url);

           // break;
        }
         return $this;
    }


}