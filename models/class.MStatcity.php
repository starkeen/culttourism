<?php

use app\db\MyDB;

class MStatcity extends Model
{
    protected $_table_pk = 'sc_id';
    protected $_table_order = 'sc_id';
    protected $_table_active = 'sc_id';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('statcity');
        $this->_table_fields = [
            'sc_id',
            'sc_citypage_id',
            'sc_date',
            'sc_hash',
        ];
        parent::__construct($db);
    }

    public function add($city_id, $hash)
    {
        $this->_db->sql = "INSERT INTO $this->_table_name SET
                            sc_citypage_id = :city_id,
                            sc_date = NOW(),
                            sc_hash = :hash
                           ON DUPLICATE KEY UPDATE sc_date = NOW()";

        $this->_db->execute([
            ':hash' => $hash,
            ':city_id' => $city_id,
        ]);

        return true;
    }
}
