<?php

function queryRecords($tableName, $idColumn = null, $idValue = null, $columnsToReturnString = "*", $additionalParameters = null) {
    // Build the base query
    $statementForQuery = "SELECT " . $columnsToReturnString . " FROM " . $tableName . " ";

    // Check if a specific record is being queried by ID
    if ($idColumn && $idValue) {
        $statementForQuery .= "WHERE " . $idColumn . " = ?";
    } elseif ($additionalParameters) {
        // Ensure that $additionalParameters is safe to use directly in the query
        $statementForQuery .= $additionalParameters;
    }

    global $dBConnection;
    $stmt = $dBConnection->prepare($statementForQuery);
    if (!$stmt) {
        die('Prepare failed: ' . $dBConnection->error);
    }

    // Bind ID value if provided
    if ($idColumn && $idValue) {
        $stmt->bind_param("i", $idValue);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Check if query was successful and return the results
    if (!$result) {
        $stmt->close();
        return null; // Return null if no rows found
    }

    $records = $result->fetch_all(MYSQLI_ASSOC); // Fetch as associative array
    $stmt->close();
    return $records;
}

function checkMatchWasReported($matchId){
    return queryRecords('match_reports', 'match_id', $matchId, 'match_id');
}

function selectFieldNamesAndNumbersForUserInterface(){
    return queryRecords('fields', 'field_active', 1, 'field_number , field_name');
}

function selectDivisionNamesAndNumbersForUserInterface(){
    return queryRecords('divisions_with_coordinators', 'division_active', 1, 'division_number , division_name');
}

function getFieldNameByFieldNumber($fieldNumber){
    return queryRecords('fields', 'field_number', $fieldNumber, 'field_name');
}

function getDivisionNameByDivisionNumber($divisionNumber){
    return queryRecords('divisions', 'division_number', $divisionNumber, 'division_name');    
}

function selectScheduledMatchOptionsFromDatabaseEXP(){
    global $dBConnection;
    // Start the transaction
    $dBConnection->begin_transaction();
    $dateTime = new DateTime();
    $currentDateFromSys = $dateTime->format('Y-m-d');
    $currentTimeFromSys = $dateTime->format('H:i');

    try {
        $sql = "SELECT 
            f.field_number,
            f.field_name,
            d.division_number,
            d.division_name,
            sm.match_date,
            DATE_FORMAT(sm.match_time, '%H:%i') AS formatted_match_time
        FROM scheduled_matches AS sm
        LEFT JOIN divisions_with_coordinators AS d
            ON d._id = sm.division
        LEFT JOIN fields AS f
            ON f._id = sm.field
        WHERE
            -- no report yet for this match
            NOT EXISTS (
                SELECT 1 
                FROM match_reports AS mr
                WHERE mr.match_id = sm._id
            )
            -- only look back 5 days
            AND sm.match_date >= CURDATE() - INTERVAL 5 DAY
            -- earlier than the cutoff date+time
            AND (
                sm.match_date < ?
                OR (sm.match_date = ? AND sm.match_time < ?)
            )
        ORDER BY
            sm.match_date ASC,
            sm.division ASC,
            sm.field ASC,
            sm.match_time ASC
        ";
        $stmt = $dBConnection->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $dBConnection->error);
        }
        $stmt->bind_param("sss", $currentDateFromSys,  $currentDateFromSys, $currentTimeFromSys);  //-----------------------------------------------------------------------------
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            throw new Exception('Query failed: ' . $stmt->error);
        }

        // Fetch all the data
        $data = $result->fetch_all(MYSQLI_ASSOC);

        // Commit the transaction
        $dBConnection->commit();
        $stmt->close();

        return $data;
    } catch (Exception $e) {
        // Rollback the transaction on error
        $dBConnection->rollback();
        throw $e;
    }
}

