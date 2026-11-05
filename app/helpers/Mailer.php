<?php

/**
 * Real e-mail delivery built on PHPMailer.
 * Reads SMTP settings from .env (MAIL_HOST / MAIL_PORT / MAIL_USERNAME /
 * MAIL_PASSWORD / MAIL_ENCRYPTION / MAIL_FROM_ADDRESS / MAIL_FROM_NAME).
 */
class Mailer {
    /**
     * Sends an HTML e-mail. Returns true on success, false otherwise.
     */
    public static function enviar(string $para, string $assunto, string $corpoHtml, string $corpoTexto = ''): bool {
        require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/Exception.php';
        require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/SMTP.php';

        $host = Env::get('MAIL_HOST', '');
        $user = Env::get('MAIL_USERNAME', '');
        $pass = Env::get('MAIL_PASSWORD', '');

        if (empty($host) || empty($user) || empty($pass)) {
            Logger::warning('Mailer: SMTP não configurado (MAIL_HOST/USERNAME/PASSWORD vazios)');
            return false;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $user;
            $mail->Password   = $pass;
            $mail->Port       = (int) Env::get('MAIL_PORT', 587);
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 20;

            $enc = strtolower(Env::get('MAIL_ENCRYPTION', 'tls'));
            $mail->SMTPSecure = $enc === 'ssl'
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom(Env::get('MAIL_FROM_ADDRESS', $user), Env::get('MAIL_FROM_NAME', 'EloMed'));
            $mail->addAddress($para);
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $corpoHtml;
            $mail->AltBody = $corpoTexto !== '' ? $corpoTexto : strip_tags(str_replace(['<br>', '</p>'], "\n", $corpoHtml));

            return $mail->send();
        } catch (\Throwable $e) {
            Logger::error('Mailer: falha ao enviar e-mail', ['error' => $e->getMessage()]);
            return false;
        }
    }
}