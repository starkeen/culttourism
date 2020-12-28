<?php

use app\db\MyDB;

class MModules extends Model
{
    protected $_table_pk = 'md_id';
    protected $_table_order = 'md_sort';
    protected $_table_active = 'md_active';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('modules');
        $this->_table_fields = [
            'md_id',
            'md_pid',
            'md_name',
            'md_url',
            'md_title',
            'md_keywords',
            'md_description',
            'md_active',
            'md_pagecontent',
            'md_photo_id',
            'md_sort',
            'md_robots',
            'md_lastedit',
        ];
        parent::__construct($db);
    }

    /**
     * @param string $uri
     * @return array|null
     */
    public function getModuleByURI(string $uri): ?array
    {
        $this->_db->sql = "SELECT dbm.*
                            FROM $this->_table_name AS dbm
                            WHERE dbm.md_url = :mod_id
                                AND dbm.md_active = 1";
        $this->_db->execute(
            [
                ':mod_id' => $uri,
            ]
        );
        return $this->_db->fetch();
    }
}
