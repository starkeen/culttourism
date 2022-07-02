<?php

declare(strict_types=1);

namespace app\crontab;

use app\db\exceptions\MyPDOException;
use app\db\MyDB;

class RankerCommand extends AbstractCrontabCommand
{
    private MyDB $db;

    public function __construct(MyDB $db)
    {
        $this->db = $db;
    }

    /**
     * @return void
     * @throws MyPDOException
     */
    public function run(): void
    {
        $dbp = $this->db->getTableName('pagepoints');
        $dbc = $this->db->getTableName('pagecity');
        $dbsp = $this->db->getTableName('statpoints');
        $dbsc = $this->db->getTableName('statcity');

        $this->db->sql = "UPDATE $dbp pp SET pp.pt_cnt_shows = pp.pt_cnt_shows + 
            (SELECT count(sp.sp_id) as cnt FROM $dbsp sp WHERE sp.sp_pagepoint_id = pp.pt_id)";
        $this->db->exec();

        $this->db->sql = "TRUNCATE TABLE $dbsp";
        $this->db->exec();

        $this->db->sql = "UPDATE $dbp pp
                            SET
                                pp.pt_order = NULL,
                                pp.pt_rank = 1000 * pp.pt_cnt_shows / (DATEDIFF(now(), pp.pt_create_date) + 1) + 100 * pp.pt_is_best
                          ";
        $this->db->exec();
        $pdo = $this->db->getPDO();
        $sql = "
                            SET @counter = 0;
                            UPDATE $dbp 
                            SET pt_order = @counter := @counter + 1
                            WHERE pt_deleted_at IS NULL
                            ORDER BY pt_rank DESC;
                          ";
        $pdo->exec($sql);

        $this->db->sql = "UPDATE $dbc pc SET pc.pc_cnt_shows = pc.pc_cnt_shows + 
            (SELECT 100*count(sc.sc_id) as cnt FROM $dbsc sc WHERE sc.sc_citypage_id = pc.pc_id)";
        $this->db->exec();

        $this->db->sql = "TRUNCATE TABLE $dbsc";
        $this->db->exec();

        $this->db->sql = "UPDATE $dbc pc SET pc.pc_rank = 100*pc.pc_cnt_shows/(DATEDIFF(now(),pc.pc_add_date)+1)";
        $this->db->exec();
    }
}
