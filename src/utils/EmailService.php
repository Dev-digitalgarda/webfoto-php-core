<?php

namespace Webfoto\Core\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

use League\OAuth2\Client\Provider\Google;

use Webfoto\Core\Utils\Logger;

class EmailService
{
    private static function processText(string $text, string $album): string
    {
        return str_replace('{{ALBUM}}', $album, $text);
    }

    private static function sendEmailWithCredentials(array $emailOptions, string $album): void
    {
        try {
            $mail = new PHPMailer(true);

            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = $emailOptions['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $emailOptions['username'];
            $mail->Password   = $emailOptions['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom($emailOptions['username']);
            $mail->addAddress($emailOptions['recipient']);

            $mail->isHTML(false);
            $mail->Subject = EmailService::processText($emailOptions['subject'], $album);
            $mail->Body    = EmailService::processText($emailOptions['body'], $album);

            $mail->send();
        } catch (Exception $e) {
            Logger::$logger->error("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }

    private static function sendEmailWithGoogle(array $emailOptions, string $album): void
    {
        try {
            $mail = new PHPMailer(true);

            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = $emailOptions['host'];
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;


            $mail->AuthType = 'XOAUTH2';
            $provider = new Google(
                [
                    'clientId' => $emailOptions['googleClientId'],
                    'clientSecret' => $emailOptions['googleClientSecret'],
                ]
            );
            $mail->setOAuth(
                new OAuth(
                    [
                        'provider' => $provider,
                        'clientId' => $emailOptions['googleClientId'],
                        'clientSecret' => $emailOptions['googleClientSecret'],
                        'refreshToken' => $emailOptions['googleRefreshToken'],
                        'userName' => $emailOptions['username']
                    ]
                )
            );

            $mail->setFrom($emailOptions['username']);
            $mail->addAddress($emailOptions['recipient']);

            $mail->isHTML(false);
            $mail->Subject = EmailService::processText($emailOptions['subject'], $album);
            $mail->Body    = EmailService::processText($emailOptions['body'], $album);

            $mail->send();
        } catch (Exception $e) {
            Logger::$logger->error("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }

    public static function sendAlertEmail(array $emailOptions, string $album): void
    {
        switch ($emailOptions['authType']) {
            case 'credentials':
                Logger::$logger->info('Sending email with credentials');
                EmailService::sendEmailWithCredentials($emailOptions, $album);
                Logger::$logger->info('Sent email with credentials');
                break;
            case 'google':
                Logger::$logger->info('Sending email with google');
                EmailService::sendEmailWithGoogle($emailOptions, $album);
                Logger::$logger->info('Sent email with google');
                break;
            default:
                Logger::$logger->warning("Invalid email auth type: {$emailOptions['authType']}");
        }
    }
}
