<?php

use app\constant\MimeType;
use app\db\MyDB;

abstract class Model
{
    /**
     * @var MyDB
     */
    protected $_db;

    protected $_table_name = ''; // таблица с данными
    protected $_table_fields = []; // поля, доступные для редактирования
    protected $_table_pk = 'id'; // первичный ключ
    protected $_table_order = 'order'; // поле сортировки
    protected $_table_active = 'active'; // поле активности
    protected $_tables_related = [];
    protected $_files_dir = 'files'; // директория для привязанных файлов
    protected $_pager; // листалка для многостраничной выборки

    /**
     * @param MyDB $db
     */
    public function __construct(MyDB $db)
    {
        $this->_db = $db;
        $this->_pager = new SQLPager();
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        $this->_db->sql = "SELECT * FROM $this->_table_name\n";
        if ($this->_table_order) {
            $this->_db->sql .= "ORDER BY $this->_table_order ASC\n";
        }
        $this->_db->exec();
        return $this->_db->fetchAll();
    }

    public function getActive(): array
    {
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

    /**
     *
     * @param array $filter
     *
     * @return array
     */
    public function getItemsByFilter(array $filter): array
    {
        $out = [
            'fields' => '*',
            'join' => [],
            'where' => [],
            'having' => [],
            'limit' => 20,
            'offset' => 0,
            'order' => "$this->_table_order ASC",
            'groupby' => [],
            'binds' => [],
            'items' => [],
            'total' => 0,
        ];

        foreach ($filter as $k => $f) {
            if (isset($out[$k])) {
                $out[$k] = $f;
            } else {
                $out['where'][] = $f;
            }
        }
        $this->_pager->setParam('limit', $out['limit']);
        $out['offset'] = $this->_pager->getParam('offset');
        $out['binds'][':offset'] = $out['offset'];
        $out['binds'][':limit'] = $out['limit'];

        $this->_db->sql = "SELECT SQL_CALC_FOUND_ROWS {$out['fields']}
                            FROM $this->_table_name t "
            . (!empty($out['join']) ? implode("\n", $out['join']) : '') . PHP_EOL
            . (!empty($out['where']) ? ('WHERE ' . implode("\n AND ", $out['where'])) : '') . PHP_EOL
            . (!empty($out['having']) ? ('HAVING ' . implode("\n AND ", $out['having'])) : '') . PHP_EOL
            . (!empty($out['groupby']) ? ('GROUP BY ' . implode(", ", $out['groupby'])) : '') . PHP_EOL
            . "ORDER BY {$out['order']}
            LIMIT :offset, :limit";
        $this->_db->execute($out['binds']);
        $out['items'] = $this->_db->fetchAll();
        $this->_db->sql = "SELECT FOUND_ROWS() AS cnt";
        $this->_db->exec();
        $row = $this->_db->fetch();
        $out['total'] = $row['cnt'];
        $this->_pager->setParam('total', $out['total']);

        return $out;
    }

    public function getPager($show_selector = false, $show_total = false): string
    {
        return $this->_pager->getHTML($show_selector, $show_total);
    }

    public function getItemByPk($id)
    {
        $this->_db->sql = "SELECT * FROM $this->_table_name WHERE $this->_table_pk = :id";

        $this->_db->execute(
            [
                ':id' => (int) $id,
            ]
        );
        return $this->_db->fetch();
    }

    /**
     * @param int $id
     * @param array $values
     * @param array|null $files
     *
     * @return int|null
     */
    public function updateByPk(int $id, $values = [], $files = []): ?int
    {
        $new_fields_places = [];
        $new_fields_values = [
            ':primary_key' => $id,
        ];
        foreach ($values as $k => $v) {
            if (in_array($k, $this->_table_fields, true)) {
                $fieldValue = $v !== null ? trim(preg_replace('/\s+/', ' ', $v)) : null;
                $new_fields_places[] = "$k = :$k";
                $new_fields_values[':' . $k] = $fieldValue;
            }
        }
        if (!empty($new_fields_places)) {
            $this->_db->sql = "UPDATE $this->_table_name
                            SET " . implode(",\n", $new_fields_places) . "
                            WHERE $this->_table_pk = :primary_key";

            $result = $this->_db->execute($new_fields_values);
            if ($result) {
                if (!empty($files)) {
                    foreach ($files as $file_field => $file) {
                        $this->saveFile($id, $file_field, $file);
                    }
                }

                return $id;
            }
        }

        return null;
    }

    public function insert($values = [], $files = []): ?int
    {
        $new_fields_places = [];
        $new_fields_values = [];
        foreach ($values as $k => $v) {
            if (in_array($k, $this->_table_fields, true)) {
                $fieldValue = $v !== null ? trim(preg_replace('/\s+/', ' ', $v)) : null;
                $new_fields_places[] = "$k = :$k";
                $new_fields_values[':' . $k] = $fieldValue;
            }
        }
        if (!empty($new_fields_places)) {
            $this->_db->sql = "INSERT INTO $this->_table_name
                            SET " . implode(",\n", $new_fields_places);

            $result = $this->_db->execute($new_fields_values);
            if ($result) {
                $id = $this->_db->getLastInserted();
                if (!empty($files)) {
                    foreach ($files as $file_field => $file) {
                        $this->saveFile($id, $file_field, $file);
                    }
                }

                return $id;
            }
        }

        return null;
    }

    /**
     * Количество строк в таблице
     * @return int
     */
    public function getCount(): int
    {
        $this->_db->sql = "SELECT COUNT(1) AS cnt FROM $this->_table_name";
        $this->_db->exec();
        return $this->_db->fetchCol();
    }

    /**
     * Удалить строку по ID
     *
     * @param int $id
     *
     * @return PDOStatement
     */
    public function deleteByPk($id)
    {
        $this->_db->sql = "DELETE FROM $this->_table_name WHERE $this->_table_pk = :id";

        return $this->_db->execute(
            [
                ':id' => $id,
            ]
        );
    }

    public function truncate(): PDOStatement
    {
        $this->_db->sql = "TRUNCATE TABLE $this->_table_name";
        return $this->_db->exec();
    }

    public function optimize(): PDOStatement
    {
        $this->_db->sql = "OPTIMIZE TABLE $this->_table_name";
        return $this->_db->exec();
    }

    public function saveFile($id, $file_field, $file): void
    {
        if (!empty($file) && $file['error'] == 0) {
            $mime = new MimeType($file['type']);
            $filename = md5_file($file['tmp_name']) . '.' . $mime->getDefaultExtension();
            if (!file_exists($this->_files_dir)) {
                mkdir($this->_files_dir);
            }
            move_uploaded_file($file['tmp_name'], $this->_files_dir . "/$filename");
            $this->updateByPk($id, [(string) $file_field => $filename]);
        }
    }

    public function getDefault(): array
    {
        $out = [
            $this->_table_pk => 'новый',
        ];
        foreach ($this->_table_fields as $fld) {
            $out[$fld] = null;
        }
        $out[$this->_table_order] = 10;
        $out[$this->_table_active] = 1;
        return $out;
    }

    public function escape(string $txt): string
    {
        return $this->_db->getEscapedString(trim($txt));
    }

    protected function addRelatedTable(string $tableName): void
    {
        $tableName = trim($tableName);
        if ($tableName !== '') {
            $this->_tables_related[$tableName] = $this->_db->getTableName($tableName);
        }
    }

    public function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function getUserId(): int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 1;
    }
}
