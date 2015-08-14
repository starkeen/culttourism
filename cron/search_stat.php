<?php

$dbsc = $db->getTableName('search_cache');
$dbsr = $db->getTableName('search_requests');

$db->sql = "INSERT INTO $dbsr (sr_phrase, sr_weight)
                (
                    SELECT sc_query, count(*) AS cnt
                    FROM $dbsc
                    WHERE sc_session != '2'
                        AND sc_sr_id IS NULL
                    GROUP BY sc_query
                    ORDER BY cnt DESC, sc_query)
            ON DUPLICATE KEY UPDATE
                sr_weight = sr_weight + (
                    SELECT count(*) AS cnt
                    FROM $dbsc
                    WHERE sc_query = sr_phrase
                        AND sc_session != '2'
                        AND sc_sr_id IS NULL
                    GROUP BY sc_session
                    ORDER BY cnt DESC
                    LIMIT 1
                )";
$db->exec();

$db->sql = "UPDATE $dbsc SET sc_sr_id = (SELECT sr_id FROM $dbsr WHERE sr_phrase = sc_query) WHERE sc_sr_id IS NULL";
//$db->showSQL();
$db->exec();
