<?php

/**
 * Description of class DeployBitbucket
 *
 * @author Андрей
 */
class DeployBitbucket {

    private $_config = array();
    private $_repo_path = null;

    public function __construct($config) {
        $this->_config = $config;
    }

    public function deploy($req) {
        $this->_repo_path = $req->canon_url
                . $this->_config['git_url']
                . $req->repository->absolute_url . 'raw/'
                . $this->_config['git_branch'] . '/';
        $log = array();

        foreach ($req->commits as $commit) {
            // check if the branch is known at this step
            if (!empty($commit->branch) || !empty($commit->branches)) {
                // if commit was on the branch we're watching, deploy changes
                if ($commit->branch == $this->_config['git_branch'] || (!empty($commit->branches) && array_search($this->_config['git_branch'], $commit->branches) !== false)) {
                    // get a list of files
                    foreach ($commit->files as $file) {
                        if ($file->type == 'modified' || $file->type == 'added') {
                            $log[] = $this->addFile($file->file);
                        } elseif ($file->type == 'removed') {
                            $log[] = $this->delFile($file->file);
                        }
                    }
                }
            }
        }
        
        //$log[] = system('chdir '._DIR_ROOT.' & bin/composer install');
        $log[] = shell_exec(_DIR_ROOT.'/bin/deploy.sh');

        return $log;
    }

    private function addFile($filename) {
        $out = null;
        $contents = $this->getFileContents($this->_repo_path . $filename);
        if ($contents == 'Not Found') {
            // try one more time, BitBucket gets weirdo sometimes
            $contents = $this->getFileContents($this->_repo_path . $filename);
        }
        if ($contents != 'Not Found' && $contents != '') {
            if (!is_dir(dirname($this->_config['location'] . $filename))) {
                // attempt to create the directory structure first
                mkdir(dirname($this->_config['location'] . $filename), 0755, true);
            }
            //chmod($this->_config['location'] . $filename, 0664);
            file_put_contents($this->_config['location'] . $filename, $contents);
            $out = "Synchronized $filename";
        } else {
            $out = "Could not get file contents for $filename";
        }
        return $out;
    }

    private function delFile($filename) {
        $out = array();
        if (unlink($this->_config['location'] . $filename)) {
            $deldir = $this->delDir(dirname($this->_config['location'] . $filename));
            if ($deldir) {
                $out[] = "Removed dir $deldir";
            }
            $out[] = "Removed $filename";
        } else {
            $out[] = "Can't remove file $filename";
        }
        return implode("; ", $out);
    }

    private function delDir($dirpath) {
        if (!glob($dirpath . '/*')) {
            if (rmdir($dirpath)) {
                return $dirpath;
            }
        }
        return;
    }

    private function getFileContents($url) {
        // create a new cURL resource
        $ch = curl_init();
        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->_config['git_user'] . ':' . $this->_config['git_passwd']);
        //curl_setopt($ch, CURLOPT_SSLVERSION, 2);
        //curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'SSLv3');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // grab URL
        $data = curl_exec($ch);

        if ($data === false) {
            $error = curl_error($ch);
            Logging::addHistory('sys', "Ошибка curl:  [$error] from URL [$url]");
            //file_put_contents('./curl', $error, FILE_APPEND);
        }

        // close cURL resource, and free up system resources
        curl_close($ch);

        return $data;
    }

}
