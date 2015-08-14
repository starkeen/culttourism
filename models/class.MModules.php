<?php

class MModules extends Model {

    protected $_table_pk = 'md_id';
    protected $_table_order = 'md_id';
    protected $_table_active = 'md_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('modules');
        $this->_table_fields = array(
            'md_id',
            'md_pid',
            'md_name',
            'md_url',
            'md_title',
            'md_keywords',
            'md_description',
            'md_active',
            'md_counters',
            'md_pagecontent',
            'md_redirect',
            'md_sort',
            'md_css',
            'md_robots',
            'md_lastedit',
        );
        parent::__construct($db);
    }

}