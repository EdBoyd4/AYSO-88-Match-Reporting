<?php
declare(strict_types=1);

use Boyd\LoginLibrary\Contracts\OtpMailerInterface;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Delivers one-time login codes for gss88 via SMTP (PHPMailer). In dev mode
 * ($devLogOnly) the code is written to the PHP error log instead of being sent,
 * so the sign-in flow can be exercised on the dev box without real mail.
 */
final class Gss88OtpMailer implements OtpMailerInterface
{
    /**
     * @param array{host:string,port:string|int,secure:string,user:string,pass:string,from_email:string,from_name:string} $smtp
     */
    public function __construct(
        private array $smtp,
        private bool $devLogOnly = false
    ) {}

    public function sendOtp(string $toEmail, string $code, int $ttlSeconds): void
    {
        $minutes = max(1, intdiv($ttlSeconds, 60));

        if ($this->devLogOnly) {
            error_log(sprintf(
                '[Gss88OtpMailer DEV] one-time code for %s = %s (valid %d min)',
                $toEmail, $code, $minutes
            ));
            return;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->smtp['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtp['user'];
        $mail->Password = $this->smtp['pass'];
        $mail->SMTPSecure = strtolower((string)$this->smtp['secure']) === 'smtps'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)$this->smtp['port'];

        $mail->setFrom($this->smtp['from_email'], $this->smtp['from_name']);
        $mail->addAddress($toEmail);
        $mail->Subject = 'Your AYSO Region 88 sign-in code';
        $mail->Body =
            "Your one-time sign-in code is: {$code}\n\n" .
            "It expires in {$minutes} minutes.\n" .
            "If you did not request this, you can ignore this email.";
        $mail->AltBody = $mail->Body;

        $mail->send();
    }
}
