<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends the "a RAPP incident was filed" notification. One message, once, to
 * everyone who holds rapp.notify plus any active alternate (the caller supplies
 * the resolved recipient list). The audio is never attached -- the message
 * links to the authenticated detail page.
 *
 * In dev-log mode the message is written to the PHP error log instead of sent.
 */
final class RappNotifier
{
    /**
     * @param array{host:string,port:string|int,secure:string,user:string,pass:string,from_email:string,from_name:string} $smtp
     */
    public function __construct(
        private array $smtp,
        private bool $devLogOnly,
        private string $detailUrlBase
    ) {}

    /**
     * @param array<string,mixed> $report a row from RappReportRepository::findReport()
     * @param list<string>         $recipients
     */
    public function notifyNewReport(array $report, array $recipients): void
    {
        $recipients = array_values(array_unique(array_filter($recipients)));
        if ($recipients === []) {
            error_log('RappNotifier: no recipients for report ' . ($report['_id'] ?? '?'));
            return;
        }

        $subject = sprintf(
            'RAPP INCIDENT — %s — %s %s — %s',
            $report['division_name'] ?? '?',
            $report['match_date'] ?? '?',
            $report['match_time'] ?? '',
            $report['field_name'] ?? '?'
        );

        $link = $this->detailUrlBase . '/reports.php?id=' . (int) ($report['_id'] ?? 0);
        $parts = [];
        $parts[] = 'A Referee Abuse Prevention Program report has been filed.';
        $parts[] = '';
        $parts[] = 'Match:      ' . ($report['match_date'] ?? '?') . ' ' . ($report['match_time'] ?? '')
                 . ' — ' . ($report['division_name'] ?? '?') . ', ' . ($report['field_name'] ?? '?');
        $parts[] = 'Teams:      ' . ($report['home_team'] ?? '?') . ' v ' . ($report['away_team'] ?? '?');
        $parts[] = 'Filed by:   ' . ($report['submitter_name'] ?? $report['submitter_email'] ?? '?');
        $parts[] = 'Contains:   ' . (($report['body_text'] ?? '') !== '' ? 'written account' : 'no written account')
                 . (((int) ($report['has_audio'] ?? 0)) === 1 ? ' + audio recording' : '');
        $parts[] = '';
        $parts[] = 'Review it here (sign-in required): ' . $link;
        $parts[] = '';
        $parts[] = 'This is the only notification for this report.';
        $body = implode("\n", $parts);

        if ($this->devLogOnly) {
            error_log(sprintf(
                "[RappNotifier DEV] to=%s | %s\n%s",
                implode(',', $recipients),
                $subject,
                $body
            ));
            return;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->smtp['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtp['user'];
        $mail->Password = $this->smtp['pass'];
        $mail->SMTPSecure = strtolower((string) $this->smtp['secure']) === 'smtps'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) $this->smtp['port'];

        $mail->setFrom($this->smtp['from_email'], $this->smtp['from_name']);
        // To: the sending mailbox; recipients as BCC so the list isn't exposed.
        $mail->addAddress($this->smtp['from_email'], $this->smtp['from_name']);
        foreach ($recipients as $rcpt) {
            $mail->addBCC($rcpt);
        }
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $body;

        $mail->send();
    }
}
