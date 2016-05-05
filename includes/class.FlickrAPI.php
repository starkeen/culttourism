<?php

/**
 * Класс для работы с API flickr.com
 */
class FlickrAPI {

    const URL = 'https://api.flickr.com/services/rest/';

    protected $token;

    public function __construct($token) {
        $this->token = $token;
    }

    /**
     * Информация о фотографии
     * @param int $id
     * @return array
     */
    public function getPhotoInfo($id) {
        $requestData = array(
            'api_key' => $this->token,
            'format' => 'json',
            'nojsoncallback' => '1',
            'method' => 'flickr.photos.getInfo',
            'photo_id' => $id,
        );
        $url = self::URL . '?' . http_build_query($requestData);
        try {
            $data = $this->request($url);
            return json_decode($data, true);
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Доступные размеры картинки
     * @param int $id
     * @return array
     */
    public function getSizes($id) {
        $requestData = array(
            'api_key' => $this->token,
            'format' => 'json',
            'nojsoncallback' => '1',
            'method' => 'flickr.photos.getSizes',
            'photo_id' => $id,
        );
        $url = self::URL . '?' . http_build_query($requestData);
        try {
            $data = $this->request($url);
            return json_decode($data, true);
        } catch (Exception $e) {
            //
        }
    }

    private function request($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);

        $text = curl_exec($ch);
        $errno = curl_errno($ch);
        if ($errno) {
            $error_message = curl_error($ch);
            throw new Exception("cURL error ({$errno}):\n {$error_message}");
        }
        curl_close($ch);

        return $text;
    }

}
