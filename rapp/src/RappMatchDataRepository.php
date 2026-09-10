<?php
declare(strict_types=1);

/**
 * Read-only access to gss88's existing match tables for the non-RAPP sections
 * of the dashboard: staffing / match issues / sanctions / scores / game cards.
 * Kept separate from RappReportRepository so the RAPP-sensitive queries and the
 * routine match queries don't share a class.
 */
final class RappMatchDataRepository
{
    public function __construct(private PDO $pdo) {}

    /** @return list<array{id:int,number:int,name:string}> */
    public function divisions(): array
    {
        $stmt = $this->pdo->query(
            'SELECT _id, division_number, division_name
             FROM divisions_with_coordinators
             WHERE division_active = 1
             ORDER BY division_number'
        );
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[] = ['id' => (int) $r['_id'], 'number' => (int) $r['division_number'], 'name' => (string) $r['division_name']];
        }
        return $out;
    }

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
}
