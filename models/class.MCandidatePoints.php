<?php

class MCandidatePoints extends Model {

    protected $_table_pk = 'cp_id';
    protected $_table_order = 'cp_date';
    protected $_table_active = 'cp_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('candidate_points');
        $this->_table_fields = array(
            'cp_date',
            'cp_title',
            'cp_text',
            'cp_city',
            'cp_addr',
            'cp_phone',
            'cp_web',
            'cp_worktime',
            'cp_email',
            'cp_sender',
            'cp_referer',
            'cp_type_id',
            'cp_citypage_id',
            'cp_latitude',
            'cp_longitude',
            'cp_source_id',
            'cp_state',
            'cp_active',
        );
        parent::__construct($db);
        $this->_addRelatedTable('uniref_values');
    }

    public function add($data) {
        $data['cp_date'] = $this->now();
        $data['cp_state'] = 3;
        $data['cp_active'] = 1;
        return $this->insert($data);
    }

}
