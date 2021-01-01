<?php

use app\db\FactoryDB;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Description of classMailing
 *
 * @author Andrey_Pns
 */
class Mailing
{
    /**
     * @param array $letter
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private static function sendOnly(array $letter): void
    {
        $mailer = new PHPMailer();
        $mailer->isSMTP();
        $mailer->isHTML();
        $mailer->SMTPAuth = true;
        $mailer->CharSet = 'utf-8';
        $mailer->setLanguage('ru');
        $mailer->setFrom(GLOBAL_MAIL_FROMADDR, GLOBAL_MAIL_FROMNAME);
        $mailer->Host = GLOBAL_MAIL_HOST;
        $mailer->SMTPSecure = 'ssl';
        $mailer->Port = 465;
        $mailer->Username = GLOBAL_MAIL_USER;                  // SMTP username
        $mailer->Password = GLOBAL_MAIL_PASS;                  // SMTP password
        $mailer->Subject = $letter['ml_theme'];
        $mailer->Body = $letter['ml_text'];
        if ($letter['ml_customheader'] !== '') {
            $mailer->AddCustomHeader($letter['ml_customheader']);
            $listId = trim(str_replace('X-Mailru-Msgtype:', '', $letter['ml_customheader']));
            $mailer->AddCustomHeader("List-id: <list-$listId.culttourism.ru>");
        }
        $mailer->AddAddress($letter['ml_adr_to']);
        $mailer->Send();
        $mailer->ClearAddresses();
        $mailer->ClearAttachments();
    }

    private static function sendLetter($db, $ml_id)
    {
        $mp = new MMailPool($db);
        $letter = $mp->getItemByPk($ml_id);
        $mp->markInwork($ml_id);
        self::sendOnly($letter);
        return $mp->markWorked($ml_id);
    }

    public static function sendFromPool($limit = 20)
    {
        $db = FactoryDB::db();
        $mp = new MMailPool($db);
        $pool = $mp->getPortion($limit);
        $cnt = 0;
        if (!empty($pool)) {
            foreach ($pool as $m) {
                self::sendLetter($db, $m['ml_id']);
                $cnt++;
            }
        }
        return $cnt;
    }

    public static function sendLetterInvite($to, $details)
    {
        $attrs['WHO_SEND'] = $details['owner_name'];
        $attrs['EVENT_LINK'] = $details['event_link'];
        $attrs['EVENT_TITLE'] = $details['ev_title'];
        $attrs['REQUEST_LINK'] = $details['request_link'];
        $letter = self::prepareLetter(1, $attrs);
        return self::sendInCache($to, $letter['mt_content'], $letter['mt_theme'], $_SESSION['user_id']);
    }

    public static function sendLetterNewPassword($to, $details)
    {
        $db = FactoryDB::db();
        $attrs['REQUEST_LINK'] = _SITE_URL . 'request/' . $details['req_key'] . '/';
        $attrs['SITE_LINK'] = _SITE_URL;
        $letter = self::prepareLetter(5, $attrs);
        return self::sendImmediately($db, $to, $letter['mt_content'], $letter['mt_theme'], $letter['mt_custom_header']);
    }

    public static function sendLetterNewUser($to, $details)
    {
        $db = FactoryDB::db();
        $attrs['USER_NAME'] = $details['user_name'];
        $attrs['REQUEST_KEY'] = $details['request_key'];
        $attrs['USER_EMAIL'] = $to;
        $attrs['SITE_LINK'] = _SITE_URL;
        $letter = self::prepareLetter(2, $attrs);
        return self::sendImmediately($db, $to, $letter['mt_content'], $letter['mt_theme'], $letter['mt_custom_header']);
    }

    /**
     * @param $to
     * @param $type
     * @param $details
     *
     * @return PDOStatement
     */
    public static function sendLetterCommon($to, $type, $details)
    {
        $db = FactoryDB::db();
        $attrs = [];
        $attrs['SITE_LINK'] = _SITE_URL;
        $attrs['USER_IP'] = $_SERVER['REMOTE_ADDR'] ?? 'cron';
        $attrs['NOW'] = date('d.m.Y H:i:s');
        foreach ($details as $key => $val) {
            $attrs[strtoupper($key)] = $val;
        }
        if (!isset($attrs['USER_EMAIL']) || !$attrs['USER_EMAIL']) {
            $attrs['USER_EMAIL'] = $to;
        }
        $letter = self::prepareLetter($type, $attrs);
        return self::sendImmediately($db, $to, $letter['mt_content'], $letter['mt_theme'], $letter['mt_custom_header']);
    }

    public static function sendInCache($to, $text, $theme, $sender = null, $xheader = null)
    {
        $db = FactoryDB::db();
        $mp = new MMailPool($db);
        $lt_id = $mp->insert(
            [
                'ml_datecreate' => $mp->now(),
                'ml_text' => trim($text),
                'ml_adr_to' => $to,
                'ml_theme' => trim($theme),
                'ml_inwork' => 0,
                'ml_worked' => 0,
                'ml_sender_id' => 0,
                'ml_customheader' => $xheader,
            ]
        );
        return $lt_id;
    }

    public static function sendImmediately($db, $to, $text, $theme, $custom_header = '')
    {
        $mp = new MMailPool($db);
        $lt_id = $mp->insert(
            [
                'ml_datecreate' => $mp->now(),
                'ml_text' => trim($text),
                'ml_adr_to' => $to,
                'ml_theme' => trim($theme),
                'ml_inwork' => 0,
                'ml_worked' => 0,
                'ml_sender_id' => 0,
                'ml_customheader' => $custom_header,
            ]
        );
        return self::sendLetter($db, $lt_id);
    }

    /* Отправка напрямую без записи в БД
     */

    public static function sendDirect($to, $theme, $text, $headers = null)
    {
        self::sendOnly(
            [
                'ml_adr_to' => $to,
                'ml_theme' => $theme,
                'ml_text' => $text,
                'ml_customheader' => $headers ? $headers : '',
            ]
        );
    }

    private static function prepareLetter($tmpl_id, $elements = [])
    {
        $db = FactoryDB::db();
        $mt = new MMailTemplates($db);
        $template = $mt->getItemByPk($tmpl_id);
        foreach ($elements as $elkey => $element) {
            $template['mt_content'] = str_replace("%$elkey%", $element, $template['mt_content']);
        }
        return $template;
    }
}
