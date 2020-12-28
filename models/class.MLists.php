<?php

use app\db\MyDB;

class MLists extends Model
{
    protected $_table_pk = 'ls_id';
    protected $_table_order = 'ls_order';
    protected $_table_active = 'ls_active';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('lists');
        $this->_table_fields = [
            'ls_title',
            'ls_slugline',
            'ls_keywords',
            'ls_description',
            'ls_text',
            'ls_image',
            'ls_update_date',
            'ls_order',
            'ls_active',
        ];
        parent::__construct($db);
        $this->addRelatedTable('lists_items');
    }

    public function getItemBySlugLine(string $slug): array
    {
        $this->_db->sql = "SELECT ls.*,
                                UNIX_TIMESTAMP(ls.ls_update_date) AS last_update,
                                (SELECT COUNT(*) FROM {$this->_tables_related['lists_items']} WHERE li_ls_id = ls.ls_id) AS cnt,
                                CHAR_LENGTH(TRIM(ls_description)) AS len_descr,
                                CHAR_LENGTH(TRIM(ls_text)) AS len_text
                            FROM $this->_table_name ls
                            WHERE ls.ls_slugline = :slug
                                AND ls.ls_active = 1";
        $this->_db->execute(
            [
                ':slug' => $slug,
            ]
        );

        return $this->_db->fetch();
    }

    public function getAll(): array
    {
        $this->_db->sql = "SELECT ls.*,
                                UNIX_TIMESTAMP(ls.ls_update_date) AS last_update,
                                (SELECT COUNT(*) FROM {$this->_tables_related['lists_items']} WHERE li_ls_id = ls.ls_id) AS cnt,
                                CHAR_LENGTH(TRIM(ls_description)) AS len_descr,
                                CHAR_LENGTH(TRIM(ls_text)) AS len_text
                            FROM $this->_table_name ls
                            ORDER BY $this->_table_order ASC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function getActive(): array
    {
        $this->_db->sql = "SELECT ls.*,
                                UNIX_TIMESTAMP(ls.ls_update_date) AS last_update,
                                (SELECT COUNT(*) FROM {$this->_tables_related['lists_items']} WHERE li_ls_id = ls.ls_id) AS cnt,
                                CHAR_LENGTH(TRIM(ls_description)) AS len_descr,
                                CHAR_LENGTH(TRIM(ls_text)) AS len_text
                            FROM $this->_table_name ls
                            WHERE ls.ls_active = 1
                            ORDER BY $this->_table_order ASC";
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function deleteByPk($id)
    {
        return $this->updateByPk($id, [$this->_table_active => 0]);
    }

    /**
     * Заменяет все абсолютные ссылки относительными
     */
    public function repairLinksAbsRel(): void
    {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET ls_text = REPLACE(ls_text, '=\"http://" . _URL_ROOT . "/', '=\"/')";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name
                            SET ls_text = REPLACE(ls_text, '=\"https://" . _URL_ROOT . "/', '=\"/')";
        $this->_db->exec();
    }
}
