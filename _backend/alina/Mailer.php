<?php

namespace alina;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer
{
    public function usageExample()
    {
        $mail = new PHPMailer(TRUE);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = 'smtp.yandex.ru';                    // Set the SMTP server to send through
            $mail->SMTPAuth   = TRUE;                                   // Enable SMTP authentication
            $mail->Username   = 'my-customer-mailbox@yandex.ru';                     // SMTP username
            $mail->Password   = 'qwerty123qwerty';                               // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
            $mail->Port       = 587;                                    // TCP port to connect to

            //Recipients
            //From
            $mail->setFrom('my-customer-mailbox@yandex.ru', 'ALINA Framework');
            $mail->addReplyTo('my-customer-mailbox@yandex.ru', 'ALINA Framework');
            //To
            $mail->addAddress('air_star_h@mail.ru', 'Sewa Mail');     // Add a recipient
            $mail->addCC('vsevolod.azovsky@gmail.com');
//            $mail->addAddress('vsevolod.azovsky@gmail.com');               // Name is optional
//            $mail->addBCC('vsevolod.azovsky@gmail.com');

            // Attachments
//            $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

            // Content
            $mail->isHTML(TRUE);                                  // Set email format to HTML
            $mail->Subject = 'Here is the subject';
            $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            Message::set("Message has been sent");
        } catch (Exception $e) {
            Message::set("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }

        return true;
    }
}
