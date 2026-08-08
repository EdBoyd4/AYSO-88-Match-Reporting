<?php

$dBConnection = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($dBConnection->connect_error) die($dBConnection->connect_error);


function insert($table, $columns, $values, $types) {
    global $dBConnection;
    
    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $columnsList = implode(',', $columns);
    $sql = "INSERT INTO $table ($columnsList) VALUES ($placeholders)";
    error_log($sql);
    error_log(implode(',', $values));
    
    
    $stmt = $dBConnection->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . $dBConnection->error);
    }
    
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    if ($stmt->errno) {
        die('Execute failed: ' . $stmt->error);
    }
    
    $insertId = $dBConnection->insert_id;
    $stmt->close();
    
    return $insertId;
}

function prepareInsertData($data, $columnTypes) {
    $processedValues = [];
    $types = '';

    foreach ($data as $key => $value) {
        if (isset($value)) {
            $processedValues[] = $value;
            $types .= $columnTypes[$key];
        } else {
            $processedValues[] = null;
            $types .= 's'; // Assuming 's' for string type, adjust if necessary
        }
    }
    
    return [$processedValues, $types];
}

// matches
function insertMatchPlayed($matchId, $refStaffingIssue = null, $matchIssue = null) {
    $table = 'match_reports';
    
    $data = [
        'match_id' => $matchId,
    ];
    
    // Define the expected types for each column
    $columnTypes = [
        'match_id' => 'i',
    ];

    if(!($refStaffingIssue === null)){
        $data['ref_staffing_issue'] = $refStaffingIssue;
        $columnTypes['ref_staffing_issue'] = 's';
    }

    if(!($matchIssue === null)){
        $data['match_issue'] = $matchIssue;
        $columnTypes['match_issue'] = 's';
    }
    
    // Filter out null values from both data and columnTypes
    $filteredData = array_filter($data, function($value) {
        return $value !== null;
    });

    // Filter the column types accordingly
    $filteredColumnTypes = array_intersect_key($columnTypes, $filteredData);
    
    // Extract the keys (column names) after filtering
    $columns = array_keys($filteredData);

    list($values, $types) = prepareInsertData($filteredData, $filteredColumnTypes);
    
    return insert($table, $columns, $values, $types);
}

// gamecards
function insertGameCardFileInfo($match_id, $image_number, $filename) {
    $table = 'gamecards';
    
    $data = [
        'match_id' => $match_id, 
        'image_number' => $image_number, 
        'filename' => $filename
    ];
    
    // Define the expected types for each column
    $columnTypes = [
        'match_id' => 'i', 
        'image_number' => 'i', 
        'filename' => 's'
    ];
    
    // Filter out null values from both data and columnTypes
    $filteredData = array_filter($data, function($value) {
        return $value !== null;
    });

    // Filter the column types accordingly
    $filteredColumnTypes = array_intersect_key($columnTypes, $filteredData);
    
    // Extract the keys (column names) after filtering
    $columns = array_keys($filteredData);

    list($values, $types) = prepareInsertData($filteredData, $filteredColumnTypes);
    
    return insert($table, $columns, $values, $types);
}

//officiants
function insertRefs($match_id, $referee_1, $referee_2 = null, $referee_3 = null) {
    $table = 'officiants';
    
    $data = [
        'match_id' => $match_id, 
        'referee_1' => $referee_1
    ];
    
    // Define the expected types for each column
    $columnTypes = [
        'match_id' => 'i', 
        'referee_1' => 's'
    ];
    
    if(!($referee_2 === null)){
        $data['referee_2'] = $referee_2;
        $columnTypes['referee_2'] = 's';
    }

    if(!($referee_3 === null)){
        $data['referee_3'] = $referee_3;
        $columnTypes['referee_3'] = 's';
    }
    
    // Filter out null values from both data and columnTypes
    $filteredData = array_filter($data, function($value) {
        return $value !== null;
    });

    // Filter the column types accordingly
    $filteredColumnTypes = array_intersect_key($columnTypes, $filteredData);
    
    // Extract the keys (column names) after filtering
    $columns = array_keys($filteredData);

    list($values, $types) = prepareInsertData($filteredData, $filteredColumnTypes);
    
    return insert($table, $columns, $values, $types);
}

