<?php
/**
 * Description of class MyPHPMailer
 *
 * @author Andrey_Pns
 */

require_once "class.phpmailer.php";

class MyPHPMailer extends PHPMailer {
    public function __construct() {
        $this->IsSMTP();
        $this->ContentType   = "text/html";
        $this->Mailer   = "mail";
        //$this->IsSMTP();
        $this->SMTPAuth = true;
        $this->CharSet  = "windows-1251";
        $this->From     = _MAIL_FROMADDR;
        $this->FromName = _MAIL_FROMNAME;
        $this->Host     = _MAIL_HOST;
        $this->Username = _MAIL_USER;                  // SMTP username
        $this->Password = _MAIL_PASS;                  // SMTP password
    }
}
?>
