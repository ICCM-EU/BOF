<?php

namespace ICCM\BOF;
use \PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $dbo;
    private $settings;

    function __construct($dbo) {
        $this->dbo = $dbo;
        $this->settings = require __DIR__.'/../../cfg/settings.php';
    }

    public function sendEmail($email_to, $subject, $html_body, $text_body) {

        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host       = $this->settings['settings']['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->settings['settings']['smtp']['user'];
        $mail->Password   = $this->settings['settings']['smtp']['passwd'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $this->settings['settings']['smtp']['port'];
        $mail->setFrom($this->settings['settings']['smtp']['from'], $this->settings['settings']['smtp']['from_name']);
        $mail->addAddress($email_to);
        $mail->Subject = $subject;
        $mail->CharSet = "UTF-8";

        if ($html_body != '') {
            $mail->isHTML(true);
            $mail->Body = $html_body;
            $mail->AltBody = $text_body;
        }
        else
        {
            $mail->Body = $text_body;
        }

        try {
            if (!$mail->send()) {
                error_log($mail->ErrorInfo);
            }
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }
}
