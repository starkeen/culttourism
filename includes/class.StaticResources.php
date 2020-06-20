<?php

class StaticResources
{
    const PREFIX = 'ct';

    private $config = [
        'css' => [],
        'js' => [],
    ];

    private $timestamp_old;

    /**
     * StaticResources constructor.
     */
    public function __construct()
    {
        $this->config = include(_DIR_ROOT . '/config/static_files.php');
        $this->timestamp_old = strtotime('-6 months');
    }

    /**
     * @param $type
     * @param $pack
     *
     * @return string
     */
    public function getFull($type, $pack): string
    {
        $out = '';
        foreach ((array) $this->config[$type][$pack] as $file) {
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
        foreach ((array) $this->config['css'] as $pack => $files) {
            $file_out = _DIR_ROOT . '/css/' . self::PREFIX . '-' . $pack . '.css';
            file_put_contents($file_out, '');
            foreach ((array) $files as $file) {
                file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
                file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
            }
            $file_hash_new = crc32(file_get_contents($file_out));
            $file_production = _DIR_ROOT . '/css/' . self::PREFIX . '-' . $pack . '-' . $file_hash_new . '.min.css';
            if (!file_exists($file_production)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://cssminifier.com/raw');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['input' => trim(file_get_contents($file_out))]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FAILONERROR, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $minified = curl_exec($ch);
                curl_close($ch);
                if ($minified != '') {
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
        foreach ((array) $this->config['js'] as $pack => $files) {
            $file_out = _DIR_ROOT . '/js/' . self::PREFIX . '-' . $pack . '.js';
            file_put_contents($file_out, '');
            foreach ((array) $files as $file) {
                file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
                file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
            }
            $file_hash_new = crc32(file_get_contents($file_out));
            $file_production = _DIR_ROOT . '/js/' . self::PREFIX . '-' . $pack . '-' . $file_hash_new . '.min.js';
            if (!file_exists($file_production)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://javascript-minifier.com/raw');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['input' => trim(file_get_contents($file_out))]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FAILONERROR, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $minified = curl_exec($ch);
                curl_close($ch);
                if ($minified != '') {
                    $old_files = glob(_DIR_ROOT . '/js/' . self::PREFIX . '-' . $pack . '-*.min.js');
                    file_put_contents($file_production, $minified);
                    foreach ($old_files as $old) {
                        //unlink($old);
                    }
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
            'css' => $this->rebuildCSS(),
            'js' => $this->rebuildJS(),
        ];
    }

    /**
     *
     */
    public function clean()
    {
        $mask = [];
        foreach ((array) $this->config as $filetype => $files) {
            foreach ($files as $msk => $file) {
                $mask[] = _DIR_ROOT . '/' . $filetype . '/' . self::PREFIX . '-' . $msk . '-*.min.' . $filetype;
            }
        }
        $files = [];
        foreach ($mask as $id => $variant) {
            foreach (glob($variant) as $filename) {
                $timestamp = filemtime($filename);
                $files[$id][$timestamp] = [
                    'filename' => $filename,
                    'timestamp' => $timestamp,
                    'delete' => $timestamp < $this->timestamp_old,
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
}
