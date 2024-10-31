<?php

class seocopyApi {
    const BASEURL = 'https://api.wpseoplugins.org/';
    public static function request($path){
        try {
            $request = wp_remote_get(self::BASEURL.$path);
        }catch(\Exception $e){
            throw new seocopyApiException(__('Connection unavailable, error', seocopy_DOMAIN));
        }
        $data = json_decode($request['body'],true);
        if(!$data){
            throw new seocopyApiException(__('Connection unavailable', seocopy_DOMAIN));
        }
        if(isset($data['code']) && $data['code']!=200){
            throw new seocopyApiException($data['message'], $data['code']);
        }
        return $data;
    }
    public static function keyIsValid($key){
        self::getBalance($key);
        return true;
    }
    public static function getBalance($key){
        $data = self::request('balance?'.http_build_query(array('key'=>$key)));
        return $data['balance'];
    }


}
