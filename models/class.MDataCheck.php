<?php

class MDataCheck extends Model {

    const ENTITY_POINTS = 'pagepoints';
    const ENTITY_CITIES = 'pagecity';
    const ENTITY_CANDIDATES = 'candidate_points';
    const ENTITY_BLOG = 'blogentries';

    protected $_table_pk = 'dc_id';
    protected $_table_order = 'dc_id';
    protected $_table_active = 'dc_id';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('data_check');
        $this->_table_fields = array(
            'dc_type',
            'dc_field',
            'dc_item_id',
            'dc_date',
            'dc_result',
        );
        parent::__construct($db);
    }

    public function markChecked($type, $ptid, $field, $result) {
        $this->_db->sql = "INSERT INTO $this->_table_name SET
                            dc_type = :type,
                            dc_field = :field,
                            dc_item_id = :ptid,
                            dc_date = NOW(),
                            dc_result = :result1
                            ON DUPLICATE KEY UPDATE
                            dc_date = NOW(),
                            dc_result = :result2";
        $this->_db->execute(array(
            ':type' => $type,
            ':field' => $field,
            ':ptid' => $ptid,
            ':result1' => $result,
            ':result2' => $result,
        ));
        $dcid = $this->_db->getLastInserted();
        return $dcid;
    }

    /**
     * Удалить связку из истории проверок
     * @param string $entity
     * @param int $id
     */
    public function deleteChecked($entity, $id) {
        $this->_db->sql = "DELETE FROM $this->_table_name 
                            WHERE dc_type = :type
                            AND dc_item_id = :id";
        $this->_db->execute(array(
            ':type' => $entity,
            ':id' => $id,
        ));
    }

}
