<?php
declare(strict_types=1);

/**
 * All read access to the RAPP feature: the reports themselves, their audio
 * media rows, the "alternate RRA" designation, and a few read helpers that
 * join gss88's other databases. Writes are RappReportInserter (model-rapp-
 * report-insert.php).
 *
 * rapp_reports/rapp_media/rapp_alternates are native to $pdo's own database.
 * scheduled_matches/divisions_with_coordinators/fields live in the separate
 * match_reports database; users/roles/gss88_user_profiles live in the
 * separate user_access database -- both same-server, reached here via
 * schema-qualified table names over this one connection (grants cover all of
 * them; MySQL just doesn't support cross-database FOREIGN KEYs, not
 * cross-database SELECT/JOIN).
 *
 * A report's submitter is a composite reference (*_user_id + *_source)
 * rather than a plain FK, because user_access and otp_users are two disjoint
 * identity spaces sharing one numeric ID range -- the same user_id could
 * name two different people depending which store it came from. Every join
 * against either users table is conditioned on the matching *_source value
 * for that reason; getting this wrong would misattribute a report to the
 * wrong real person.
 */
final class RappReportQueries
{
    public function __construct(
        private PDO $pdo,
        private string $matchReportsDb,
        private string $userAccessDb,
        private string $otpUsersDb
    ) {
        foreach (['matchReportsDb' => $matchReportsDb, 'userAccessDb' => $userAccessDb, 'otpUsersDb' => $otpUsersDb] as $label => $name) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
                throw new \InvalidArgumentException("Invalid database name for {$label}: {$name}");
            }
        }
    }

    // -- match picker -------------------------------------------------------

    /**
     * Scheduled matches still inside the reporting window, for the submission
     * dropdown. Not filtered by whether a match report exists -- a RAPP
     * report is independent of that. Hour-precision (match_date + match_time
     * combined), not calendar-day, since the window is policy-set in hours
     * (120, i.e. 5 days, as of 2026-09-27 -- referees are volunteers, and a
     * Saturday match reported the following Wednesday isn't unreasonable) --
     * a day-granularity filter would let a match from first thing 5 days ago
     * stay pickable nearly a full day longer than intended.
     *
     * @return list<array{id:int,label:string,division_id:int}>
     */
    public function recentScheduledMatches(int $windowHours): array
    {
        $sql = $this->matchPickerSelect() . "
                WHERE TIMESTAMP(sm.match_date, sm.match_time) >= (NOW() - INTERVAL :hours HOUR)
                ORDER BY sm.match_date DESC, sm.match_time DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':hours', $windowHours, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'toPickerEntry'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * True if $scheduledMatchId exists AND is still inside $windowHours of
     * now. Re-checked at submission time regardless of how the match was
     * selected (the picker dropdown, or a pre-filled report.php?match= link)
     * -- the picker only controls what's *offered*, not what the server will
     * actually accept.
     */
    public function isWithinReportWindow(int $scheduledMatchId, int $windowHours): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM {$this->matchReportsDb}.scheduled_matches
             WHERE _id = :id AND TIMESTAMP(match_date, match_time) >= (NOW() - INTERVAL :hours HOUR)"
        );
        $stmt->bindValue(':id', $scheduledMatchId, PDO::PARAM_INT);
        $stmt->bindValue(':hours', $windowHours, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    /** Class constants can't interpolate $this->matchReportsDb, hence a method. */
    private function matchPickerSelect(): string
    {
        return "SELECT sm._id,
                       sm.match_date,
                       DATE_FORMAT(sm.match_time, '%H:%i') AS match_time,
                       sm.home_team, sm.away_team,
                       d._id  AS division_id,
                       d.division_name,
                       f.field_name
                FROM {$this->matchReportsDb}.scheduled_matches sm
                LEFT JOIN {$this->matchReportsDb}.divisions_with_coordinators d ON d._id = sm.division
                LEFT JOIN {$this->matchReportsDb}.fields f ON f._id = sm.field";
    }

    /** @return array{id:int,label:string,division_id:int} */
    private function toPickerEntry(array $r): array
    {
        return [
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

    // -- read ----------------------------------------------------------

    /** Full detail for one report, joined to match / division / field / submitter. */
    public function findReport(int $id): ?array
    {
        $sql = "SELECT r.*,
                       sm.match_date, DATE_FORMAT(sm.match_time,'%H:%i') AS match_time,
                       sm.home_team, sm.away_team,
                       d._id AS division_id, d.division_name, d.division_number,
                       f.field_name,
                       COALESCE(ua.user_name, ou.user_name) AS submitter_email,
                       p.full_name AS submitter_name
                FROM rapp_reports r
                LEFT JOIN {$this->matchReportsDb}.scheduled_matches sm ON sm._id = r.scheduled_match_id
                LEFT JOIN {$this->matchReportsDb}.divisions_with_coordinators d ON d._id = sm.division
                LEFT JOIN {$this->matchReportsDb}.fields f ON f._id = sm.field
                LEFT JOIN {$this->userAccessDb}.users ua ON ua.user_id = r.submitted_by_user_id AND r.submitted_by_source = 'user_access'
                LEFT JOIN {$this->userAccessDb}.gss88_user_profiles p ON p.user_id = r.submitted_by_user_id AND r.submitted_by_source = 'user_access'
                LEFT JOIN {$this->otpUsersDb}.users ou ON ou.user_id = r.submitted_by_user_id AND r.submitted_by_source = 'otp_users'
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
                       p.full_name AS submitter_name,
                       COALESCE(ua.user_name, ou.user_name) AS submitter_email
                FROM rapp_reports r
                LEFT JOIN {$this->matchReportsDb}.scheduled_matches sm ON sm._id = r.scheduled_match_id
                LEFT JOIN {$this->matchReportsDb}.divisions_with_coordinators d ON d._id = sm.division
                LEFT JOIN {$this->matchReportsDb}.fields f ON f._id = sm.field
                LEFT JOIN {$this->userAccessDb}.users ua ON ua.user_id = r.submitted_by_user_id AND r.submitted_by_source = 'user_access'
                LEFT JOIN {$this->userAccessDb}.gss88_user_profiles p ON p.user_id = r.submitted_by_user_id AND r.submitted_by_source = 'user_access'
                LEFT JOIN {$this->otpUsersDb}.users ou ON ou.user_id = r.submitted_by_user_id AND r.submitted_by_source = 'otp_users'
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

    // -- alternates ------------------------------------------------

    /**
     * @return list<int> user_ids currently serving as an alternate RRA.
     * Alternates are always drawn from user_access -- only manually-managed,
     * privileged roles are eligible (see RappUserAdminRepository::
     * activeUsersForPicker(), the only source of candidates for this table).
     */
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

    /** @return list<array<string,mixed>> */
    public function listActiveAlternates(): array
    {
        $stmt = $this->pdo->query(
            "SELECT a._id, a.starts_at, a.ends_at, p.full_name, u.user_name AS email
             FROM rapp_alternates a
             LEFT JOIN {$this->userAccessDb}.users u ON u.user_id = a.alternate_user_id
             LEFT JOIN {$this->userAccessDb}.gss88_user_profiles p ON p.user_id = a.alternate_user_id
             WHERE a.is_active = 1
             ORDER BY a.starts_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -- recipients --------------------------------------------------

    /**
     * Email addresses of active users holding any of the given roles, unioned
     * with any active alternates. Used for the incident notification. Roles
     * like 'rra' and 'senior_board' only ever exist in user_access -- a
     * self-service otp_users account can't hold them (see AccessPolicy /
     * SelfServiceOtpController's fixed 'ref' role) -- so this is user_access
     * only, no source branching needed.
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
                FROM {$this->userAccessDb}.users u
                JOIN {$this->userAccessDb}.user_roles ur ON ur.user_id = u.user_id
                JOIN {$this->userAccessDb}.roles ro ON ro.role_id = ur.role_id
                LEFT JOIN {$this->userAccessDb}.gss88_user_profiles p ON p.user_id = u.user_id
                WHERE ro.role IN ($in)
                  AND COALESCE(p.is_active, 1) = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($roles);
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $altIds = $this->activeAlternateUserIds();
        if ($altIds !== []) {
            $altIn = implode(',', array_fill(0, count($altIds), '?'));
            $altStmt = $this->pdo->prepare("SELECT user_name FROM {$this->userAccessDb}.users WHERE user_id IN ($altIn)");
            $altStmt->execute($altIds);
            $emails = array_merge($emails, $altStmt->fetchAll(PDO::FETCH_COLUMN));
        }

        return array_values(array_unique(array_filter(array_map('strval', $emails))));
    }
}
