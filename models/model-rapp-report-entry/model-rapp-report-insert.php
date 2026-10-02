<?php
declare(strict_types=1);

/**
 * All writes to the RAPP feature's own tables (rapp_reports, rapp_media,
 * rapp_alternates, rapp_media_access_log). Read access to the same tables --
 * plus the cross-database joins to match_reports/user_access/otp_users a
 * report display needs -- is RappReportQueries (model-rapp-report-query.php).
 *
 * Split out of the former RappReportRepository (rapp/src/), which mixed both;
 * none of the write methods below ever needed the cross-database names that
 * class carried for its read methods, so this one takes only a PDO
 * connection -- a real simplification the split exposed, not just a rename.
 */
final class RappReportInserter
{
    public function __construct(private PDO $pdo) {}

    public function createReport(
        int $scheduledMatchId,
        int $submittedByUserId,
        string $submittedBySource,
        ?string $bodyText,
        bool $hasAudio
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rapp_reports (scheduled_match_id, submitted_by_user_id, submitted_by_source, body_text, has_audio)
             VALUES (:m, :u, :src, :b, :h)'
        );
        $stmt->execute([
            'm' => $scheduledMatchId,
            'u' => $submittedByUserId,
            'src' => $submittedBySource,
            'b' => ($bodyText === null || $bodyText === '') ? null : $bodyText,
            'h' => $hasAudio ? 1 : 0,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function addMedia(int $reportId, string $relativePath, string $mime, int $bytes, ?string $originalName): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rapp_media (rapp_report_id, filename, mime, bytes, original_name)
             VALUES (:r, :f, :m, :b, :o)'
        );
        $stmt->execute([
            'r' => $reportId,
            'f' => $relativePath,
            'm' => $mime,
            'b' => $bytes,
            'o' => $originalName,
        ]);
        $this->pdo->prepare('UPDATE rapp_reports SET has_audio = 1 WHERE _id = ?')->execute([$reportId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE rapp_reports
             SET status = :s,
                 acknowledged_at = CASE WHEN :s2 IN ('acknowledged','retained','closed') AND acknowledged_at IS NULL
                                        THEN NOW() ELSE acknowledged_at END
             WHERE _id = :id"
        );
        $stmt->execute(['s' => $status, 's2' => $status, 'id' => $id]);
    }

    public function setRetain(int $id, bool $retain): void
    {
        $stmt = $this->pdo->prepare('UPDATE rapp_reports SET retain = :r, status = :s WHERE _id = :id');
        $stmt->execute([
            'r' => $retain ? 1 : 0,
            's' => $retain ? 'retained' : 'acknowledged',
            'id' => $id,
        ]);
    }

    public function logMediaAccess(int $reportId, int $userId, string $userSource): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rapp_media_access_log (rapp_report_id, user_id, user_source) VALUES (?, ?, ?)'
        );
        $stmt->execute([$reportId, $userId, $userSource]);
    }

    /** Wipe body text + mark media purged. The metadata row is kept. */
    public function markContentPurged(int $reportId): void
    {
        $this->pdo->prepare(
            "UPDATE rapp_reports
             SET body_text = NULL, content_purged_at = NOW(), status = 'closed'
             WHERE _id = ?"
        )->execute([$reportId]);
        $this->pdo->prepare(
            'UPDATE rapp_media SET purged_at = NOW() WHERE rapp_report_id = ? AND purged_at IS NULL'
        )->execute([$reportId]);
    }

    public function addAlternate(int $alternateUserId, int $activatedByUserId, string $startsAt, ?string $endsAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rapp_alternates (alternate_user_id, activated_by_user_id, starts_at, ends_at)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$alternateUserId, $activatedByUserId, $startsAt, $endsAt ?: null]);
    }

    public function deactivateAlternate(int $alternateRowId): void
    {
        $this->pdo->prepare('UPDATE rapp_alternates SET is_active = 0 WHERE _id = ?')->execute([$alternateRowId]);
    }
}
