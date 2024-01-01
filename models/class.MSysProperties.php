<?php

use app\cache\Cache;
use app\db\MyDB;
use config\CachesConfig;

/**
 * Модель таблицы системных настроек
 */
class MSysProperties extends Model
{
    private const CACHE_PREFIX_BY_NAME = 'by_name_v1__';
    private const CACHE_KEY_PUBLIC = 'public';

    protected $_table_pk = 'sp_id';
    protected $_table_order = 'sp_name';
    protected $_table_active = 'sp_id';
    protected $cache;

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('siteprorerties');
        $this->_table_fields = [
            //'sp_name',
            //'sp_rs_id',
            'sp_value',
            //'sp_title',
            //'sp_public',
            //'sp_whatis',
        ];
        parent::__construct($db);
        $this->cache = Cache::i(CachesConfig::SYSPROPS);
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getSettingsByBranchId(int $id): array
    {
        $this->_db->sql = "SELECT sp_name, sp_value FROM $this->_table_name WHERE sp_rs_id = :rsid";
        $this->_db->execute(
            [
                ':rsid' => $id,
            ]
        );

        $config = [];
        while ($row = $this->_db->fetch()) {
            $config[$row['sp_name']] = $row['sp_value'];
        }

        return $config;
    }

    /**
     * @param bool $refresh
     *
     * @return array
     */
    public function getPublic(bool $refresh = false): array
    {
        $config = $this->cache->get(self::CACHE_KEY_PUBLIC);
        if (empty($config) || $refresh) {
            $this->_db->sql = "SELECT sp_name, sp_value FROM $this->_table_name WHERE sp_public = :pub";
            $this->_db->execute(
                [
                    ':pub' => 1,
                ]
            );
            $config = [];
            while ($row = $this->_db->fetch()) {
                $config[$row['sp_name']] = $row['sp_value'];
            }
            $this->cache->put(self::CACHE_KEY_PUBLIC, $config);
        }
        return $config;
    }

    /**
     * @param string $name
     * @param        $value
     */
    public function updateByName(string $name, $value): void
    {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET sp_value = :value
                            WHERE sp_name = :name";

        $this->_db->execute(
            [
                ':name' => $name,
                ':value' => $value,
            ]
        );

        $cacheKey = self::CACHE_PREFIX_BY_NAME . $name;
        $this->cache->remove($cacheKey);
        $this->cache->remove(self::CACHE_KEY_PUBLIC);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getByName(string $name): string
    {
        $cacheKey = self::CACHE_PREFIX_BY_NAME . $name;

        $result = $this->cache->get($cacheKey);

        if ($result === null) {
            $this->_db->sql = "SELECT sp_value
                            FROM $this->_table_name
                            WHERE sp_name = :name";
            $this->_db->execute(
                [
                    ':name' => $name,
                ]
            );

            $result = (string) $this->_db->fetchCol();

            $this->cache->put($cacheKey, $result);
        }

        return $result;
    }
}
