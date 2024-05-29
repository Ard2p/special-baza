<?php

use App\Helpers\RequestHelper;
use App\Service\RedirectLink;
use App\Service\RequestBranch;
use Illuminate\Support\Str;

function request_domain($type = 1){
    return  app()->make(RequestBranch::class)->getDomain();
}

function request_branch(){
    return app()->make(RequestBranch::class)->companyBranch;
}

function stripParamFromUrl( $url, $param )
{
    $base_url = strtok($url, '?');              // Get the base URL
    $parsed_url = parse_url($url);              // Parse it
    $query = $parsed_url['query'];              // Get the query string
    parse_str( $query, $parameters );           // Convert Parameters into array
    unset( $parameters[$param] );               // Delete the one you want
    $new_query = http_build_query($parameters); // Rebuilt query string
    return $base_url.'?'.$new_query;            // Finally URL is ready
}


function humanFilesize($size, $precision = 2)
{
    $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $step = 1024;
    $i = 0;

    while (($size / $step) > 0.9) {
        $size = $size / $step;
        $i++;
    }

    return round($size, $precision) . $units[$i];
}

function xml_escape($s)
{
    $s = html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $s = htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8', false);
    return $s;
}

function humanSumFormat($sum, $dec = 0)
{
    return number_format($sum / 100, $dec, ',', ' ');
}

function isBinary($str)
{
    return preg_match('~[^\x20-\x7E\t\r\n]~', $str) > 0;
}

function sumToPenny($sum)
{
    return round(str_replace(',', '.', $sum) * 100);
}

function trimPhone($phone)
{

    return preg_replace('/[^0-9]/', '', $phone);
}

function is_valid_inn($inn)
{
    if (preg_match('/\D/', $inn)) return false;

    $inn = (string)$inn;
    $len = strlen($inn);

    if ($len === 10) {
        return $inn[9] === (string)(((
                        2 * $inn[0] + 4 * $inn[1] + 10 * $inn[2] +
                        3 * $inn[3] + 5 * $inn[4] + 9 * $inn[5] +
                        4 * $inn[6] + 6 * $inn[7] + 8 * $inn[8]
                    ) % 11) % 10);
    } elseif ($len === 12) {
        $num10 = (string)(((
                    7 * $inn[0] + 2 * $inn[1] + 4 * $inn[2] +
                    10 * $inn[3] + 3 * $inn[4] + 5 * $inn[5] +
                    9 * $inn[6] + 4 * $inn[7] + 6 * $inn[8] +
                    8 * $inn[9]
                ) % 11) % 10);

        $num11 = (string)(((
                    3 * $inn[0] + 7 * $inn[1] + 2 * $inn[2] +
                    4 * $inn[3] + 10 * $inn[4] + 3 * $inn[5] +
                    5 * $inn[6] + 9 * $inn[7] + 4 * $inn[8] +
                    6 * $inn[9] + 8 * $inn[10]
                ) % 11) % 10);

        return $inn[11] === $num11 && $inn[10] === $num10;
    }

    return false;
}

function valid_ogrn($ogrn)
{
    $ogrn = trim($ogrn);
    //13ти значный код
    if (preg_match('#([\d]{13})#', $ogrn, $m)) {
        $code1 = substr($m[1], 0, 12);
        $code2 = floor($code1 / 11) * 11;
        $code = ($code1 - $code2) % 10;
        if ($code == $m[1][12]) return $m[1];
    }
    return false;
}

function multi_implode($array, $glue) {
    $ret = '';
    if(!is_array($array)) {
        return $ret;
    }
    foreach ($array as $item) {
        if (is_array($item)) {
            $ret .= multi_implode($item, $glue) . $glue;
        } else {
            $ret .= $item . $glue;
        }
    }

    $ret = substr($ret, 0, 0-strlen($glue));

    return $ret;
}

function generateChpu($str)
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

