<?php

use app\db\FactoryDB;

class Users
{
    public static function getAllUsers()
    {
        $db = FactoryDB::db();
        $dbu = $db->getTableName('users');
        $db->sql = "SELECT us_id, us_name
                    FROM  $dbu us
                    ORDER BY us_name";
        if ($db->exec()) {
            while ($row = $db->fetch()) {
                $users[$row['us_id']] = $row;
            }
            return $users;
        } else {
            return false;
        }
    }

    public static function getUserProfile($id)
    {
        $db = FactoryDB::db();
        $dbu = $db->getTableName('users');
        $dbuc = $db->getTableName('userscontacts');
        $dbul = $db->getTableName('userlevels');
        $dbrc = $db->getTableName('ref_contacts');
        $db->sql = "SELECT us.us_id, us.us_name, ul.ul_title
                    FROM  $dbu us
                    LEFT JOIN $dbul ul ON ul.ul_id = us.us_level_id
                    WHERE us_id = '$id'
                    LIMIT 1";
        if ($db->exec()) {
            $user = $db->fetch();
            $db->sql = "SELECT uc.uc_id, uc.uc_value, rc_icon, rc_name, rc_id,
                        REPLACE(rc.rc_link, '{VALUE}', uc.uc_value) link
                        FROM  $dbuc uc
                        LEFT JOIN $dbrc rc ON rc.rc_id = uc.uc_cnt_id
                        WHERE uc.uc_us_id = '$id'
                        AND rc.rc_active = 1";
            if ($db->exec()) {
                while ($row = $db->fetch()) {
                    $user['contacts'][$row['uc_id']]['link'] = $row['link'];
                    $user['contacts'][$row['uc_id']]['icon'] = $row['rc_icon'];
                    $user['contacts'][$row['uc_id']]['value'] = $row['uc_value'];
                    $user['contacts'][$row['uc_id']]['name'] = $row['rc_name'];
                    $user['contacts'][$row['uc_id']]['rcid'] = $row['rc_id'];
                }
            }
            return $user;
        } else {
            return false;
        }
    }

    public static function getRefContacts()
    {
        $db = FactoryDB::db();
        $dbrc = $db->getTableName('ref_contacts');
        $db->sql = "SELECT rc_icon, rc_name, rc_id
                    FROM $dbrc rc
                    WHERE rc.rc_active = 1";
        if ($db->exec()) {
            while ($row = $db->fetch()) {
                $contacts[$row['rc_id']] = $row;
            }
            return $contacts;
        } else {
            return false;
        }
    }

    public static function saveUserProfile($uid, $uname, $ucontacts = [])
    {
        $db = FactoryDB::db();
        $dbu = $db->getTableName('users');
        $dbuc = $db->getTableName('userscontacts');
        $db->sql = "UPDATE $dbu SET us_name='$uname' WHERE us_id='$uid'";
        if ($db->exec()) {
            $db->sql = "DELETE FROM $dbuc WHERE uc_us_id = '$uid'";
            $db->exec();
            $db->sql = "INSERT INTO $dbuc (uc_us_id, uc_cnt_id, uc_value) VALUES\n";
            $acont_temp = [];
            foreach ($ucontacts as $cnt => $val) {
                $acont_temp[] = "('$uid', '$cnt', '$val')";
            }
            $db->sql .= implode(', ', $acont_temp);
            if (!empty($acont_temp) && $db->exec()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

