<?php

use app\db\MyDB;

class MSearchLog extends Model
{
    protected $_table_pk = 'sl_id';
    protected $_table_order = 'sl_date';
    protected $_table_active = 'sl_id';

    /**
     * @var int
     */
    private $recordId;

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('search_log');
        $this->_table_fields = [
            'sl_date',
            'sl_date_last',
            'sl_query',
            'sl_query_hash',
            'sl_request',
            'sl_answer',
            'sl_error_code',
            'sl_error_text',
            'sl_requests_count',
        ];
        parent::__construct($db);
    }

    /**
     *
     * @param array $data
     *
     * @return int
     */
    public function add(array $data): ?int
    {
        $data['sl_date'] = $this->now();
        $data['sl_date_last'] = $this->now();
        $data['sl_query_hash'] = self::getQueryHash($data['sl_request']);
        $data['sl_answer'] = $data['sl_answer'] ?? null;
        $data['sl_error_text'] = $data['sl_error_text'] ?? null;
        $data['sl_error_code'] = $data['sl_error_code'] ?? 0;

        try {
            $this->recordId = $this->insert($data);
        } catch (Throwable $exception) {
            return null;
        }

        return $this->recordId;
    }

    /**
     *
     * @param array $data
     */
    public function setAnswer(array $data): void
    {
        $this->updateByPk($this->recordId, $data);
    }

    /**
     * Поиск запросов в логе
     *
     * @param string $doc
     *
     * @return string|null
     */
    public function searchByHash(string $doc): ?string
    {
        $this->_db->sql = "
                SELECT *
                FROM $this->_table_name
                WHERE sl_query_hash = :hash
                AND sl_error_code = 0
        ";
        $this->_db->execute(
            [
                ':hash' => self::getQueryHash($doc),
            ]
        );
        $row = $this->_db->fetch();

        return $row['sl_answer'] ?? null;
    }

    /**
     * Увеличиваем счетчик попыток поиска
     *
     * @param string $doc
     */
    public function updateHashData(string $doc): void
    {
        $this->_db->sql = "UPDATE $this->_table_name SET
                                sl_requests_count = sl_requests_count + 1,
                                sl_date_last = NOW()
                            WHERE sl_query_hash = :hash";
        $this->_db->execute(
            [
                ':hash' => self::getQueryHash($doc),
            ]
        );
    }

    /**
     * Индексируемый ключ уникализации запроса
     *
     * @param string $query
     * @return string
     */
    public static function getQueryHash(string $query): string
    {
        $lower = mb_strtolower($query);
        $symbols = preg_replace('|s+|', '', $lower);
        $trimmed = trim($symbols);

        return sha1($trimmed);
    }

    /**
     * Удаляет записи старше указанной даты
     *
     * @param string $dateDepth
     */
    public function deleteOldRecords(string $dateDepth): void
    {
        $timestamp = strtotime($dateDepth);

        $this->_db->sql = "DELETE FROM $this->_table_name
                            WHERE sl_date <= :date_from";
        $this->_db->execute(
            [
                ':date_from' => date('Y-m-d H:i:s', $timestamp),
            ]
        );
    }
}