function getMatchIdForReporting($matchDate, $matchTime, $field, $division){
    global $dBConnection;
    // Start the transaction
    $dBConnection->begin_transaction();

    try {
        $sql = "SELECT 
            sm._id
        FROM 
            scheduled_matches sm
        JOIN fields f ON sm.field = f._id
        JOIN divisions_with_coordinators d ON sm.division = d._id
        WHERE 
            sm.match_date = ? 
            AND sm.match_time = ?
            AND f.field_number = ?
            AND d.division_number = ?;
        "; 

        $stmt = $dBConnection->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $dBConnection->error);
        }
        $stmt->bind_param("ssii", $matchDate, $matchTime, $field, $division);  //-----------------------------------------------------------------------------
        $stmt->execute();
        if ($stmt->error) {
            error_log("Execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();

        if (!$result) {
            throw new Exception('Query failed: ' . $stmt->error);
        }

        // Fetch all the data
        // $data = $result->fetch_all(MYSQLI_ASSOC);
        // Fetch the single row (assuming there is only one)
        $data = $result->fetch_assoc();

        // Commit the transaction
        $dBConnection->commit();
        $stmt->close();

        // If no data is found, return null or handle accordingly
        return $data ? $data['_id'] : null;
    } catch (Exception $e) {
        // Rollback the transaction on error
        $dBConnection->rollback();
        throw $e;
    }
}

