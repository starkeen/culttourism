<?php

class StaticResources {

    private $config = array(
        'css' => array(),
        'js' => array(),
    );

    public function __construct() {
        $this->config = include(_DIR_ROOT . '/config/static_files.php');
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
            $file_out = _DIR_ROOT . '/css/ct-' . $pack . '.css';
            file_put_contents($file_out, '');
            foreach ($files as $file) {
                file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
                file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
            }
            $file_hash_new = crc32(file_get_contents($file_out));
            $file_production = _DIR_ROOT . '/css/ct-' . $pack . '-' . $file_hash_new . '.min.css';
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
                    $old_files = glob(_DIR_ROOT . '/css/ct-' . $pack . '-*.min.css');
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
            $file_out = _DIR_ROOT . '/js/ct-' . $pack . '.js';
            file_put_contents($file_out, '');
            foreach ($files as $file) {
                file_put_contents($file_out, "/*\n$file\n*/\n\n\n", FILE_APPEND);
                file_put_contents($file_out, file_get_contents($file) . "\n", FILE_APPEND);
            }
            $file_hash_new = crc32(file_get_contents($file_out));
            $file_production = _DIR_ROOT . '/js/ct-' . $pack . '-' . $file_hash_new . '.min.js';
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
                    $old_files = glob(_DIR_ROOT . '/js/ct-' . $pack . '-*.min.js');
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

}
