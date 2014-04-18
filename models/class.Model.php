<?php

class Model {

    protected $_db = null;
    protected $_table_name = ''; //таблица с данными
    protected $_table_fields = array(); //поля, доступные для редактирования
    protected $_table_pk = 'id'; //первичный ключ
    protected $_table_order = 'order'; //поле сортировки
    protected $_table_active = 'active'; //поле активности
    protected $_files_dir = 'files'; //директория для привязанных файлов

    public function __construct($db) {
        $this->_db = $db;
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
                $new_fields[] = "$k = '" . $this->_db->getEscapedString($v) . "'";
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
                $new_fields[] = "$k = '" . $this->_db->getEscapedString($v) . "'";
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

}

?>
