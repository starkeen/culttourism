<?php

class MPages extends Model {

    protected $_table_pk = 'pg_id';
    protected $_table_order = 'pg_order';
    protected $_table_active = 'pg_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('pages');
        $this->_table_fields = array(
            'pg_id',
            'pg_pid',
            'pg_md_id',
            'pg_h1',
            'pg_slugline',
            'pg_keywords',
            'pg_description',
            'pg_text',
            'pg_create_date',
            'pg_create_user',
            'pg_modify_date',
            'pg_modify_user',
            'pg_robots',
            'pg_order',
            'pg_active',
        );
        parent::__construct($db);
    }

}
