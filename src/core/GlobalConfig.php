<?php

declare(strict_types=1);

namespace app\core;

use app\db\MyDB;
use MSysProperties;

class GlobalConfig
{
    /**
     * @var MSysProperties
     */
    private $propertiesModel;

    /**
     * @var Properties
     */
    private $properties;

    /**
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->propertiesModel = new MSysProperties($db);
    }

    /**
     * @return string
     */
    public function getUrlCss(): string
    {
        return $this->getProperties()->mainfile_css;
    }

    /**
     * @return string
     */
    public function getUrlJs(): string
    {
        return $this->getProperties()->mainfile_js;
    }

    /**
     * @deprecated
     * @return array
     */
    public function getJsResources(): array
    {
        return [
            'res_js_list' => $this->getProperties()->res_js_list,
            'res_js_map' => $this->getProperties()->res_js_map,
            'res_js_city' => $this->getProperties()->res_js_city,
            'res_js_point' => $this->getProperties()->res_js_point,
            'res_js_editor' => $this->getProperties()->res_js_editor,
        ];
    }

    /**
     * @return string
     */
    public function getUrlRSS(): string
    {
        return $this->getProperties()->main_rss;
    }

    /**
     * @return string
     */
    public function getYandexMapsKey(): string
    {
        return $this->getProperties()->key_yandexmaps;
    }

    /**
     * @return Properties
     */
    private function getProperties(): Properties
    {
        if ($this->properties === null) {

            $this->properties = new Properties();
            $globals = $this->propertiesModel->getPublic();
            foreach ($globals as $key => $value) {
                $this->properties->{$key} = $value;
            }
        }
        return $this->properties;
    }
}
