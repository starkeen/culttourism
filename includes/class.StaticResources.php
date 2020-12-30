<?php

use app\core\assets\constant\Pack;
use app\core\assets\constant\Type;
use app\core\assets\StaticFilesConfigInterface;

class StaticResources
{
    private const PREFIX = 'ct';

    /**
     * @var StaticFilesConfigInterface
     */
    private $config;

    /**
     * @var int
     */
    private $timestampOld;

    /**
     * @param StaticFilesConfigInterface $config
     */
    public function __construct(StaticFilesConfigInterface $config)
    {
        $this->config = $config;
        $this->timestampOld = strtotime('-6 months');
    }

    /**
     * @param Type $type
     * @param Pack $pack
     *
     * @return string
     */
    public function getFull(Type $type, Pack $pack): string
    {
        $out = '';
        switch ($type->getValue()) {
            case Type::CSS:
                $packs = $this->config->getCSSList();
                break;
            case Type::JS:
                $packs = $this->config->getJavascriptList();
                break;
            default:
                throw new InvalidArgumentException('Неизвестный тип');
        }
        $files = $packs[$pack->getValue()];
        foreach ($files as $file) {
            $out .= file_get_contents($file);
        }

        return $out;
    }

    /**
     * @return array
     */
    private function rebuildCSS(): array
    {
        $out = [];
        foreach ($this->config->getCSSList() as $pack => $files) {
            $file_out = _DIR_ROOT . '/css/' . self::PREFIX . '-' . $pack . '.css';
            file_put_contents($file_out, '');
            foreach ((array) $files as $file) {
                file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
                file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
            }
            $file_hash_new = crc32(file_get_contents($file_out));
            $file_production = _DIR_ROOT . '/css/' . self::PREFIX . '-' . $pack . '-' . $file_hash_new . '.min.css';
            if (!file_exists($file_production)) {
                $minified = $this->getMinifiedCss(file_get_contents($file_out));
                if ($minified !== '') {
                    file_put_contents($file_production, $minified);
                }
            }
            unlink($file_out);
            $out[$pack] = $file_production;
        }

        return $out;
    }

    /**
     * @return array
     */
    private function rebuildJS(): array
    {
        $out = [];
        foreach ((array) $this->config->getJavascriptList() as $pack => $files) {
            $file_out = _DIR_ROOT . '/js/' . self::PREFIX . '-' . $pack . '.js';
            file_put_contents($file_out, '');
            foreach ((array) $files as $file) {
                file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
                file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
            }
            $file_hash_new = crc32(file_get_contents($file_out));
            $file_production = _DIR_ROOT . '/js/' . self::PREFIX . '-' . $pack . '-' . $file_hash_new . '.min.js';
            if (!file_exists($file_production)) {
                $minified = $this->getMinifiedJavascript(file_get_contents($file_out));
                if ($minified !== '') {
                    file_put_contents($file_production, $minified);
                }
            }
            unlink($file_out);
            $out[$pack] = $file_production;
        }

        return $out;
    }

    /**
     * @return array
     */
    public function rebuildAll(): array
    {
        return [
            Type::CSS => $this->rebuildCSS(),
            Type::JS => $this->rebuildJS(),
        ];
    }

    /**
     *
     */
    public function clean(): void
    {
        $mask = [];

        foreach ($this->config->getCSSList() as $packName => $file) {
            $mask[] = _DIR_ROOT . '/css/' . self::PREFIX . '-' . $packName . '-*.min.css';
        }
        foreach ($this->config->getJavascriptList() as $packName => $file) {
            $mask[] = _DIR_ROOT . '/js/' . self::PREFIX . '-' . $packName . '-*.min.js';
        }

        $files = [];
        foreach ($mask as $id => $variant) {
            foreach (glob($variant) as $filename) {
                $timestamp = filemtime($filename);
                $files[$id][$timestamp] = [
                    'filename' => $filename,
                    'timestamp' => $timestamp,
                    'delete' => $timestamp < $this->timestampOld,
                ];
            }
            ksort($files[$id]);
        }

        foreach ($files as $id => $variant) {
            array_pop($variant);
            foreach ((array) $variant as $file) {
                if ($file['delete']) {
                    unlink($file['filename']);
                    echo "delete old file: {$file['filename']} => " . date('d.m.Y', $file['timestamp']) . PHP_EOL;
                }
            }
        }
    }

    /**
     * @param string $content
     * @return string
     */
    private function getMinifiedCss(string $content): string
    {
        return $this->getExternalResponse('https://cssminifier.com/raw', $content);
    }

    /**
     * @param string $content
     * @return string
     */
    private function getMinifiedJavascript(string $content): string
    {
        return $this->getExternalResponse('https://javascript-minifier.com/raw', $content);
    }

    /**
     * @param string $url
     * @param string $content
     * @return string
     */
    private function getExternalResponse(string $url, string $content): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['input' => trim($content)]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $minified = curl_exec($ch);
        curl_close($ch);

        return $minified;
    }
}
