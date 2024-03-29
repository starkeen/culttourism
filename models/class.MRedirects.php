<?php

use app\cache\Cache;
use app\db\MyDB;
use config\CachesConfig;

class MRedirects extends Model
{
    protected $_table_pk = 'rd_id';
    protected $_table_order = 'rd_order';
    protected $_table_active = 'rd_active';

    private Cache $cache;

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('redirects');
        $this->_table_fields = [
            'rd_from',
            'rd_to',
            'rd_order',
            'rd_active',
        ];
        parent::__construct($db);
        $this->cache = Cache::i(CachesConfig::REDIRECTS);
    }

    public function getActive(): array
    {
        $redirects = $this->cache->get('active');
        if (empty($redirects)) {
            $redirects = parent::getActive();
            $this->cache->put('active', $redirects);
        }
        return $redirects;
    }
}
