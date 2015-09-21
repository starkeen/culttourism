<?php

class StaticResources {

    private $config = array(
        'css' => array(),
        'js' => array(),
    );
    private $prefix = 'ct';
    private $timestamp_old;

    public function __construct() {
        $this->config = include(_DIR_ROOT . '/config/static_files.php');
        $this->timestamp_old = strtotime("-6 months");
    }

    public function getFull($type, $pack) {
        $out = '';
        foreach ($this->config[$type][$pack] as $file) {
            $out.= file_get_contents($file);
        }
        return $out;
    }

    private function rebuildCSS() {
        $out = array();
        foreach ($this->config['css'] as $pack => $files) {
            $file_out = _DIR_ROOT . '/css/' . $this->prefix . '-' . $pack . '.css';
            file_put_contents($file_out, '');
            foreach ($files as $file) {
                file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
                file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
            }
            $file_hash_new = crc32(file_get_contents($file_out));
            $file_production = _DIR_ROOT . '/css/' . $this->prefix . '-' . $pack . '-' . $file_hash_new . '.min.css';
            if (!file_exists($file_production)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://cssminifier.com/raw');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('input' => trim(file_get_contents($file_out)))));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FAILONERROR, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $minified = curl_exec($ch);
                curl_close($ch);
                if ($minified != '') {
                    $old_files = glob(_DIR_ROOT . '/css/' . $this->prefix . '-' . $pack . '-*.min.css');
                    file_put_contents($file_production, $minified);
                    foreach ($old_files as $old) {
                        // unlink($old);
                    }
                }
            }
            unlink($file_out);
            $out[$pack] = $file_production;
        }
        return $out;
    }

    private function rebuildJS() {
        $out = array();
        foreach ($this->config['js'] as $pack => $files) {
            $file_out = _DIR_ROOT . '/js/' . $this->prefix . '-' . $pack . '.js';
            file_put_contents($file_out, '');
            foreach ($files as $file) {
                file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
                file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
            }
            $file_hash_new = crc32(file_get_contents($file_out));
            $file_production = _DIR_ROOT . '/js/' . $this->prefix . '-' . $pack . '-' . $file_hash_new . '.min.js';
            if (!file_exists($file_production)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://javascript-minifier.com/raw');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('input' => trim(file_get_contents($file_out)))));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FAILONERROR, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $minified = curl_exec($ch);
                curl_close($ch);
                if ($minified != '') {
                    $old_files = glob(_DIR_ROOT . '/js/' . $this->prefix . '-' . $pack . '-*.min.js');
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

    public function rebuildAll() {
        return array(
            'css' => $this->rebuildCSS(),
            'js' => $this->rebuildJS(),
        );
    }

    public function clean() {
        $mask = array();
        foreach ($this->config as $filetype => $files) {
            foreach ($files as $msk => $file) {
                $mask[] = _DIR_ROOT . '/' . $filetype . '/' . $this->prefix . '-' . $msk . '-*.min.' . $filetype;
            }
        }
        $files = array();
        foreach ($mask as $id => $variant) {
            foreach (glob($variant) as $filename) {
                $timestamp = filemtime($filename);
                $files[$id][$timestamp] = array(
                    'filename' => $filename,
                    'timestamp' => $timestamp,
                    'delete' => $timestamp < $this->timestamp_old,
                );
            }
            ksort($files[$id]);
        }

        foreach ($files as $id => $variant) {
            array_pop($variant);
            foreach ($variant as $file) {
                if ($file['delete']) {
                    unlink($file['filename']);
                    echo "delete old file: {$file['filename']} => " . date('d.m.Y', $file['timestamp']) . PHP_EOL;
                }
            }
        }
    }

}
