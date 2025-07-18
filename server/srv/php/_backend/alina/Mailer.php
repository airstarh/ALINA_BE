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
            $mail->Host       = AlinaCfg('mailer/admin/Host');
            $mail->SMTPAuth   = TRUE;
            $mail->Username   = AlinaCfg('mailer/admin/Username');                     // SMTP username
            $mail->Password   = AlinaCfg('mailer/admin/Password');                               // SMTP password
            $mail->SMTPSecure = AlinaCfg('mailer/admin/SMTPSecure');
            $mail->Port       = AlinaCfg('mailer/admin/Port');                                    // TCP port to connect to
            //$mail->SMTPDebug  = 1;                                    // TCP port to connect to
            //Recipients
            //From
            $mail->setFrom(AlinaCfg('mailer/admin/Username'), AlinaCfg('mailer/admin/FromName'));
            $mail->addReplyTo(AlinaCfg('mailer/admin/Username'), AlinaCfg('mailer/admin/FromName'));
            //To
            $mail->addAddress($to, $to);     // Add a recipient
            // Content
            $subject = "Reset Password. Verification code.";
            $message = "Your verification code is {$code}. You know, what to do :-)";
            $mail->isHTML(TRUE);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = $message;
            $sendRes       = $mail->send();
            if ($sendRes) {
                Message::setInfo("Message has been sent");
            }
            else {
                Message::setDanger("Failed");
            }
        } catch (AppException $e) {
            Message::setDanger("Message could not be sent. Mailer Error: %s", [$mail->ErrorInfo]);
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
            $mail->Host       = AlinaCfg('mailer/admin/Host');                    // Set the SMTP server to send through
            $mail->SMTPAuth   = TRUE;                                   // Enable SMTP authentication
            $mail->Username   = AlinaCfg('mailer/admin/Username');                     // SMTP username
            $mail->Password   = AlinaCfg('mailer/admin/Password');                               // SMTP password
            $mail->SMTPSecure = AlinaCfg('mailer/admin/SMTPSecure');         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
            $mail->Port       = AlinaCfg('mailer/admin/Port');                                    // TCP port to connect to
            //Recipients
            //From
            $mail->setFrom(AlinaCfg('mailer/admin/Username'), 'ALINA Framework');
            $mail->addReplyTo(AlinaCfg('mailer/admin/Username'), 'ALINA Framework');
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
            $mail->Body    = '<h1>Hello, Sewa!</h1>This is the HTML message body <b>in bold!</b>';
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
            $mail->send();
            Message::setInfo("Message has been sent");
        } catch (AppException $e) {
            Message::setDanger("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }

        return TRUE;
    }
}
