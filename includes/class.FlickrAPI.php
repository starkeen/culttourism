<?php

/**
 * Класс для работы с API flickr.com
 */
class FlickrAPI
{
    private const URL = 'https://api.flickr.com/services/rest/';

    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Информация о фотографии
     *
     * @param int $id
     *
     * @return array
     */
    public function getPhotoInfo($id): ?array
    {
        $licenses = [
            0 => 'All Rights Reserved',
            1 => 'Attribution-NonCommercial-ShareAlike License',
            2 => 'Attribution-NonCommercial License',
            3 => 'Attribution-NonCommercial-NoDerivs License',
            4 => 'Attribution License',
            5 => 'Attribution-ShareAlike License',
            6 => 'Attribution-NoDerivs License',
            7 => 'No known copyright restrictions',
            8 => 'United States Government Work',
        ];
        $requestData = [
            'api_key' => $this->token,
            'format' => 'json',
            'nojsoncallback' => '1',
            'method' => 'flickr.photos.getInfo',
            'photo_id' => $id,
        ];
        $url = self::URL . '?' . http_build_query($requestData);
        try {
            $data = $this->request($url);
            $out = json_decode($data, true);
            $out['photo']['license_text'] = isset($licenses[$out['photo']['license']]) ?
                $licenses[$out['photo']['license']] : 'undefined license';
            return $out;
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Доступные размеры картинки
     *
     * @param int $id
     *
     * @return array
     */
    public function getSizes($id): ?array
    {
        $requestData = [
            'api_key' => $this->token,
            'format' => 'json',
            'nojsoncallback' => '1',
            'method' => 'flickr.photos.getSizes',
            'photo_id' => $id,
        ];
        $url = self::URL . '?' . http_build_query($requestData);
        try {
            $data = $this->request($url);
            return json_decode($data, true);
        } catch (Exception $e) {
            //
        }
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getLocation($id)
    {
        $requestData = [
            'api_key' => $this->token,
            'format' => 'json',
            'nojsoncallback' => '1',
            'method' => 'flickr.photos.geo.getLocation',
            'photo_id' => $id,
        ];
        $url = self::URL . '?' . http_build_query($requestData);
        try {
            $data = $this->request($url);
            return json_decode($data, true);
        } catch (Exception $e) {
            //
        }
    }

    private function request($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36'
        );
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
