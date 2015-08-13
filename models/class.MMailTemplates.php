<?php

class MMailTemplates extends Model {

    protected $_table_pk = 'mt_id';
    protected $_table_order = 'mt_id';
    protected $_table_active = 'mt_id';

    public function __construct($db, $id = null) {
        $this->_table_name = $db->getTableName('mail_templates');
        $this->_table_fields = array(
            'mt_content',
            'mt_description',
            'mt_theme',
            'mt_custom_header',
        );
        parent::__construct($db);
    }

    public function getCompiled($id, $data = array()) {
        $text = parent::getItemByPk($id);
        foreach ($data as $k => $v) {
            $text = str_replace('%' . strtoupper($k) . '%', $v, $text);
        }
        return $text;
    }

}
