<?php
declare(strict_types=1);

/**
 * All database access for the RAPP feature: the reports themselves, their audio
 * media rows, the media-access audit log, the "alternate RRA" designation, and
 * a few read helpers that join gss88's existing match tables.
 *
 * Constructor-injected PDO (the same connection the login library uses).
 */
final class RappReportRepository
{
    public function __construct(private PDO $pdo) {}

    // -- match picker -------------------------------------------------------

    /**
     * Recent scheduled matches for the submission dropdown. Not filtered by
     * whether a match report exists -- a RAPP report is independent of that.
     *
     * @return list<array{id:int,label:string,division_id:int}>
     */
    public function recentScheduledMatches(int $days = 21): array
    {
        $sql = "SELECT sm._id,
                       sm.match_date,
                       DATE_FORMAT(sm.match_time, '%H:%i') AS match_time,
                       sm.home_team, sm.away_team,
                       d._id  AS division_id,
                       d.division_name,
                       f.field_name
                FROM scheduled_matches sm
                LEFT JOIN divisions_with_coordinators d ON d._id = sm.division
                LEFT JOIN fields f ON f._id = sm.field
                WHERE sm.match_date >= (CURDATE() - INTERVAL :days DAY)
                ORDER BY sm.match_date DESC, sm.match_time DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[] = [
                'id' => (int) $r['_id'],
                'division_id' => (int) $r['division_id'],
                'label' => sprintf(
                    '%s %s — %s, %s (%s v %s)',
                    $r['match_date'],
                    $r['match_time'],
                    $r['division_name'] ?? '?',
                    $r['field_name'] ?? '?',
                    $r['home_team'],
                    $r['away_team']
                ),
            ];
        }
        return $out;
    }

    public function scheduledMatchExists(int $scheduledMatchId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM scheduled_matches WHERE _id = ?');
        $stmt->execute([$scheduledMatchId]);
        return (bool) $stmt->fetchColumn();
    }

    // -- create ----------------------------------------------------------

    public function createReport(int $scheduledMatchId, int $submittedByUserId, ?string $bodyText, bool $hasAudio): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rapp_reports (scheduled_match_id, submitted_by_user_id, body_text, has_audio)
             VALUES (:m, :u, :b, :h)'
        );
        $stmt->execute([
            'm' => $scheduledMatchId,
            'u' => $submittedByUserId,
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

    // -- read ----------------------------------------------------------

    /** Full detail for one report, joined to match / division / field / submitter. */
    public function findReport(int $id): ?array
    {
        $sql = "SELECT r.*,
                       sm.match_date, DATE_FORMAT(sm.match_time,'%H:%i') AS match_time,
                       sm.home_team, sm.away_team,
                       d._id AS division_id, d.division_name, d.division_number,
                       f.field_name,
                       u.user_name AS submitter_email,
                       p.full_name AS submitter_name
                FROM rapp_reports r
                LEFT JOIN scheduled_matches sm ON sm._id = r.scheduled_match_id
                LEFT JOIN divisions_with_coordinators d ON d._id = sm.division
                LEFT JOIN fields f ON f._id = sm.field
                LEFT JOIN users u ON u.user_id = r.submitted_by_user_id
                LEFT JOIN gss88_user_profiles p ON p.user_id = r.submitted_by_user_id
                WHERE r._id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Report list for the RAPP dashboard (RRA / Senior Board see all).
     *
     * @return list<array<string,mixed>>
     */
    public function listReports(int $limit = 200): array
    {
        $sql = "SELECT r._id, r.status, r.retain, r.has_audio, r.created_at,
                       r.content_purged_at,
                       sm.match_date, DATE_FORMAT(sm.match_time,'%H:%i') AS match_time,
                       d.division_name, f.field_name,
                       p.full_name AS submitter_name, u.user_name AS submitter_email
                FROM rapp_reports r
                LEFT JOIN scheduled_matches sm ON sm._id = r.scheduled_match_id
                LEFT JOIN divisions_with_coordinators d ON d._id = sm.division
                LEFT JOIN fields f ON f._id = sm.field
                LEFT JOIN users u ON u.user_id = r.submitted_by_user_id
                LEFT JOIN gss88_user_profiles p ON p.user_id = r.submitted_by_user_id
                ORDER BY r.created_at DESC
                LIMIT :lim";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array{_id:int,filename:string,mime:string,bytes:int,purged_at:?string}> */
    public function mediaForReport(int $reportId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT _id, filename, mime, bytes, purged_at FROM rapp_media WHERE rapp_report_id = ? ORDER BY _id'
        );
        $stmt->execute([$reportId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findMedia(int $mediaId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rapp_media WHERE _id = ?');
        $stmt->execute([$mediaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // -- mutate --------------------------------------------------------

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

    public function logMediaAccess(int $reportId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rapp_media_access_log (rapp_report_id, user_id) VALUES (?, ?)'
        );
        $stmt->execute([$reportId, $userId]);
    }

    // -- retention ---------------------------------------------------

    /** @return list<array{_id:int}> reports whose content is due for purging. */
    public function reportsDueForPurge(int $olderThanHours): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT _id FROM rapp_reports
             WHERE retain = 0
               AND content_purged_at IS NULL
               AND created_at < (NOW() - INTERVAL :h HOUR)'
        );
        $stmt->bindValue(':h', $olderThanHours, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    // -- alternates ------------------------------------------------

    /** @return list<int> user_ids currently serving as an alternate RRA. */
    public function activeAlternateUserIds(): array
    {
        $stmt = $this->pdo->query(
            'SELECT alternate_user_id FROM rapp_alternates
             WHERE is_active = 1
               AND starts_at <= NOW()
               AND (ends_at IS NULL OR ends_at >= NOW())'
        );
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
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

    /** @return list<array<string,mixed>> */
    public function listActiveAlternates(): array
    {
        $stmt = $this->pdo->query(
            "SELECT a._id, a.starts_at, a.ends_at, p.full_name, u.user_name AS email
             FROM rapp_alternates a
             LEFT JOIN users u ON u.user_id = a.alternate_user_id
             LEFT JOIN gss88_user_profiles p ON p.user_id = a.alternate_user_id
             WHERE a.is_active = 1
             ORDER BY a.starts_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -- recipients --------------------------------------------------

    /**
     * Email addresses of active users holding any of the given roles, unioned
     * with any active alternates. Used for the incident notification.
     *
     * @param list<string> $roles
     * @return list<string>
     */
    public function notificationRecipients(array $roles): array
    {
        if ($roles === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($roles), '?'));
        $sql = "SELECT DISTINCT u.user_name
                FROM users u
                JOIN user_roles ur ON ur.user_id = u.user_id
                JOIN roles ro ON ro.role_id = ur.role_id
                LEFT JOIN gss88_user_profiles p ON p.user_id = u.user_id
                WHERE ro.role IN ($in)
                  AND COALESCE(p.is_active, 1) = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($roles);
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $altIds = $this->activeAlternateUserIds();
        if ($altIds !== []) {
            $altIn = implode(',', array_fill(0, count($altIds), '?'));
            $altStmt = $this->pdo->prepare("SELECT user_name FROM users WHERE user_id IN ($altIn)");
            $altStmt->execute($altIds);
            $emails = array_merge($emails, $altStmt->fetchAll(PDO::FETCH_COLUMN));
        }

        return array_values(array_unique(array_filter(array_map('strval', $emails))));
    }
}
