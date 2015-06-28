<?php

class Model {

    protected $_db = null;
    protected $_table_name = ''; //таблица с данными
    protected $_table_fields = array(); //поля, доступные для редактирования
    protected $_table_pk = 'id'; //первичный ключ
    protected $_table_order = 'order'; //поле сортировки
    protected $_table_active = 'active'; //поле активности
    protected $_tables_related = array();
    protected $_files_dir = 'files'; //директория для привязанных файлов
    protected $_pager; //листалка для многостраничной выборки

    public function __construct($db) {
        $this->_db = $db;
        $this->_pager = new SQLPager();
    }

    public function getAll() {
        $this->_db->sql = "SELECT * FROM $this->_table_name\n";
        if ($this->_table_order) {
            $this->_db->sql .= "ORDER BY $this->_table_order ASC\n";
        }
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function getActive() {
        $this->_db->sql = "SELECT * FROM $this->_table_name\n";
        if ($this->_table_active) {
            $this->_db->sql .= "WHERE $this->_table_active = 1\n";
        }
        if ($this->_table_order) {
            $this->_db->sql .= "ORDER BY $this->_table_order ASC\n";
        }
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function getItemsByFilter($filter) {
        $out = array(
            'fields' => '*',
            'join' => array(),
            'where' => array(),
            'having' => array(),
            'limit' => 20,
            'offset' => 0,
            'order' => "$this->_table_order ASC",
            'groupby' => array(),
            'items' => array(),
            'total' => 0,
        );

        foreach ($filter as $k => $f) {
            if (isset($out[$k])) {
                $out[$k] = $f;
            } else {
                $out['where'][] = $f;
            }
        }
        $this->_pager->setParam('limit', $out['limit']);
        $out['offset'] = $this->_pager->getParam('offset');

        $this->_db->sql = "SELECT SQL_CALC_FOUND_ROWS {$out['fields']}
                            FROM $this->_table_name t
                                " . (!empty($out['join']) ? implode("\n", $out['join']) : '') . "
                            " . (!empty($out['where']) ? ('WHERE ' . implode("\n AND ", $out['where'])) : '') . "
                            " . (!empty($out['having']) ? ('HAVING ' . implode("\n AND ", $out['having'])) : '') . "
                            " . (!empty($out['groupby']) ? ('GROUP BY ' . implode(", ", $out['groupby'])) : '') . "
                            ORDER BY {$out['order']}
                            LIMIT {$out['offset']}, {$out['limit']}";
        //$this->_db->showSQL();exit();
        $this->_db->exec();
        $out['items'] = $this->_db->fetchAll();
        $this->_db->sql = "SELECT FOUND_ROWS() AS cnt";
        $this->_db->exec();
        $row = $this->_db->fetch();
        $out['total'] = $row['cnt'];
        $this->_pager->setParam('total', $out['total']);
        return $out;
    }

    public function getPager($show_selector = false, $show_total = false) {
        return $this->_pager->getHTML($show_selector, $show_total);
    }

    public function getItemByPk($id) {
        $id = intval($id);
        $this->_db->sql = "SELECT * FROM $this->_table_name WHERE $this->_table_pk = '$id'";
        $this->_db->exec();
        return $this->_db->fetch();
    }

    public function updateByPk($id, $values = array(), $files = array()) {
        $id = intval($id);
        $new_fields = array();
        foreach ($values as $k => $v) {
            if (array_search($k, $this->_table_fields) !== false) {
                $new_fields[] = "$k = '" . $this->_db->getEscapedString(trim(preg_replace('/\s+/', ' ', $v))) . "'";
            }
        }
        if (!empty($new_fields)) {
            $this->_db->sql = "UPDATE $this->_table_name
                            SET\n";
            $this->_db->sql .= implode(",\n", $new_fields) . "\n";
            $this->_db->sql .= "WHERE $this->_table_pk = '$id'";

            if ($this->_db->exec()) {
                if (!empty($files)) {
                    foreach ($files as $file_field => $file) {
                        $this->saveFile($id, $file_field, $file);
                    }
                    return $id;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function insert($values = array(), $files = array()) {
        $new_fields = array();
        foreach ($values as $k => $v) {
            if (array_search($k, $this->_table_fields) !== false) {
                $new_fields[] = "$k = '" . $this->_db->getEscapedString(trim(preg_replace('/\s+/', ' ', $v))) . "'";
            }
        }
        if (!empty($new_fields)) {
            $this->_db->sql = "INSERT INTO $this->_table_name
                            SET\n";
            $this->_db->sql .= implode(",\n", $new_fields) . "\n";
            if ($this->_db->exec()) {
                $id = $this->_db->getLastInserted();
                if (!empty($files)) {
                    foreach ($files as $file_field => $file) {
                        $this->saveFile($id, $file_field, $file);
                    }
                    return $id;
                } else {
                    return $id;
                }
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function deleteByPk($id) {
        $this->_db->sql = "DELETE FROM $this->_table_name WHERE $this->_table_pk = '$id'";
        return $this->_db->exec();
    }

    public function truncate() {
        $this->_db->sql = "TRUNCATE TABLE $this->_table_name";
        return $this->_db->exec();
    }

    public function saveFile($id, $file_field, $file) {
        if (!empty($file) && $file['error'] == 0) {
            $filename = md5_file($file['tmp_name']) . '.' . Helper::getExt($file['type']);
            if (!file_exists($this->_files_dir)) {
                mkdir($this->_files_dir);
            }
            move_uploaded_file($file['tmp_name'], $this->_files_dir . "/$filename");
            $this->updateByPk($id, array("$file_field" => $filename));
        }
    }

    public function getDefault() {
        $out = array(
            $this->_table_pk => 'новый',
        );
        foreach ($this->_table_fields as $fld) {
            $out[$fld] = null;
        }
        $out[$this->_table_order] = 10;
        $out[$this->_table_active] = 1;
        return $out;
    }

    public function escape($txt) {
        return $this->_db->getEscapedString(trim($txt));
    }

    protected function _addRelatedTable($tablename) {
        $tablename = trim($tablename);
        if ($tablename != '') {
            $this->_tables_related[$tablename] = $this->_db->getTableName($tablename);
        }
    }

    public function now() {
        return date('Y-m-d H:i:s');
    }

    public function getUserId() {
        return intval($_SESSION['user_id']);
    }

}
