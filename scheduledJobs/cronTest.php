<?php

$dateTime = new DateTime();
$currentDateFromSys = $dateTime->format('Y-m-d');
$currentTimeFromSys = $dateTime->format('H:i');

error_log('cron daemon ran on '.$currentDateFromSys.' at '.$currentTimeFromSys);