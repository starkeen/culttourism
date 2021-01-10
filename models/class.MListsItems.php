<?php

use app\db\MyDB;

class MListsItems extends Model
{
    protected $_table_pk = 'li_id';
    protected $_table_order = 'li_order';
    protected $_table_active = 'li_active';

    private $_list_id;

    public function __construct(MyDB $db, $lid = 0)
    {
        $this->_table_name = $db->getTableName('lists_items');
        $this->_table_fields = [
            'li_ls_id',
            'li_pt_id',
            'li_order',
            'li_active',
        ];
        $this->_list_id = (int) $lid;
        parent::__construct($db);
        $this->addRelatedTable('lists');
        $this->addRelatedTable('pagepoints');
        $this->addRelatedTable('pagecity');
        $this->addRelatedTable('region_url');
        $this->addRelatedTable('ref_pointtypes');
    }

    public function setField($field, $pt_id, $val)
    {
        if (in_array($field, $this->_table_fields, true)) {
            $row = $this->getRowForPointId($pt_id);
            $this->updateByPk(
                $row['li_id'],
                [
                    $field => $val,
                ]
            );
            $nrow = $this->getItemByPk($row['li_id']);
            $newval = $nrow[$field];
        } else {
            $newval = null;
        }
        return $newval;
    }

    /**
     * Находим в этом списке объект и получаем айди его записи в таблице
     *
     * @param int $point_id
     *
     * @return int
     */
    public function getRowForPointId($point_id)
    {
        $this->_db->sql = "SELECT li_id FROM $this->_table_name WHERE li_ls_id = '$this->_list_id' AND li_pt_id = :ptid";
        $this->_db->execute(
            [
                ':ptid' => $point_id,
            ]
        );
        return $this->_db->fetch();
    }

    /**
     * Списки, где участвует эта точка
     *
     * @param int $point_id
     *
     * @return array
     */
    public function getListsForPointId(int $point_id)
    {
        $this->_db->sql = "SELECT *
                            FROM $this->_table_name li
                                LEFT JOIN {$this->_tables_related['lists']} ls ON ls.ls_id = li.li_ls_id
                            WHERE li_pt_id = :ptid
                                AND ls.ls_active = 1
                                AND li.li_active = 1";
        $this->_db->execute(
            [
                ':ptid' => $point_id,
            ]
        );
        return $this->_db->fetchAll();
    }

    public function getSuggestion($name)
    {
        $this->_db->sql = "SELECT *
                            FROM {$this->_tables_related['pagepoints']} pt
                                LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pt.pt_citypage_id
                            WHERE pt.pt_name LIKE :name
                                AND pt.pt_active = 1
                                AND pt.pt_id NOT IN (SELECT li_pt_id FROM $this->_table_name WHERE li_ls_id = '$this->_list_id')
                            ORDER BY pt.pt_name";
        $this->_db->execute(
            [
                ':name' => '%' . $name . '%',
            ]
        );
        return $this->_db->fetchAll();
    }

    public function getAll(): array
    {
        $this->_db->sql = "SELECT li.*, pt.*, pc.*, rt.*,
                                UNIX_TIMESTAMP(pt.pt_lastup_date) AS last_update,
                                CHAR_LENGTH(TRIM(pt.pt_description)) AS len_descr,
                                CONCAT(ru.url, '/') AS url_region,
                                CONCAT(ru.url, '/', pt.pt_slugline, '.html') AS url_canonical
                            FROM $this->_table_name li
                                LEFT JOIN {$this->_tables_related['pagepoints']} pt ON pt.pt_id = li.li_pt_id
                                    LEFT JOIN {$this->_tables_related['ref_pointtypes']} rt ON rt.tp_id = pt.pt_type_id
                                    LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pt.pt_citypage_id
                                        LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc.pc_url_id
                            WHERE li_ls_id = '$this->_list_id'
                            GROUP BY pt.pt_id
                            ORDER BY $this->_table_order ASC, pt.pt_rank DESC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function getActive(): array
    {
        $this->_db->sql = "SELECT li.*, pt.*, pc.*,
                                UNIX_TIMESTAMP(pt.pt_lastup_date) AS last_update,
                                CONCAT(ru.url, '/') AS url_region,
                                CONCAT(ru.url, '/', pt.pt_slugline, '.html') AS url_canonical
                            FROM $this->_table_name li
                                LEFT JOIN {$this->_tables_related['pagepoints']} pt ON pt.pt_id = li.li_pt_id
                                    LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pt.pt_citypage_id
                                        LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc.pc_url_id
                            WHERE li_ls_id = '$this->_list_id'
                                AND li.li_active = 1
                                AND pt.pt_active = 1
                            GROUP BY pt.pt_id
                            ORDER BY $this->_table_order ASC, pt.pt_rank DESC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function getPointsInList($list_id)
    {
        $this->_db->sql = "SELECT pp.*,
                                0 AS obj_selected,
                                CONCAT(:url_root1, ru.url, '/') AS cityurl,
                                CONCAT(:url_root2, ru.url, '/', pp.pt_slugline, '.html') AS objurl,
                                CONCAT(ru.url, '/', pp.pt_slugline, '.html') AS objuri
                            FROM $this->_table_name li
                                LEFT JOIN {$this->_tables_related['pagepoints']} AS pp ON pp.pt_id = li.li_pt_id
                                    LEFT JOIN {$this->_tables_related['ref_pointtypes']} pt ON pt.tp_id = pp.pt_type_id
                                    LEFT JOIN {$this->_tables_related['pagecity']} pc ON pc.pc_id = pp.pt_citypage_id
                                        LEFT JOIN {$this->_tables_related['region_url']} ru ON ru.uid = pc.pc_url_id
                            WHERE pp.pt_active = 1
                                AND pt_latitude != 0
                                AND pt_longitude != 0
                                AND li.li_active
                                AND li.li_ls_id = :list_id
                            ORDER BY pt.tr_order DESC, pp.pt_rank
                            LIMIT 300";

        $this->_db->execute(
            [
                ':list_id' => $list_id,
                ':url_root1' => GLOBAL_URL_ROOT,
                ':url_root2' => GLOBAL_URL_ROOT,
            ]
        );
        return $this->_db->fetchAll();
    }

    public function deleteByPk(int $id): ?int
    {
        return $this->updateByPk($id, [$this->_table_active => 0]);
    }
}
