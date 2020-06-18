<?php

declare(strict_types=1);

namespace app\sys;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use stdClass;

class DeployBitbucket
{
    private const SENTRY_DSN = 'https://sentry.io/api/hooks/release/builtin/114324/bfd5c7f4281799d21a588cc8a5927c3f0be4dc886896561c9c8833bc82d5b385/';
    private const BITBUCKET_AUTH = 'https://bitbucket.org/site/oauth2/access_token';

    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $repoPath;

    private $accessToken;

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
        $token = $this->getAccessToken();

        $requestOptions = [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
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
     * @return string|null
     */
    private function sendToSentry($version = 'master'): ?string
    {
        $requestData = [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            RequestOptions::JSON => [
                'version' => $version,
            ],
        ];

        $content = null;
        try {
            $response = $this->guzzleClient->request('POST', self::SENTRY_DSN, $requestData);
            $content = $response->getBody()->getContents();
        } catch (RequestException $exception) {
            $error = $exception->getResponse()->getBody()->getContents();
            Logging::addHistory('sys', 'Ошибка отправки данных в Sentry', [$error]);
        }

        return $content;
    }

    private function getAccessToken(): ?string
    {
        if ($this->accessToken === null) {
            $requestOptions = [
                RequestOptions::AUTH => [
                    $this->config['git_app_key'],
                    $this->config['git_app_secret'],
                ],
                RequestOptions::FORM_PARAMS => [
                    'grant_type' => 'password',
                    'username' => $this->config['git_user'],
                    'password' => $this->config['git_passwd'],
                ],
            ];

            try {
                $response = $this->guzzleClient->request('POST', self::BITBUCKET_AUTH, $requestOptions);
                $content = $response->getBody()->getContents();

                $tokenData = json_decode($content, true);
                $this->accessToken = $tokenData['access_token'];
            } catch (RequestException $exception) {
                $error = $exception->getResponse()->getBody()->getContents();
                Logging::addHistory('sys', 'Ошибка запроса авторизации', [$error]);
            }
        }

        return $this->accessToken;
    }
}