function queryRecords2($tableName, $idColumn, $idValue, $columnsToReturnString, $additionalParameters) {
    // Build the base query
    $statementForQuery = "SELECT " . $columnsToReturnString . " FROM " . $tableName . " ";

    // Check if a specific record is being queried by ID
    if ($additionalParameters) {
        // Ensure that $additionalParameters is safe to use directly in the query
        $statementForQuery .= $additionalParameters;
    }
    if ($idColumn && $idValue) {
        $statementForQuery .= "WHERE " . $idColumn . " = ?";
    } 

    global $dBConnection;
    $stmt = $dBConnection->prepare($statementForQuery);
    if (!$stmt) {
        die('Prepare failed: ' . $dBConnection->error);
    }

    // Bind ID value if provided
    if ($idColumn && $idValue) {
        $stmt->bind_param("i", $idValue);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Check if query was successful and return the results
    if (!$result) {
        $stmt->close();
        return null; // Return null if no rows found
    }
    
    $records = $result->fetch_assoc();
    $stmt->close();
    return $records;
}

function getMatchDetailsForEmail2($matchId){
    $columnsToReturnString = 'sm._id,
    f.field_number,
    f.field_name,
    dvc.division_number,
    dvc.division_name,
    dvc.dc_email,
    sm.match_date,
    DATE_FORMAT(sm.match_time, \'%H:%i\') AS match_time, 
    DATE_FORMAT(sm.match_time, \'%H-%i\') AS file_time,
    sm.home_team,
    sm.away_team,
    o.referee_1,
    o.referee_2,
    o.referee_3,
    mr.ref_staffing_issue,
    mr.match_issue,
    g1.filename AS gamecard_filename_1,
    g2.filename AS gamecard_filename_2,
    dvc.dc_email,
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
    MAX(CASE WHEN s1.sanction_number_in_match = 5 THEN s1.sanction_number_in_match END) AS sanction_number_5';
    $additionalParameters = ' LEFT JOIN `divisions_with_coordinators` AS dvc ON dvc._id = sm.division
    LEFT JOIN `match_reports` AS mr ON mr.match_id = sm._id
    LEFT JOIN `fields` AS f ON sm.field = f._id
    LEFT JOIN `officiants` AS o ON sm._id = o.match_id
    LEFT JOIN `gamecards` AS g1 ON sm._id = g1.match_id AND g1.image_number = 1
    LEFT JOIN `gamecards` AS g2 ON sm._id = g2.match_id AND g2.image_number = 2
    LEFT JOIN `sanctions` AS s1 ON sm._id = s1.match_id ';

    return queryRecords2('scheduled_matches AS sm', 'mr._id', $matchId, $columnsToReturnString, $additionalParameters);
}

function getMatchDetailsForEmail($matchIdPlayed) {
    global $dBConnection;

    // Start the transaction
    $dBConnection->begin_transaction();

    try {
        // Prepare the SQL query with multiple joins

        $sql = "
        SELECT 
            sm._id,
            sm.field,
            dvc.division_name,
            sm.match_date,
            sm.match_time,
            sm.home_team,
            sm.away_team,
            o.referee_1,
            o.referee_2,
            o.referee_3,
            mr.ref_staffing_issue,
            mr.match_issue,
            g1.filename AS gamecard_filename_1,
            g2.filename AS gamecard_filename_2,
            dvc.dc_email
        FROM `scheduled_matches` AS sm
        LEFT JOIN `divisions_with_coordinators` AS dvc ON dvc._id = sm.division
        LEFT JOIN `match_reports` AS mr ON mr.match_id = sm._id
        LEFT JOIN `officiants` AS o ON sm._id = o.match_id
        LEFT JOIN `gamecards` AS g1 ON sm._id = g1.match_id AND g1.image_number = 1
        LEFT JOIN `gamecards` AS g2 ON sm._id = g2.match_id AND g2.image_number = 2
        WHERE mr._id = ?;
        "; 

        /* $sql = "
        SELECT 
            m.field,
            m.division,
            m.match_date,
            m.match_time,
            DATE_FORMAT(m.match_time, '%H-%i') AS match_time, 
            m.ref_staffing_issue,
            m.match_issue,
            o.referee_1,
            o.referee_2,
            o.referee_3,
            g1.filename AS gamecard_filename_1,
            g2.filename AS gamecard_filename_2,
            s1.sanction_number_in_match AS sanction_1,
            s1.sanction_level AS sanction_level_1,
            s1.sanctioned_party AS sanctioned_party_1,
            s1.party_description AS party_description_1,
            s1.event_description AS event_description_1,
            s2.sanction_number_in_match AS sanction_2,
            s2.sanction_level AS sanction_level_2,
            s2.sanctioned_party AS sanctioned_party_2,
            s2.party_description AS party_description_2,
            s2.event_description AS event_description_2,
            s3.sanction_number_in_match AS sanction_3,
            s3.sanction_level AS sanction_level_3,
            s3.sanctioned_party AS sanctioned_party_3,
            s3.party_description AS party_description_3,
            s3.event_description AS event_description_3,
            s4.sanction_number_in_match AS sanction_4,
            s4.sanction_level AS sanction_level_4,
            s4.sanctioned_party AS sanctioned_party_4,
            s4.party_description AS party_description_4,
            s4.event_description AS event_description_4,
            s4.sanction_number_in_match AS sanction_5,
            s5.sanction_level AS sanction_level_5,
            s5.sanctioned_party AS sanctioned_party_5,
            s5.party_description AS party_description_5,
            s5.event_description AS event_description_5
        FROM matches AS m
        LEFT JOIN officiants AS o ON m._id = o.match_id
        LEFT JOIN gamecards AS g1 ON m._id = g1.match_id AND g1.image_number = 1
        LEFT JOIN gamecards AS g2 ON m._id = g2.match_id AND g2.image_number = 2
        LEFT JOIN sanctions AS s1 ON m._id = s1.match_id AND s1.sanction_number_in_match = 1
        LEFT JOIN sanctions AS s2 ON m._id = s2.match_id AND s2.sanction_number_in_match = 2
        LEFT JOIN sanctions AS s3 ON m._id = s3.match_id AND s3.sanction_number_in_match = 3
        LEFT JOIN sanctions AS s4 ON m._id = s4.match_id AND s4.sanction_number_in_match = 4
        LEFT JOIN sanctions AS s5 ON m._id = s5.match_id AND s5.sanction_number_in_match = 5
        WHERE m._id = ?
        AND (s1.sanction_level IS NOT NULL OR s2.sanction_level IS NOT NULL OR s3.sanction_level IS NOT NULL OR s4.sanction_level IS NOT NULL OR s5.sanction_level IS NOT NULL)
        ";  */

        $stmt = $dBConnection->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $dBConnection->error);
        }
        $stmt->bind_param("i", $matchId);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            error_log('Query failed: ' . $stmt->error);
            throw new Exception('Query failed: ' . $stmt->error);
        }

        // Fetch all the data
        // $data = $result->fetch_all(MYSQLI_ASSOC);
        // Fetch the single row (assuming there is only one)
        $data = $result->fetch_assoc();
        
        error_log('the results returned from the query, in the query ='.print_r($data, true));

        // Commit the transaction
        $dBConnection->commit();
        $stmt->close();

        return $data;
    } catch (Exception $e) {
        // Rollback the transaction on error
        $dBConnection->rollback();
        throw $e;
    }
}