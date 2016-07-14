<?php

class MRedirects extends Model {

    protected $_table_pk = 'rd_id';
    protected $_table_order = 'rd_order';
    protected $_table_active = 'rd_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('redirects');
        $this->_table_fields = array(
            'rd_from',
            'rd_to',
            'rd_order',
            'rd_active',
        );
        parent::__construct($db);
        $this->cache = Cache::i('redirects');
    }

    public function getActive() {
        $redirs = $this->cache->get('active');
        if (empty($redirs)) {
            $redirs = parent::getActive();
            $this->cache->put('active', $redirs);
        }
        return $redirs;
    }

}
