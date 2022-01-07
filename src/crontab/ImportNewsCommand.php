<?php

declare(strict_types=1);

namespace app\crontab;

use app\core\exception\CoreException;
use DOMDocument;
use MNewsItems;
use MNewsSources;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class ImportNewsCommand extends AbstractCrontabCommand
{
    private const RSS_TAGS = [
        'title',
        'link',
        'guid',
        'comments',
        'description',
        'pubDate',
        'category',
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MNewsItems
     */
    private $newsItemsModel;

    /**
     * @var MNewsSources
     */
    private $newsSourcesModel;

    public function __construct(LoggerInterface $logger, MNewsItems $newsItemsModel, MNewsSources $newsSourcesModel)
    {
        $this->logger = $logger;
        $this->newsItemsModel = $newsItemsModel;
        $this->newsSourcesModel = $newsSourcesModel;
    }

    public function run(): void
    {
        $sources = $this->newsSourcesModel->getPortion();
        foreach ($sources as $source) {
            $rssFeed = $this->rssToArray('item', $source['ns_url']);
            foreach ($rssFeed as $item) {
                $this->newsItemsModel->add(
                    [
                        'source_id' => $source['ns_id'],
                        'pubdate' => date('Y-m-d H:i:s', strtotime($item['pubDate'])),
                        'title' => !empty($item['title']) ? $item['title'] : '[no title]',
                        'link' => $item['link'],
                        'description' => htmlentities($item['description'] ?? '', ENT_QUOTES, 'UTF-8'),
                    ]
                );
            }
            $this->newsSourcesModel->updateByPk(
                $source['ns_id'],
                [
                    'ns_last_read' => $this->newsSourcesModel->now(),
                ]
            );
        }
    }

    private function rssToArray($tag, $url): array
    {
        $doc = new DOMdocument();
        $result = [];
        try {
            if (!$doc->load($url)) {
                throw new CoreException("HTTP error [$url]");
            }
            foreach ($doc->getElementsByTagName($tag) as $node) {
                $item = [];
                foreach (self::RSS_TAGS as $value) {
                    $no = $node->getElementsByTagName($value);
                    if (isset($no) && is_object($no) && is_object($no->item(0))) {
                        $item[$value] = (string) $no->item(0)->nodeValue;
                    }
                }
                $result[] = $item;
            }
        } catch (Throwable $e) {
            $this->logger->warning('Ошибка получения новостей', ['error' => $e->getMessage()]);
        }

        return $result;
    }
}
