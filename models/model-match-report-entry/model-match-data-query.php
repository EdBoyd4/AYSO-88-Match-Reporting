<?php
declare(strict_types=1);

/**
 * All read access to gss88's match domain (scheduled_matches,
 * divisions_with_coordinators, fields, match_reports, officiants, sanctions,
 * gamecards) -- for the public match-report submission form
 * (public/match-report.php), the RAPP admin dashboard's non-RAPP sections
 * (Match data / Scores & game cards), and the match-report notification
 * email (controllers/controllers-match-issue-reports-AYSO-88/
 * controller-match-report-email.php).
 *
 * Deliberately excludes rapp_reports/rapp_media/rapp_alternates -- the actual
 * abuse-report content -- which stays in RappReportQueries (model-rapp-
 * report-query.php), queried under different capabilities (rapp.view/
 * rapp.submit/rapp.retain rather than matchdata.view/scores.view) and, since
 * the four-database split, a different database entirely. Keeping routine
 * match data and RAPP-sensitive data in separate classes means a bug in one
 * has no path to the other.
 *
 * matchDetailsForEmail() is the one method here that only ever has one
 * caller: it builds the wide, unpivoted (sanction_level_1..5 as separate
 * columns, not a list) row the notification email template substitutes
 * directly -- a genuinely different shape than every other method here,
 * which return normalized rows/lists. Kept alongside the rest of this class
 * (not a separate file) because it reads the same tables through the same
 * connection; expected to be deleted outright once the registered-user
 * notification system replaces email notifications.
 */
final class MatchDataQueries
{
    public function __construct(private PDO $pdo) {}

    // -- lookups: match-report.php's submission form -------------------------

    /** @return list<array{id:int,number:int,name:string}> */
    public function activeFields(): array
    {
        $stmt = $this->pdo->query(
            'SELECT _id, field_number, field_name
             FROM fields
             WHERE field_active = 1
             ORDER BY field_number'
        );
        return $this->numberedRows($stmt);
    }

    /** @return list<array{id:int,number:int,name:string}> */
    public function activeDivisions(): array
    {
        $stmt = $this->pdo->query(
            'SELECT _id, division_number, division_name
             FROM divisions_with_coordinators
             WHERE division_active = 1
             ORDER BY division_number'
        );
        return $this->numberedRows($stmt, 'division_number', 'division_name');
    }

