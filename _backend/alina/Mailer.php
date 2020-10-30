<?php

namespace alina;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer
{
    public function __construct()
    {

    }

    public function sendVerificationCode($to, $code)
    {
        $mail = new PHPMailer(TRUE);

        try {
            //Server settings
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = AlinaCFG('mailer/admin/Host');
            $mail->SMTPAuth   = TRUE;
            $mail->Username   = AlinaCFG('mailer/admin/Username');                     // SMTP username
            $mail->Password   = AlinaCFG('mailer/admin/Password');                               // SMTP password
            $mail->SMTPSecure = AlinaCFG('mailer/admin/SMTPSecure');
            $mail->Port       = AlinaCFG('mailer/admin/Port');                                    // TCP port to connect to
            error_log('XXX',0);
            error_log(AlinaCFG('mailer/admin/SMTPSecure'),0);
            //Recipients
            //From
            $mail->setFrom(AlinaCFG('mailer/admin/Username'), AlinaCFG('mailer/admin/FromName'));
            $mail->addReplyTo(AlinaCFG('mailer/admin/Username'), AlinaCFG('mailer/admin/FromName'));
            //To
            $mail->addAddress($to, $to);     // Add a recipient
            // Content
            $subject = "Alina Verification code";
            $message = "Your verification code is {$code}. You know, what to do :-)";
            $mail->isHTML(TRUE);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = $message;

            $mail->send();
            Message::setInfo("Message has been sent");
        } catch (AppException $e) {
            Message::setDanger("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }

        return TRUE;
    }

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
            Message::setInfo("Message has been sent");
        } catch (AppException $e) {
            Message::setDanger("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }

        return TRUE;
    }
}
