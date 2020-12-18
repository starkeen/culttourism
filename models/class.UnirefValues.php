<?php

class UnirefValues extends Model
{
    protected $_table_pk = 'uv_id';
    protected $_table_order = 'uv_order';
    protected $_table_active = 'uv_active';
    private $_key_id = 0;

    public function __construct($db, $key_id)
    {
        $this->_table_name = $db->getTableName('uniref_values');
        $this->_table_fields = [
            'uv_uk_id',
            'uv_pid',
            'uv_title',
            'uv_title_short',
            'uv_code',
            'uv_color_text',
            'uv_color_bg',
            'uv_k',
            'uv_link',
            'uv_picture',
            'uv_photo',
            'uv_order',
            'uv_active',
        ];
        $this->_key_id = (int) $key_id;
        $this->_files_dir = _DIR_DATA . '/uniref/' . (int) $key_id . '/full';
        parent::__construct($db);
    }

    public function getAll(): array
    {
        $out = [];
        $this->_db->sql = "SELECT * FROM $this->_table_name
                            WHERE uv_uk_id = '$this->_key_id'
                                AND uv_pid = 0
                           ORDER BY $this->_table_order ASC, uv_title ASC";
        $this->_db->exec();
        while ($row = $this->_db->fetch()) {
            $out[$row['uv_id']] = $row;
            $out[$row['uv_id']]['subs'] = [];
        }
        $this->_db->sql = "SELECT * FROM $this->_table_name
                            WHERE uv_uk_id = '$this->_key_id'
                                AND uv_pid > 0
                           ORDER BY $this->_table_order ASC, uv_title ASC";
        $this->_db->exec();
        while ($row = $this->_db->fetch()) {
            $out[$row['uv_pid']]['subs'][$row['uv_id']] = $row;
        }
        return $out;
    }

    public function getActive(): array
    {
        $this->_db->sql = "SELECT * FROM $this->_table_name
                            WHERE uv_uk_id = '$this->_key_id'
                                AND $this->_table_active = 1
                           ORDER BY $this->_table_order ASC, uv_title ASC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function getActiveTree()
    {
        $out = [];
        $this->_db->sql = "SELECT * FROM $this->_table_name
                            WHERE `uv_uk_id` = '$this->_key_id'
                                AND $this->_table_active = 1
                                AND `uv_pid` IS NOT NULL
                                AND `uv_pid` > 0
                           ORDER BY $this->_table_order ASC, `uv_title` ASC";
        $this->_db->exec();
        $child = [];
        while ($row = $this->_db->fetch()) {
            $child[$row['uv_pid']][] = $row;
        }
        $this->_db->sql = "SELECT * FROM $this->_table_name
                            WHERE `uv_uk_id` = '$this->_key_id'
                                AND $this->_table_active = 1
                                AND `uv_id` IN (" . implode(',', array_keys($child)) . ")
                           ORDER BY $this->_table_order ASC, `uv_title` ASC";
        $this->_db->exec();
        while ($row = $this->_db->fetch()) {
            $row['subs'] = $child[$row['uv_id']];
            $out[] = $row;
        }
        return $out;
    }

    public function doResort()
    {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET uv_order = (SELECT @a:= @a + 10 FROM (SELECT @a:= 0) s)
                            WHERE uv_uk_id = '$this->_key_id'
                           ORDER BY uv_title";
        return $this->_db->exec();
    }

    public function deletePicture($id)
    {
        $item = $this->getItemByPk($id);
        $file = $this->_files_dir . "/{$item['uv_picture']}";
        if (file_exists($file)) {
            return $this->updateByPk($id, ['uv_picture' => '']);
            /*
              if (unlink($file)) {
              //
              } else {
              return false;
              }
             */
        } else {
            return false;
        }
    }

    public function deletePhoto($id)
    {
        $item = $this->getItemByPk($id);
        $file = $this->_files_dir . "/{$item['uv_photo']}";
        if (file_exists($file)) {
            return $this->updateByPk($id, ['uv_photo' => '']);
            /*
              if (unlink($file)) {
              //
              } else {
              return false;
              }
             */
        } else {
            return false;
        }
    }
}