function checkFixPPC6700($orig)
{

    //get the file contents
    $data = $orig;

    //if its a PPC 6700 image cut out the extraneous 16 bits
    if (strstr($data, "\x41\x70\x61\x63\x68\x65\x00\x48")) {
//this next line can all be one string I split it up so the form on php.net would accept it
        $bad_data = "\x00\x10\x4A\x46" . "\x49\x46\x00\x01\x01" . "\x00\x00\x01\x00\x01\x00\x00";
        return substr_replace($data, "", strpos($data, $bad_data),
            strlen($bad_data));
    } else {
        //if not from a PPC 6700 return data unaltered
        return $data;
    }

}


function trimLicencePlate($number)
{
    return mb_strtolower(trim(str_replace(' ', '', $number)));
}


function getIntegrations()
{
    return auth()->user()->integrations;
}


function getDbCoordinates($value)
{
    if (!$value) {
        return null;
    }
    if (!isBinary($value)) {
        $response = explode(
            ' ', str_replace(["GeomFromText('", "'", 'POINT(', ')'], '', $value)
        );
    }
    if (isBinary($value)) {
        $response = unpack('x/x/x/x/corder/Ltype/dlat/dlon', $value);
        unset($response['order'], $response['type']);
    }

    return implode(',', $response);
}


function b64img($str, $fs = 7, $w = 200, $h = 20, $b = array('r' => 255, 'g' => 255, 'b' => 255), $t = array('r' => 0, 'g' => 0, 'b' => 0))
{
    $tmp = tempnam(sys_get_temp_dir(), 'img');

    /*$image = imagecreate( $w, $h );
    $bck = imagecolorallocate( $image, $b['r'], $b['g'], $b['b'] );
    $txt = imagecolorallocate( $image, $t['r'], $t['g'], $t['b'] );

    $fs =  imageloadfont(public_path('fonts/Roboto-Regular.ttf'));
     imagestring( $image, 2, 0, 0, $str, $txt );
    */
    $image = imagecreatetruecolor(120, 24);
    $white = imagecolorallocate($image, 255, 255, 255);
    $grey = imagecolorallocate($image, 128, 128, 128);
    $black = imagecolorallocate($image, 243, 113, 83);
    imagefilledrectangle($image, 0, 0, 399, 29, $white);

    /* rgb(243, 113, 83);*/

    imagettftext($image, 10, 0, 0, 21, $black, public_path('fonts/Roboto-Regular.ttf'), $str);

    imagepng($image, $tmp);
    imagedestroy($image);

    $data = base64_encode(file_get_contents($tmp));
    @unlink($tmp);
    return $data;
}

function origin($path, $params = [], $domain = null)
{

    if (!$domain) {
        $domain = RequestHelper::requestDomain();
    }
    $domain = $domain->url;

    $origin = "https://{$domain}/" . trim($path, '/');
    $q = http_build_query($params);

    return $origin . '?' . $q;
}

function generateLink($link)
{
    generate:
    $hash = str_random(4);
    $check = RedirectLink::whereHash($hash)->first();
    if ($check) {
        goto generate;
    }
    RedirectLink::create([
        'hash' => $hash,
        'link' => $link
    ]);

    return $link;
}


function numberToPenny($val)
{
    if(empty($val)){
        $val = 0;
    }
    if(!is_string($val)){
        $val = "$val";
    }
    return round(trim(str_replace(',', '.', $val)) * 100);
}

function mb_ucfirst($string)
{
    return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
}

function adjustString($s)
{
    if (!is_string($s)) {
        return $s;
    }

//    if ($this->config['i18nLib'] === self::VUEX_I18N) {
//        $searchPipePattern = '/(\s)*(\|)(\s)*/';
//        $threeColons = ' ::: ';
//        $s = preg_replace($searchPipePattern, $threeColons, $s);
//    }

    return preg_replace_callback(
        '/(?<!mailto|tel):\w+/',
        function ($matches) {
            return '{' . mb_substr($matches[0], 1) . '}';
        },
        $s
    );
}


function getFileNameFromPath($str)
{
    $str = explode('/', $str);

    return array_pop($str);
}

function getFileExtensionFromString($str)
{
    $str = explode('.', $str);

    return array_pop($str);
}

function isValidPhoneNumber($number, $digits = 11)
{

    return strlen(trimPhone($number)) === $digits;
}

