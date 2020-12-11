<?php

use SendGrid\Mail\Mail;

class SendGridMailer
{

    protected $sendGrid;

    function __construct()
    {
        $this->sendGrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
    }


    public function sendMail($from, $fromName, $to, $toName, $subject, $body, $ccList = [], $bccList = [])
    {
        $mail = new Mail();
        $mail->setFrom($from, $fromName);
        $mail->addTo($to, $toName);

        if (!empty($ccList)) {
            foreach ($ccList as $ccEmail) {
                $mail->addCc($ccEmail);
            }
        }

        if (!empty($bccList)) {
            foreach ($bccList as $bccEmail) {
                $mail->addBcc($bccEmail);
            }
        }

        $mail->setSubject($subject);
        $mail->addContent("text/html", $body);

        try {
            $response = $this->sendGrid->send($mail);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
