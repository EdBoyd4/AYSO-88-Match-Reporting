<?php
declare(strict_types=1);

/**
 * All writes to gss88's match domain (match_reports, officiants, sanctions,
 * gamecards) -- the public match-report submission form's (public/match-
 * report.php) only write path. Read access to the same domain is
 * MatchDataQueries (model-match-data-query.php); this class has no read
 * methods, matching the split in the RAPP module (RappReportInserter /
 * RappReportQueries).
 *
 * A faithful PDO port of the old mysqli-based procedural functions here
 * (insert(), prepareInsertData(), insertMatchPlayed(), ...): same tables,
 * same columns, same "omit a null field from the INSERT entirely rather than
 * writing NULL" behavior, same $headerDataItems input shape from
 * controller-match-report-sanitize-and-enter.php's getHeaderDataFromPOST().
 * The mysqli bind-type-string machinery (prepareInsertData()'s $columnTypes)
 * doesn't carry over -- PDO's prepared statements don't need it, so it's
 * gone rather than ported for its own sake.
 */
final class MatchDataInserter
{
    public function __construct(private PDO $pdo) {}

    /**
     * Insert one row, omitting any column whose value is null (relies on that
     * column's own DB default rather than writing NULL explicitly) -- same
     * behavior the old insert()/prepareInsertData() pair had.
     *
     * @param array<string,mixed> $data column => value
     */
    private function insertRow(string $table, array $data): int
    {
        $data = array_filter($data, static fn ($v) => $v !== null);
        $columns = array_keys($data);
        $placeholders = implode(',', array_map(static fn ($c) => ':' . $c, $columns));
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    // matches
    public function insertMatchPlayed(int $matchId, ?string $refStaffingIssue = null, ?string $matchIssue = null, ?int $homeScore = null, ?int $awayScore = null): int
    {
        return $this->insertRow('match_reports', [
            'match_id' => $matchId,
            'ref_staffing_issue' => $refStaffingIssue,
            'match_issue' => $matchIssue,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ]);
    }

    // gamecards
    public function insertGameCardFileInfo(int $matchId, int $imageNumber, string $filename): int
    {
        return $this->insertRow('gamecards', [
            'match_id' => $matchId,
            'image_number' => $imageNumber,
            'filename' => $filename,
        ]);
    }

    // officiants
    public function insertRefs(int $matchId, string $referee1, ?string $referee2 = null, ?string $referee3 = null): int
    {
        return $this->insertRow('officiants', [
            'match_id' => $matchId,
            'referee_1' => $referee1,
            'referee_2' => $referee2,
            'referee_3' => $referee3,
        ]);
    }

    // sanctions
    public function insertSanction(int $matchId, int $sanctionNumber, $sanctionLevel, $sanctionedParty, ?string $partyDescription, ?string $eventDescription): int
    {
        return $this->insertRow('sanctions', [
            'match_id' => $matchId,
            'sanction_number_in_match' => $sanctionNumber,
            'sanction_level' => $sanctionLevel,
            'sanctioned_party' => $sanctionedParty,
            'party_description' => $partyDescription,
            'event_description' => $eventDescription,
        ]);
    }

    /**
     * Inserts the match report, officiants, gamecard filenames, and any
     * sanctions as one transaction. Returns the new match_reports row id
     * (NOT scheduled_matches._id -- matches the original's return value,
     * which controller-match-report-email.php's matchDetailsForEmail() call
     * depends on).
     *
     * @param array<string,mixed> $headerDataItems Shaped by
     *        controller-match-report-sanitize-and-enter.php's
     *        getHeaderDataFromPOST().
     * @throws \Throwable Rethrown after rollback, same as the original.
     */
    public function insertMatchData(array $headerDataItems): int
    {
        try {
            $this->pdo->beginTransaction();

            $refStaffingIssue = $headerDataItems['refStaffingIssueText'] ?? null;
            $matchIssue = $headerDataItems['matchIssueText'] ?? null;
            $homeScore = $headerDataItems['homeScore'] ?? null;
            $awayScore = $headerDataItems['awayScore'] ?? null;

            $matchIdPlayed = $this->insertMatchPlayed(
                (int) $headerDataItems['scheduled_match_id'],
                $refStaffingIssue,
                $matchIssue,
                $homeScore === null ? null : (int) $homeScore,
                $awayScore === null ? null : (int) $awayScore
            );

            $referee1 = $headerDataItems['Ref1'];
            $referee2 = $headerDataItems['Ref2'] ?? null;
            $referee3 = $headerDataItems['Ref3'] ?? null;
            $this->insertRefs((int) $headerDataItems['scheduled_match_id'], $referee1, $referee2, $referee3);

            $this->insertGameCardFiles((int) $headerDataItems['scheduled_match_id'], $headerDataItems);

            if ($headerDataItems['sanctionAmount'] > 0) {
                error_log('header sanction amount' . $headerDataItems['sanctionAmount']);
                for ($i = 1; $i <= $headerDataItems['sanctionAmount']; $i++) {
                    error_log('sanction number ' . $i);
                    $sanctionLevelKey = 'sanctionLevel' . $i;
                    $sanctionPartyTypeKey = 'sanctionParty' . $i;
                    $sanctionPartyDescriptionKey = 'sanction-party-description-' . $i;
                    $sanctionCauseSummaryKey = 'sanction' . $i . 'SummaryText';
                    $this->insertSanction(
                        (int) $headerDataItems['scheduled_match_id'],
                        $i,
                        $headerDataItems[$sanctionLevelKey],
                        $headerDataItems[$sanctionPartyTypeKey],
                        $headerDataItems[$sanctionPartyDescriptionKey],
                        $headerDataItems[$sanctionCauseSummaryKey]
                    );
                }
            }

            $this->pdo->commit();
            error_log('$matchIdPlayed = ' . $matchIdPlayed);
            return $matchIdPlayed;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('Error inserting match data: ' . $e->getMessage());
            throw $e;
        }
    }

    private function insertGameCardFiles(int $scheduledMatchId, array $headerDataItems): void
    {
        $imageNumbers = ['image_number1', 'image_number2'];
        $filenames = ['gameCardsPhoto1', 'gameCardsPhoto2'];

        foreach ($imageNumbers as $index => $imageKey) {
            $imageNumber = $headerDataItems[$imageKey];
            $filename = $headerDataItems[$filenames[$index]];
            $this->insertGameCardFileInfo($scheduledMatchId, (int) $imageNumber, $filename);
        }
    }
}
