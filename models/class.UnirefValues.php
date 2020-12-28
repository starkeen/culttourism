<?php

use app\db\MyDB;

class UnirefValues extends Model
{
    protected $_table_pk = 'uv_id';
    protected $_table_order = 'uv_order';
    protected $_table_active = 'uv_active';

    private $_key_id;

    public function __construct(MyDB $db, $key_id)
    {
        $this->_table_name = $db->getTableName('uniref_values');
        $this->_table_fields = [
            'uv_uk_id',
            'uv_pid',
            'uv_title',
            'uv_title_short',
            'uv_code',
            'uv_color_text',
            'uv_color_bg',
            'uv_k',
            'uv_link',
            'uv_picture',
            'uv_photo',
            'uv_order',
            'uv_active',
        ];
        $this->_key_id = (int) $key_id;
        $this->_files_dir = _DIR_DATA . '/uniref/' . (int) $key_id . '/full';
        parent::__construct($db);
    }

    public function getAll(): array
    {
        $out = [];
        $this->_db->sql = "SELECT * FROM $this->_table_name
                            WHERE uv_uk_id = '$this->_key_id'
                                AND uv_pid = 0
                           ORDER BY $this->_table_order ASC, uv_title ASC";
        $this->_db->exec();
        while ($row = $this->_db->fetch()) {
            $out[$row['uv_id']] = $row;
            $out[$row['uv_id']]['subs'] = [];
        }
        $this->_db->sql = "SELECT * FROM $this->_table_name
                            WHERE uv_uk_id = '$this->_key_id'
                                AND uv_pid > 0
                           ORDER BY $this->_table_order ASC, uv_title ASC";
        $this->_db->exec();
        while ($row = $this->_db->fetch()) {
            $out[$row['uv_pid']]['subs'][$row['uv_id']] = $row;
        }
        return $out;
    }

    public function getActive(): array
    {
        $this->_db->sql = "SELECT * FROM $this->_table_name
                            WHERE uv_uk_id = '$this->_key_id'
                                AND $this->_table_active = 1
                           ORDER BY $this->_table_order ASC, uv_title ASC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }
}