    private function numberedRows(PDOStatement $stmt, string $numberCol = 'field_number', string $nameCol = 'field_name'): array
    {
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[] = ['id' => (int) $r['_id'], 'number' => (int) $r[$numberCol], 'name' => (string) $r[$nameCol]];
        }
        return $out;
    }

    public function fieldNameByNumber(int $fieldNumber): ?string
    {
        $stmt = $this->pdo->prepare('SELECT field_name FROM fields WHERE field_number = ? LIMIT 1');
        $stmt->execute([$fieldNumber]);
        $name = $stmt->fetchColumn();
        return $name === false ? null : (string) $name;
    }

    public function divisionNameByNumber(int $divisionNumber): ?string
    {
        $stmt = $this->pdo->prepare('SELECT division_name FROM divisions_with_coordinators WHERE division_number = ? LIMIT 1');
        $stmt->execute([$divisionNumber]);
        $name = $stmt->fetchColumn();
        return $name === false ? null : (string) $name;
    }

    /**
     * Scheduled matches with no report yet, up to "now" (plus a 5-day
     * lookback), for the match-report.php date/time/field/division pickers.
     *
     * @return list<array{field_row_id:int,field_number:int,field_name:string,division_row_id:int,division_number:int,division_name:string,match_date:string,formatted_match_time:string}>
     */
    public function scheduledMatchPickerOptions(): array
    {
        $now = new DateTime();
        $sql = "SELECT
                    f._id AS field_row_id,
                    f.field_number,
                    f.field_name,
                    d._id AS division_row_id,
                    d.division_number,
                    d.division_name,
                    sm.match_date,
                    DATE_FORMAT(sm.match_time, '%H:%i') AS formatted_match_time
                FROM scheduled_matches AS sm
                LEFT JOIN divisions_with_coordinators AS d ON d._id = sm.division
                LEFT JOIN fields AS f ON f._id = sm.field
                WHERE
                    NOT EXISTS (SELECT 1 FROM match_reports AS mr WHERE mr.match_id = sm._id)
                    AND sm.match_date >= CURDATE() - INTERVAL 5 DAY
                    AND (sm.match_date < :cutoff_date OR (sm.match_date = :cutoff_date2 AND sm.match_time < :cutoff_time))
                ORDER BY sm.match_date ASC, sm.division ASC, sm.field ASC, sm.match_time ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'cutoff_date' => $now->format('Y-m-d'),
            'cutoff_date2' => $now->format('Y-m-d'),
            'cutoff_time' => $now->format('H:i'),
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** The scheduled match matching this exact date/time/field/division, or null. */
    public function matchIdForReporting(string $matchDate, string $matchTime, int $fieldNumber, int $divisionNumber): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT sm._id
             FROM scheduled_matches sm
             JOIN fields f ON sm.field = f._id
             JOIN divisions_with_coordinators d ON sm.division = d._id
             WHERE sm.match_date = ? AND sm.match_time = ? AND f.field_number = ? AND d.division_number = ?'
        );
        $stmt->execute([$matchDate, $matchTime, $fieldNumber, $divisionNumber]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    // -- RAPP admin dashboard: Match data / Scores & game cards --------------

    /**
     * Recent matches with joined report/score data and issue/sanction/gamecard
     * counts. $divisionId scopes to one division; $onlyWithData limits to
     * matches that actually have a report or a sanction.
     *
     * @return list<array<string,mixed>>
     */
    public function matchesWithData(?int $divisionId, int $days = 60, bool $onlyWithData = true): array
    {
        $where = ['sm.match_date >= (CURDATE() - INTERVAL :days DAY)'];
        $params = [':days' => $days];

        if ($divisionId !== null) {
            $where[] = 'sm.division = :div';
            $params[':div'] = $divisionId;
        }

        $having = $onlyWithData ? 'HAVING has_report = 1 OR sanction_count > 0' : '';

        $sql = "SELECT sm._id,
                       sm.match_date, DATE_FORMAT(sm.match_time,'%H:%i') AS match_time,
                       sm.home_team, sm.away_team,
                       d.division_name, d.division_number,
                       f.field_name,
                       o.referee_1, o.referee_2, o.referee_3,
                       mr._id IS NOT NULL                       AS has_report,
                       mr.ref_staffing_issue,
                       mr.match_issue,
                       mr.home_score, mr.away_score,
                       (SELECT COUNT(*) FROM sanctions s WHERE s.match_id = sm._id)  AS sanction_count,
                       (SELECT COUNT(*) FROM gamecards g WHERE g.match_id = sm._id)  AS gamecard_count
                FROM scheduled_matches sm
                LEFT JOIN divisions_with_coordinators d ON d._id = sm.division
                LEFT JOIN fields f ON f._id = sm.field
                LEFT JOIN match_reports mr ON mr.match_id = sm._id
                LEFT JOIN officiants o ON o.match_id = sm._id
                WHERE " . implode(' AND ', $where) . "
                $having
                ORDER BY sm.match_date DESC, sm.match_time DESC
                LIMIT 300";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function sanctionsForMatch(int $scheduledMatchId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sanction_number_in_match, sanction_level, sanctioned_party, party_description, event_description
             FROM sanctions WHERE match_id = ? ORDER BY sanction_number_in_match'
        );
        $stmt->execute([$scheduledMatchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array{image_number:int,filename:string}> */
    public function gamecardsForMatch(int $scheduledMatchId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT image_number, filename FROM gamecards WHERE match_id = ? ORDER BY image_number'
        );
        $stmt->execute([$scheduledMatchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function matchRow(int $scheduledMatchId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT sm._id, sm.match_date, DATE_FORMAT(sm.match_time,'%H:%i') AS match_time,
                    sm.home_team, sm.away_team, sm.division AS division_id,
                    d.division_name, f.field_name,
                    o.referee_1, o.referee_2, o.referee_3,
                    mr.ref_staffing_issue, mr.match_issue, mr.home_score, mr.away_score
             FROM scheduled_matches sm
             LEFT JOIN divisions_with_coordinators d ON d._id = sm.division
             LEFT JOIN fields f ON f._id = sm.field
             LEFT JOIN officiants o ON o.match_id = sm._id
             LEFT JOIN match_reports mr ON mr.match_id = sm._id
             WHERE sm._id = ?"
        );
        $stmt->execute([$scheduledMatchId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // -- match-report notification email --------------------------------

    /**
     * One flat, unpivoted row for the notification email template: sanctions
     * appear as sanction_level_1..5 / sanctioned_party_1..5 / ... columns
     * (up to 5), not a list -- matching what
     * controller-match-report-email.php's getMatchInfoForEvaluation()
     * substitutes directly into the message. A faithful port of the old
     * procedural getMatchDetailsForEmail2() (model-query.php) -- same SQL,
     * same column names, same shape -- not a new design; that function's own
     * comment already called this out for replacement by a registered-user
     * notification system rather than ongoing maintenance here.
     *
     * @param int $matchId The match_reports row id (NOT scheduled_matches._id)
     *        -- matches the original's WHERE mr._id = ?, and what
     *        insertMatchPlayed()'s return value (via insertMatchData()) is.
     * @return array<string,mixed>|null
     */
    public function matchDetailsForEmail(int $matchId): ?array
    {
        $sql = "SELECT sm._id,
                    f.field_number,
                    f.field_name,
                    dvc.division_number,
                    dvc.division_name,
                    dvc.dc_email,
                    sm.match_date,
                    DATE_FORMAT(sm.match_time, '%H:%i') AS match_time,
                    DATE_FORMAT(sm.match_time, '%H-%i') AS file_time,
                    sm.home_team,
                    sm.away_team,
                    o.referee_1,
                    o.referee_2,
                    o.referee_3,
                    mr.ref_staffing_issue,
                    mr.match_issue,
                    g1.filename AS gamecard_filename_1,
                    g2.filename AS gamecard_filename_2,
                    MAX(CASE WHEN s1.sanction_number_in_match = 1 THEN s1.sanction_number_in_match END) AS sanction_number_1,
                    MAX(CASE WHEN s1.sanction_number_in_match = 1 THEN s1.sanction_level END) AS sanction_level_1,
                    MAX(CASE WHEN s1.sanction_number_in_match = 1 THEN s1.sanctioned_party END) AS sanctioned_party_1,
                    MAX(CASE WHEN s1.sanction_number_in_match = 1 THEN s1.party_description END) AS party_description_1,
                    MAX(CASE WHEN s1.sanction_number_in_match = 1 THEN s1.event_description END) AS event_description_1,
                    MAX(CASE WHEN s1.sanction_number_in_match = 2 THEN s1.sanction_number_in_match END) AS sanction_number_2,
                    MAX(CASE WHEN s1.sanction_number_in_match = 2 THEN s1.sanction_level END) AS sanction_level_2,
                    MAX(CASE WHEN s1.sanction_number_in_match = 2 THEN s1.sanctioned_party END) AS sanctioned_party_2,
                    MAX(CASE WHEN s1.sanction_number_in_match = 2 THEN s1.party_description END) AS party_description_2,
                    MAX(CASE WHEN s1.sanction_number_in_match = 2 THEN s1.event_description END) AS event_description_2,
                    MAX(CASE WHEN s1.sanction_number_in_match = 3 THEN s1.sanction_level END) AS sanction_level_3,
                    MAX(CASE WHEN s1.sanction_number_in_match = 3 THEN s1.sanctioned_party END) AS sanctioned_party_3,
                    MAX(CASE WHEN s1.sanction_number_in_match = 3 THEN s1.party_description END) AS party_description_3,
                    MAX(CASE WHEN s1.sanction_number_in_match = 3 THEN s1.event_description END) AS event_description_3,
                    MAX(CASE WHEN s1.sanction_number_in_match = 3 THEN s1.sanction_number_in_match END) AS sanction_number_3,
                    MAX(CASE WHEN s1.sanction_number_in_match = 4 THEN s1.sanction_level END) AS sanction_level_4,
                    MAX(CASE WHEN s1.sanction_number_in_match = 4 THEN s1.sanctioned_party END) AS sanctioned_party_4,
                    MAX(CASE WHEN s1.sanction_number_in_match = 4 THEN s1.party_description END) AS party_description_4,
                    MAX(CASE WHEN s1.sanction_number_in_match = 4 THEN s1.event_description END) AS event_description_4,
                    MAX(CASE WHEN s1.sanction_number_in_match = 4 THEN s1.sanction_number_in_match END) AS sanction_number_4,
                    MAX(CASE WHEN s1.sanction_number_in_match = 5 THEN s1.sanction_level END) AS sanction_level_5,
                    MAX(CASE WHEN s1.sanction_number_in_match = 5 THEN s1.sanctioned_party END) AS sanctioned_party_5,
                    MAX(CASE WHEN s1.sanction_number_in_match = 5 THEN s1.party_description END) AS party_description_5,
                    MAX(CASE WHEN s1.sanction_number_in_match = 5 THEN s1.event_description END) AS event_description_5,
                    MAX(CASE WHEN s1.sanction_number_in_match = 5 THEN s1.sanction_number_in_match END) AS sanction_number_5
                FROM scheduled_matches AS sm
                LEFT JOIN divisions_with_coordinators AS dvc ON dvc._id = sm.division
                LEFT JOIN match_reports AS mr ON mr.match_id = sm._id
                LEFT JOIN fields AS f ON sm.field = f._id
                LEFT JOIN officiants AS o ON sm._id = o.match_id
                LEFT JOIN gamecards AS g1 ON sm._id = g1.match_id AND g1.image_number = 1
                LEFT JOIN gamecards AS g2 ON sm._id = g2.match_id AND g2.image_number = 2
                LEFT JOIN sanctions AS s1 ON sm._id = s1.match_id
                WHERE mr._id = :matchId
                GROUP BY sm._id, f._id, dvc._id, mr._id, o._id, g1._id, g2._id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['matchId' => $matchId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
