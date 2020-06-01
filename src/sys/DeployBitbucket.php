<?php

declare(strict_types=1);

namespace app\sys;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use stdClass;

class DeployBitbucket
{
    private $guzzleClient;

    private $config;
    private $repoPath;

    /**
     * @param Client $guzzleClient
     * @param array $config
     */
    public function __construct(Client $guzzleClient, array $config)
    {
        $this->guzzleClient = $guzzleClient;
        $this->config = $config;
    }

    /**
     * @param stdClass $req
     *
     * @return array
     */
    public function deploy($req): array
    {
        $this->repoPath = $req->canon_url
            . $this->config['git_url']
            . $req->repository->absolute_url . 'src/'
            . $this->config['git_branch'] . '/';
        $log = [];

        foreach ($req->commits as $commit) {
            // check if the branch is known at this step
            if (!empty($commit->branch) || !empty($commit->branches)) {
                // if commit was on the branch we're watching, deploy changes
                if (
                    $commit->branch === $this->config['git_branch']
                    ||
                    (!empty($commit->branches) && in_array($this->config['git_branch'], $commit->branches, true))
                ) {
                    // get a list of files
                    foreach ($commit->files as $file) {
                        if ($file->type === 'modified' || $file->type === 'added') {
                            $log[] = $this->addFile($file->file);
                        } elseif ($file->type === 'removed') {
                            $log[] = $this->delFile($file->file);
                        }
                    }

                    $this->sendToSentry($commit->raw_node);
                }
            }
        }

        $log[] = shell_exec(_DIR_ROOT . '/bin/deploy.sh');

        return $log;
    }

    /**
     * @param string $filename
     *
     * @return null|string
     */
    private function addFile($filename): ?string
    {
        $out = null;
        $filePath = $this->repoPath . $filename;
        $fileLocation = $this->config['location'] . $filename;

        Logging::addHistory('sys', 'fetch contents for ' . $filePath);
        $contents = $this->getFileContents($filePath);
        Logging::addHistory('sys', 'fetched contents is: ' . $contents);

        if ($contents === 'Not Found') {
            // try one more time, BitBucket gets weirdo sometimes
            Logging::addHistory('sys', 'retry fetch file contents');
            $contents = $this->getFileContents($filePath);
        }
        if ($contents !== 'Not Found' && $contents !== '' && $contents !== null) {
            if (!is_dir(dirname($fileLocation))) {
                // attempt to create the directory structure first
                mkdir(dirname($fileLocation), 0755, true);
            }
            file_put_contents($fileLocation, $contents);
            $out = "Synchronized $filename";
            Logging::addHistory('sys', $out);
        } else {
            $out = "Could not get file contents for $filename: [$contents]";
            Logging::addHistory('sys', $out);
        }

        return $out;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    private function delFile($filename): string
    {
        $out = [];
        if (unlink($this->config['location'] . $filename)) {
            $delDir = $this->delDir(dirname($this->config['location'] . $filename));
            if ($delDir) {
                $out[] = "Removed dir $delDir";
            }
            $out[] = "Removed $filename";
        } else {
            $out[] = "Can't remove file $filename";
        }

        return implode('; ', $out);
    }

    /**
     * @param string $dirPath
     *
     * @return string|null
     */
    private function delDir($dirPath): ?string
    {
        if (!glob($dirPath . '/*')) {
            if (rmdir($dirPath)) {
                return $dirPath;
            }
        }
        return null;
    }

    /**
     * @param string $url
     *
     * @return string|null
     */
    private function getFileContents($url): ?string
    {
        $requestOptions = [
            RequestOptions::AUTH => [
                $this->config['git_user'],
                $this->config['git_passwd'],
            ],
        ];

        $content = null;
        try {
            $response = $this->guzzleClient->get($url, $requestOptions);
            $content = $response->getBody()->getContents();
        } catch (RequestException $exception) {
            $error = $exception->getResponse()->getBody()->getContents();
            Logging::addHistory('sys', "Ошибка guzzle:  [$error] from URL [$url]");
        }

        return $content;
    }

    /**
     * TODO Вынести Sentry DSN в конфиг
     *
     * @param string $version
     *
     * @return string
     */
    private function sendToSentry($version = 'master'): string
    {
        $data = ['version' => $version];
        $url = 'https://sentry.io/api/hooks/release/builtin/114324/bfd5c7f4281799d21a588cc8a5927c3f0be4dc886896561c9c8833bc82d5b385/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
            ]
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
