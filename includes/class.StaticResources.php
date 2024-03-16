<?php

use app\core\assets\constant\Pack;
use app\core\assets\constant\Type;
use app\core\assets\StaticFilesConfigInterface;

class StaticResources
{
    private const PREFIX = 'ct';

    private const DIRECTORY_CSS = '/css/';
    private const DIRECTORY_JS = '/js/';

    private StaticFilesConfigInterface $config;

    private int $timestampOld;

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
        $packs = match ($type->getValue()) {
            Type::CSS => $this->config->getCSSList(),
            Type::JS => $this->config->getJavascriptList(),
            default => throw new InvalidArgumentException('Неизвестный тип'),
        };
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
            $fileOut = GLOBAL_DIR_ROOT . self::DIRECTORY_CSS . self::PREFIX . '-' . $pack . '.css';
            file_put_contents($fileOut, '');
            foreach ((array) $files as $file) {
                file_put_contents($fileOut, "/*\n$file\n*/\n\n\n", FILE_APPEND);
                file_put_contents($fileOut, file_get_contents($file) . "\n", FILE_APPEND);
            }
            $fileHashNew = crc32(file_get_contents($fileOut));
            $fileProduction = GLOBAL_DIR_ROOT . self::DIRECTORY_CSS
                . self::PREFIX . '-' . $pack . '-' . $fileHashNew . '.min.css';
            if (!file_exists($fileProduction)) {
                $minified = $this->getMinifiedCss(file_get_contents($fileOut));
                if ($minified !== '') {
                    file_put_contents($fileProduction, $minified);
                }
            }
            unlink($fileOut);
            $out[$pack] = $fileProduction;
        }

        return $out;
    }

    /**
     * @return array
     */
    private function rebuildJS(): array
    {
        $out = [];
        foreach ($this->config->getJavascriptList() as $pack => $files) {
            $fileOut = GLOBAL_DIR_ROOT . self::DIRECTORY_JS . self::PREFIX . '-' . $pack . '.js';
            file_put_contents($fileOut, '');
            foreach ((array) $files as $file) {
                file_put_contents($fileOut, "/*\n$file\n*/\n\n\n", FILE_APPEND);
                file_put_contents($fileOut, file_get_contents($file) . PHP_EOL, FILE_APPEND);
            }
            $fileHashNew = crc32(file_get_contents($fileOut));
            $fileProduction = GLOBAL_DIR_ROOT . self::DIRECTORY_JS
                . self::PREFIX . '-' . $pack . '-' . $fileHashNew . '.min.js';
            if (!file_exists($fileProduction)) {
                $minified = $this->getMinifiedJavascript(file_get_contents($fileOut));
                if ($minified !== '') {
                    file_put_contents($fileProduction, $minified);
                }
            }
            unlink($fileOut);
            $out[$pack] = $fileProduction;
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
            $mask[] = GLOBAL_DIR_ROOT . self::DIRECTORY_CSS . self::PREFIX . '-' . $packName . '-*.min.css';
        }
        foreach ($this->config->getJavascriptList() as $packName => $file) {
            $mask[] = GLOBAL_DIR_ROOT . self::DIRECTORY_JS . self::PREFIX . '-' . $packName . '-*.min.js';
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

        foreach ($files as $variant) {
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
