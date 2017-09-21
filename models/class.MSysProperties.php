<?php

use app\cache\Cache;

/**
 * Description of class MSysProperties
 *
 * @author Андрей
 */
class MSysProperties extends Model
{
    protected $_table_pk = 'sp_id';
    protected $_table_order = 'sp_name';
    protected $_table_active = 'sp_id';
    protected $cache;

    public function __construct($db)
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
        $this->cache = Cache::i('sysprops');
    }

    public function getSettingsByBranchId($rsid)
    {
        $this->_db->sql = "SELECT sp_name, sp_value FROM $this->_table_name WHERE sp_rs_id = :rsid";

        $this->_db->execute(
            [
                ':rsid' => intval($rsid),
            ]
        );
        $config = [];
        while ($row = $this->_db->fetch()) {
            $config[$row['sp_name']] = $row['sp_value'];
        }
        return $config;
    }

    public function getPublic($refresh = false)
    {
        $config = $this->cache->get('public');
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
            $this->cache->put('public', $config);
        }
        return $config;
    }

    public function updateByName($name, $value)
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
        $this->cache->remove('public');
    }

    public function getByName($name)
    {
        $this->_db->sql = "SELECT sp_value
                            FROM $this->_table_name
                            WHERE sp_name = :name";
        $this->_db->execute(
            [
                ':name' => $name,
            ]
        );
        return $this->_db->fetchCol();
    }

}
