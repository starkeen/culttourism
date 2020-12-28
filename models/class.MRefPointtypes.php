<?php

use app\db\MyDB;

class MRefPointtypes extends Model
{
    protected $_table_pk = 'tp_id';
    protected $_table_order = 'tr_sight desc, tr_order';
    protected $_table_active = 'tp_active';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('ref_pointtypes');
        $this->_table_fields = [
            'tp_name',
            'tp_short',
            'tp_icon',
            'tr_sight',
            'tr_order',
            'tp_active',
        ];
        parent::__construct($db);
    }

    /**
     * Маркеры точек
     * @return array
     */
    public function getMarkers(): array
    {
        $out = [];
        foreach ($this->getAll() as $pt) {
            $out[$pt['tp_id']] = array_map(static function ($item) {
                return trim($item);
            }, explode(',', $pt['tp_markers']));
        }

        return $out;
    }
}