// sanctions
function insertSanction($match_id, $sanctionNumber, $sanction_level, $sanctioned_party, $party_description, $event_description) {
    $table = 'sanctions';
    
    $data = [
        'match_id' => $match_id, 
        'sanction_number_in_match' => $sanctionNumber,
        'sanction_level' => $sanction_level, 
        'sanctioned_party' => $sanctioned_party, 
        'party_description' => $party_description, 
        'event_description' => $event_description
    ];
    
    // Define the expected types for each column
    $columnTypes = [
        'match_id' => 'i', 
        'sanction_number_in_match' => 'i',
        'sanction_level' => 'i', 
        'sanctioned_party' => 'i', 
        'party_description' => 's', 
        'event_description' => 's'
    ];
    
    // Filter out null values from both data and columnTypes
    $filteredData = array_filter($data, function($value) {
        return $value !== null;
    });

    // Filter the column types accordingly
    $filteredColumnTypes = array_intersect_key($columnTypes, $filteredData);
    
    // Extract the keys (column names) after filtering
    $columns = array_keys($filteredData);

    list($values, $types) = prepareInsertData($filteredData, $filteredColumnTypes);
    
    return insert($table, $columns, $values, $types);
}

function insertMatchData($headerDataItems) {
    global $dBConnection;
    
    try {
        // Start the transaction
        $dBConnection->begin_transaction();
        
        // Extract data from the arrays
        /* $pitch = $headerDataItems['matchSelection'];

        $ageLevel = $headerDataItems['teamDivision'];
        $datePlayed = $headerDataItems['matchDate'];
        $startTime = $headerDataItems['matchTime']; */
        $refStaffingIssue = $headerDataItems['refStaffingIssueText'] ?? null;
        $matchIssue = $headerDataItems['matchIssueText'] ?? null;
        
        //$matchIdScheduled = getMatchIdForReporting($datePlayed, $startTime, $ageLevel, $pitch);

        // Insert the match
        $matchIdPlayed = insertMatchPlayed($headerDataItems['scheduled_match_id'], $refStaffingIssue, $matchIssue);
        
        // Extract officiants data
        $referee_1 = $headerDataItems['Ref1'];
        $referee_2 = $headerDataItems['Ref2'] ?? null;
        $referee_3 = $headerDataItems['Ref3'] ?? null;
        
        // Insert officiants
        $refCrewId = insertRefs($headerDataItems['scheduled_match_id'], $referee_1, $referee_2, $referee_3);
        
        // Insert gamecard filenames 
        function insertGameCardFiles($matchIdPlayed, $headerDataItems) {
            $imageNumbers = ['image_number1', 'image_number2'];
            $filenames = ['gameCardsPhoto1', 'gameCardsPhoto2'];
        
            foreach ($imageNumbers as $index => $imageKey) {
                $image_number = $headerDataItems[$imageKey];
                $filename = $headerDataItems[$filenames[$index]];
                insertGameCardFileInfo($matchIdPlayed, $image_number, $filename);
            }
        }

        insertGameCardFiles($headerDataItems['scheduled_match_id'], $headerDataItems);



        // Insert sanctions, if any
        if ($headerDataItems['sanctionAmount'] > 0) {
            error_log('header sanction amount'.$headerDataItems['sanctionAmount']);
            for ($i = 1; $i <= $headerDataItems['sanctionAmount']; $i++) {
            error_log('sanction number '.$i);
                // Sanction level
                $sanctionLevelKey = 'sanctionLevel'. $i;
                $sanctionPartyTypeKey = 'sanctionParty' . $i;
                $sanctionPartyDescriptionKey = 'sanction-party-description-' . $i;
                $sanctionCauseSummaryKey = 'sanction' . $i . 'SummaryText';
                $sanctionNumber = $i;
                $sanction_level = $headerDataItems[$sanctionLevelKey];
                $sanctioned_party = $headerDataItems[$sanctionPartyTypeKey];
                $party_description = $headerDataItems[$sanctionPartyDescriptionKey];
                $event_description = $headerDataItems[$sanctionCauseSummaryKey ];
                insertSanction($headerDataItems['scheduled_match_id'], $sanctionNumber, $sanction_level, $sanctioned_party, $party_description, $event_description);
            }
        }
        
        // Commit the transaction
        $dBConnection->commit();
error_log('$matchIdPlayed = '.$matchIdPlayed);
        return $matchIdPlayed;
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $dBConnection->rollback();
        // Log or handle the error as needed
        error_log('Error inserting match data: ' . $e->getMessage());
        throw $e; // Rethrow the exception or handle it as necessary
    }
}

