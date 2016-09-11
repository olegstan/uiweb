<?php
namespace Uiweb\Curl;

class Curl
{
//curl_multi_init();



//echo $url . '?' . http_build_query($params);
//$ch = curl_init();
//curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
//    //curl_setopt( $ch, CURLOPT_POST, true );
//curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
//    //возвращает данные или true
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_TIMEOUT, 2);
//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
//curl_setopt($ch, CURLOPT_HEADER, 0);
//$response = curl_exec($ch);

    //curl_setopt($ch, CURLOPT_POST, 1);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
//        curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
//        curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
//        curl_setopt($process, CURLOPT_ENCODING , '');
//        curl_setopt($process, CURLOPT_CONNECTTIMEOUT, 120);
//        curl_setopt($process, CURLOPT_TIMEOUT, 120);
//
//        curl_setopt($process, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
//        curl_setopt($process, CURLOPT_POST, 1);
//        curl_setopt($process,CURLOPT_VERBOSE,1);curl_setopt ($process, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt ($process, CURLOPT_SSL_VERIFYHOST, false);




//        curl_setopt($ch, CURLOPT_USERAGENT, 'IE20');
//        curl_setopt($ch, CURLOPT_HEADER, 0);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, '1');



//echo json_decode($response);
    
    public $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

    /**
     * @var
     */
    public $response;

    public $options = [
//        CURLOPT_COOKIEFILE     =>'cookie.txt',
//        CURLOPT_COOKIEJAR      =>'cookie.txt',
//        CURLOPT_VERBOSE => 1,
//        CURLOPT_PROXY => $this->proxy,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_PORT => 80,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_ENCODING => '',
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_MAXREDIRS => 1,
        //CURLOPT_HTTPHEADER, ['Content-type: text/xml;charset=\'utf-8\'']
    ];

    public function get($url, array $fields = [], array $options = [])
    {
        return $this->request($url, $this->options + $options + [
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POST => false,
            CURLOPT_USERAGENT => $this->user_agent
        ]);
    }

    public function put($url)
    {
    }

    public function post($url, array $fields = [], array $options = [])
    {
        return $this->request($url, $this->options + $options + [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POST => true,
            CURLOPT_USERAGENT => $this->user_agent
        ]);
    }

    public function patch($url)
    {
    }

    public function trace($url)
    {
    }

    public function delete($url)
    {
    }

    public function head($url)
    {
    }

    public function options($url)
    {
    }

    public function request($url, array $options = [])
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $response = new CurlResponse(curl_exec($ch), array_merge(curl_getinfo($ch), ['errno' => curl_errno($ch), 'errmsg' => curl_error($ch)]));
        curl_close($ch);
        return $response;
    }

}