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
        $mailer->FromName = _MAIL_FROMNAME;
        $mailer->Host = _MAIL_HOST;
        $mailer->Username = _MAIL_USER;                  // SMTP username
        $mailer->Password = _MAIL_PASS;                  // SMTP password
        $mailer->Subject = $letter['ml_theme'];
        $mailer->Body = $letter['ml_text'];
        if ($letter['ml_customheader'] != '') {
            $mailer->AddCustomHeader($letter['ml_customheader']);
            $listid = trim(str_replace('X-Mailru-Msgtype:', '', $letter['ml_customheader']));
            $mailer->AddCustomHeader("List-id: <list-$listid.culttourism.ru>");
        }
        $mailer->AddAddress($letter['ml_adr_to']);
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
        } else {
            return FALSE;
        }
    }

    public static function sendFromPool($limit = null) {
        global $db;
        $dbm = $db->getTableName('mail_pool');
        $db->sql = "SELECT ml_id FROM $dbm WHERE ml_worked = 0 AND ml_inwork = 0";
        if ($limit) {
            $db->sql .= " LIMIT $limit";
        }
        $db->exec();
        $pool = array();
        $cnt = 0;
        while ($ml = $db->fetch()) {
            $pool[] = $ml['ml_id'];
        }
        if (!empty($pool)) {
            foreach ($pool as $mid) {
                $db->exec("UPDATE $dbm SET ml_inwork = 1 WHERE ml_id = '$mid'");
                self::sendLetter($db, $mid);
                $db->exec("UPDATE $dbm SET ml_inwork = 0, ml_worked = 1 WHERE ml_id = '$mid'");
                $cnt++;
            }
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

    public static function sendLetterNewPassword($to, $details) {
        global $db;
        $attrs['REQUEST_LINK'] = _SITE_URL . 'request/' . $details['req_key'] . '/';
        $attrs['SITE_LINK'] = _SITE_URL;
        $letter = self::prepareLetter(5, $attrs);
        return self::sendImmediately($db, $to, $letter['mt_content'], $letter['mt_theme'], $letter['mt_custom_header']);
    }

    public static function sendLetterNewUser($to, $details) {
        global $db;
        $attrs['USER_NAME'] = $details['user_name'];
        $attrs['REQUEST_KEY'] = $details['request_key'];
        $attrs['USER_EMAIL'] = $to;
        $attrs['SITE_LINK'] = _SITE_URL;
        $letter = self::prepareLetter(2, $attrs);
        return self::sendImmediately($db, $to, $letter['mt_content'], $letter['mt_theme'], $letter['mt_custom_header']);
    }

    public static function sendLetterCommon($to, $type, $details) {
        global $db;
        $attrs = array();
        $attrs['SITE_LINK'] = _SITE_URL;
        $attrs['USER_IP'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cron';
        foreach ($details as $key => $val) {
            $attrs[strtoupper($key)] = $val;
        }
        if (!isset($attrs['USER_EMAIL']) || !$attrs['USER_EMAIL']) {
            $attrs['USER_EMAIL'] = $to;
        }
        $letter = self::prepareLetter($type, $attrs);
        return self::sendImmediately($db, $to, $letter['mt_content'], $letter['mt_theme'], $letter['mt_custom_header']);
    }

    public static function sendInCache($to, $text, $theme, $sender = null, $xheader = null) {
        global $db;
        $mp = new MMailPool($db);
        $mp->insert(array(
            'ml_datecreate' => date('Y-m-d H:i:s'),
            'ml_text' => trim($text),
            'ml_adr_to' => $to,
            'ml_theme' => trim($theme),
            'ml_inwork' => 0,
            'ml_worked' => 0,
            'ml_sender_id' => 0,
            'ml_customheader' => $xheader,
        ));
        return true;
    }

    public static function sendImmediately($db, $to, $text, $theme, $custom_header = '') {
        $mp = new MMailPool($db);
        $lt_id = $mp->insert(array(
            'ml_datecreate' => date('Y-m-d H:i:s'),
            'ml_text' => trim($text),
            'ml_adr_to' => $to,
            'ml_theme' => trim($theme),
            'ml_inwork' => 1,
            'ml_worked' => 0,
            'ml_sender_id' => 0,
            'ml_customheader' => $custom_header,
        ));
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
