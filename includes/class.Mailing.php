<?php

/**
 * Description of classMailing
 *
 * @author Andrey_Pns
 */
class Mailing {

    public function __construct() {
        //
    }

    private static function sendOnly($letter) {
        include_once(_DIR_ADDONS . "/phpmailer/class.phpmailer.php");
        $mailer = new PHPMailer();
        $mailer->IsSMTP();
        $mailer->ContentType = "text/html";
        $mailer->Mailer = "smtp";
        $mailer->SMTPAuth = true;
        $mailer->CharSet = "utf-8";
        $mailer->From = _MAIL_FROMADDR;
        //$mailer->FromName = iconv('utf-8', 'windows-1251', _MAIL_FROMNAME);
        $mailer->FromName = _MAIL_FROMNAME;
        $mailer->Host = _MAIL_HOST;
        $mailer->Username = _MAIL_USER;                  // SMTP username
        $mailer->Password = _MAIL_PASS;                  // SMTP password
        //$mailer->Subject  = iconv('utf-8', 'windows-1251', $letter['ml_theme']);
        $mailer->Subject = $letter['ml_theme'];
        //$mailer->Body     = iconv('utf-8', 'windows-1251', $letter['ml_text']);
        $mailer->Body = $letter['ml_text'];
        $mailer->AddAddress($letter['ml_adr_to']);
        $mailer->AddCustomHeader('X-Mailru-Msgtype: feedback');
        $mailer->Send();
        $mailer->ClearAddresses();
        $mailer->ClearAttachments();
    }

    private static function sendLetter($db, $ml_id) {
        $dbmp = $db->getTableName('mail_pool');
        $db->sql = "SELECT * FROM $dbmp WHERE ml_id = '$ml_id'";
        if ($db->exec()) {
            $letter = $db->fetch();
            self::sendOnly($letter);
            $db->sql = "UPDATE $dbmp SET ml_worked = 1, ml_inwork=0, ml_datesend=now() WHERE ml_id = '$ml_id'";
            return $db->exec();
        }
        else
            return FALSE;
    }

    public static function sendFromPool($limit = null) {
        global $db;
        $dbm = $db->getTableName('mail_pool');
        $db->sql = "SELECT ml_id FROM $dbm WHERE ml_worked = 0 AND ml_inwork = 0";
        if ($limit)
            $db->sql .= " LIMIT $limit";
        $db->exec();
        $pool = array();
        $cnt = 0;
        while ($ml = $db->fetch()) {
            $pool[] = $ml['ml_id'];
        }
        if (!empty($pool))
            foreach ($pool as $mid) {
                $db->exec("UPDATE $dbm SET ml_inwork = 1 WHERE ml_id = '$mid'");
                self::sendLetter($db, $mid);
                $db->exec("UPDATE $dbm SET ml_inwork = 0, ml_worked = 1 WHERE ml_id = '$mid'");
                $cnt++;
            }
        return $cnt;
    }

    public static function sendLetterInvite($to, $details) {
        $attrs['WHO_SEND'] = $details['owner_name'];
        $attrs['EVENT_LINK'] = $details['event_link'];
        $attrs['EVENT_TITLE'] = $details['ev_title'];
        $attrs['REQUEST_LINK'] = $details['request_link'];
        $letter = self::prepareLetter(1, $attrs);
        return self::sendInCache($to, $letter['mt_content'], $letter['mt_theme'], $_SESSION['user_id']);
    }

    private static function sendInCache($to, $text, $theme, $sender) {
        global $db;
        $text = mysql_real_escape_string($text);
        $dbmp = $db->getTableName('mail_pool');
        $db->sql = "INSERT INTO $dbmp
                    (ml_datecreate, ml_text, ml_adr_to, ml_theme, ml_inwork, ml_worked, ml_sender_id)
                    VALUES
                    (now(), '$text', '$to', '$theme', 0, 0, '$sender')";
        return $db->exec();
    }

    public static function sendImmediately($db, $to, $text, $theme) {
        $text = mysql_real_escape_string($text);
        $theme = mysql_real_escape_string($theme);
        $dbmp = $db->getTableName('mail_pool');
        $db->sql = "INSERT INTO $dbmp
                    (ml_datecreate, ml_text, ml_adr_to, ml_theme, ml_inwork, ml_worked, ml_sender_id)
                    VALUES
                    (now(), '$text', '$to', '$theme', 1, 0, 0)";
        $db->exec();
        $lt_id = $db->getLastInserted();
        return self::sendLetter($db, $lt_id);
    }

    private static function prepareLetter($tmpl_id, $elements = array()) {
        global $db;
        $dbmt = $db->getTableName('mail_templates');
        $db->sql = "SELECT * FROM $dbmt
                    WHERE mt_id = '$tmpl_id'
                    LIMIT 1";
        $db->exec();
        $template = $db->fetch();
        foreach ($elements as $elkey => $element) {
            $template['mt_content'] = str_replace("%$elkey%", $element, $template['mt_content']);
        }
        return $template;
    }

}

?>