function toBool($var)
{
    return filter_var($var, FILTER_VALIDATE_BOOLEAN);
}

/**
 * Encode array from latin1 to utf8 recursively
 * @param $dat
 * @return array|string
 */
function convert_from_latin1_to_utf8_recursively($dat)
{
    if (is_string($dat)) {
        return mb_convert_encoding($dat, 'UTF-8', 'UTF-8');
    } elseif (is_array($dat)) {
        $ret = [];
        foreach ($dat as $i => $d) $ret[$i] = convert_from_latin1_to_utf8_recursively($d);

        return $ret;
    } elseif (is_object($dat)) {
        foreach ($dat as $i => $d) $dat->$i = convert_from_latin1_to_utf8_recursively($d);

        return $dat;
    } else {
        return $dat;
    }
}

function checkLock($array)
{
    $check = true;
    $locked = Cache::get('lock_vehicles', []);
    foreach ($array as $id) {
        if (in_array($id, $locked)) {
            $check = false;
        }
    }
    $locked = !$locked ? $array : array_unique(array_merge($locked, $array));
    Cache::forget('lock_vehicles');
    Cache::put('lock_vehicles', $locked, 120);

    return $check;
}

function disableLock($array)
{
    $locked = Cache::get('lock_vehicles', []);
    foreach ($array as $id) {
        if (($key = array_search($id, $locked)) !== false) {
            unset($locked[$key]);
        }
    }
    Cache::forget('lock_vehicles');
    Cache::put('lock_vehicles', $locked, 120);
}

function getDateTo($date_from, $order_type, $duration, $end = true)
{
    $date_from = $date_from instanceof \Carbon\Carbon ? $date_from : \Carbon\Carbon::parse($date_from);
    $date_to = (clone $date_from);

    if ($order_type === 'shift') {
        $date_to->addDays($duration - 1);
        if($end) {
            $date_to->endOfDay();
        }
    } else {
        $date_to->addHours($duration);
    }

    return $date_to;
}

function toObject($obj)
{
    return json_decode(json_encode($obj));
}

function file_url($url){
    $parts = parse_url($url);
    $path_parts = array_map('rawurldecode', explode('/', $parts['path']));

    return
        $parts['scheme'] . '://' .
        $parts['host'] .
        implode('/', array_map('rawurlencode', $path_parts))
        ;
}

function processImages($model, $images, $folder, $photoName, $columnName)
{
    $tmp_path = config('app.upload_tmp_dir');

    $update = false;
    $images = $images ?: [];
    foreach ($images as $key => $scan) {

        $str = str_replace("{$tmp_path}/", '', $scan);
        $exist = Storage::disk()->exists($str);

        if (!Str::contains($scan, [$tmp_path])) {
            continue;
        }

        $ext = getFileExtensionFromString($scan);
        $current = "{$photoName}_{$key}.{$ext}";
        $new_name = "{$folder}/{$current}";

        if ($exist && $scan !== $new_name) {
            Storage::disk()->move($scan, $new_name);
            $scans[$key] = $new_name;
            $update = true;
        }
    }

    if ($update) {
        $model->update([$columnName => $scans]);
    }

    $files = Storage::disk()->files($folder);

    foreach ($files as $originalName) {

        $file = $originalName;

        if (!in_array($file, $scans)) {
            Storage::disk()->delete($originalName);
        }
    }
}

function getMachineryValueByMask(string $mask, \App\Machinery $machinery, $additionalData = [])
{
    $data = array_merge([
        'name' => $machinery->name,
        'boardNumber' => $machinery->board_number,
        'serialNumber' => $machinery->serial_number,
        'category' => $machinery?->_type->name,
        'categoryGenitive' => $machinery->_type?->name_style,
        'model' => $machinery->model?->name,
        'brand' => $machinery->brand?->name,
    ], $additionalData);
    $parsed = preg_replace_callback('/{(.*?)}/', function ($matches) use ($data)  {
        [$shortCode, $index] = $matches;

        if (isset($data[$index])) {
            return $data[$index];
        } else {
//                throw new Exception("Shortcode {$shortCode} not found in template id {$this->id}", 1);
        }

    }, $mask);

    return $parsed;
}
