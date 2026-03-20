<?php

// Party Sanctioned Options and Items
define('SANCTIONED_PARTY_OPTIONS', [
    ['label' => 'player', 'text' => 'A Player'],
    ['label' => 'coach', 'text' => 'A Coach'],
]);

define('SANCTIONED_PARTY_ITEMS', [
    ['label' => 'none', 'text' => ''],
    ['label' => 'player', 'text' => 'Player'],
    ['label' => 'coach', 'text' => 'Coach'],
]);

define('DISRUPTIVE_PARTY_ITEMS', [
    ['label' => 'none', 'text' => ''],
    ['label' => 'player', 'text' => 'Player'],
    ['label' => 'coach', 'text' => 'Coach'],
    ['label' => 'fan', 'text' => 'Spectator'],
]);

// Sanction Level Options and Items
define('SANCTION_LEVEL_OPTIONS', [
    ['label' => 'yellow', 'text' => 'A Caution'],
    ['label' => 'red', 'text' => 'A Send-Off'],
]);

define('SANCTION_LEVEL_ITEMS', [
    ['label' => 'none', 'text' => ''],
    ['label' => 'yellow', 'text' => 'Caution'],
    ['label' => 'red', 'text' => 'Send-Off'],
]);

define('SANCTION_LEVEL_DETAILED_ITEMS', [
    ['label' => 'none', 'text' => ''],
    ['label' => 'warning', 'text' => 'Strong Warning'],
    ['label' => 'yelPlay', 'text' => 'Caution - Playing'],
    ['label' => 'yelSub', 'text' => 'Caution - Sub'],
    ['label' => 'red', 'text' => 'Send-Off'],
]);

// Reason for Caution: Coach Violations
define('CAUTION_COACH_VIOLATION_ITEMS', [
    ['label' => 'none', 'text' => ''],
    ['label' => 'cC0Report', 'text' => 'Delay of Restart'],
    ['label' => 'cC1Report', 'text' => 'Dissent'],
    ['label' => 'cC2Report', 'text' => 'Entered, Re-entered, or Left Field'],
    ['label' => 'cC3Report', 'text' => 'Persistent Offenses'],
    ['label' => 'cC4Report', 'text' => 'Repeated Warnings'],
    ['label' => 'cC5Report', 'text' => 'Unsporting Behaviour'],
    ['label' => 'cC6Report', 'text' => 'Entered the RRA'],
    ['label' => 'cC7Report', 'text' => 'Excessive VAR Signaling'],
]);

// Reason for Caution: Player Violations
define('CAUTION_PLAYER_VIOLATION_ITEMS', [
    ['label' => 'none', 'text' => ''],
    ['label' => 'c0Report', 'text' => 'Delay of Restart'],
    ['label' => 'c1Report', 'text' => 'Dissent'],
    ['label' => 'c2Report', 'text' => 'Entered, Re-entered, or Left Field'],
    ['label' => 'c3Report', 'text' => 'Failed to Respect Min. Distance'],
    ['label' => 'c4Report', 'text' => 'Persistent Offenses'],
    ['label' => 'c5Report', 'text' => 'Repeated Warnings'],
    ['label' => 'c6Report', 'text' => 'Unsporting Behaviour'],
    ['label' => 'c7Report', 'text' => 'DOGSO - Attempt to Play, with PK'],
    ['label' => 'c8Report', 'text' => 'Entered the RRA'],
    ['label' => 'c9Report', 'text' => 'Excessive VAR Signaling'],
]);

// Repeated Warnings - Player, Coach, or Substitute
define('REPEATED_WARNINGS_ITEMS', [
    ['label' => 'none', 'text' => ''],
    ['label' => 'rW0', 'text' => 'Respectfully Entered the Field'],
    ['label' => 'rW1', 'text' => 'Failed to Cooperate with an Official'],
    ['label' => 'rW2', 'text' => 'Indicated Disagreement at a Minor/Low Level'],
    ['label' => 'rW3', 'text' => 'Left the Technical Area'],
]);

// Unsporting Caution – Type
define('UNSPORTING_CAUTION_ITEMS', [
    ['label' => 'none', 'text' => ''],
    ['label' => 'un0', 'text' => 'Simulation'],
    ['label' => 'un1', 'text' => 'Changed Places with the Keeper'],
    ['label' => 'un2', 'text' => 'Reckless DFK Offense'],
    ['label' => 'un3', 'text' => 'Foiled a Promising Attack via Handball'],
    ['label' => 'un4', 'text' => 'Foiled a Promising Attack via non-PK'],
    ['label' => 'un5', 'text' => 'DOGSO - Attempt to Play, with PK'],
    ['label' => 'un6', 'text' => 'Handball - to Score or Prevent a Goal'],
    ['label' => 'un7', 'text' => 'Made Unauthorized Marks'],
    ['label' => 'un8', 'text' => 'Played the Ball While Leaving'],
    ['label' => 'un9', 'text' => 'Disrespecting the Game'],
    ['label' => 'un10', 'text' => 'Deliberately initiated a trick to pass to the Keeper'],
    ['label' => 'un11', 'text' => 'Verbally Distracted Opponent'],
    ['label' => 'un12', 'text' => 'Excessively Celebrated a Goal'],
    ['label' => 'un13', 'text' => 'Dangerously Celebrated a Goal'],
    ['label' => 'un14', 'text' => 'Rudely Celebrated a Goal - provoked/derided/inflamed'],
    ['label' => 'un15', 'text' => 'Covered head or face with Mask(etc.) as Celebrated a Goal'],
    ['label' => 'un16', 'text' => 'Removed Shirt / Put it over head as Celebrated a Goal'],
    ['label' => 'un17', 'text' => 'Threw Object or Ball Recklessly'],
]);

// Delay of Restart – Type
define('DELAY_OF_RESTART_ITEMS', [
    ['label' => 'none', 'text' => ''],
    ['label' => 'dOR0', 'text' => 'Feinted a Throw-in'],
    ['label' => 'dOR1', 'text' => 'Delayed Leaving the Field'],
    ['label' => 'dOR2', 'text' => 'Delayed a Restart Excessively'],
    ['label' => 'dOR3', 'text' => 'Kicked / Carried Ball Away, / touched the ball during stoppage (with confrontation)'],
    ['label' => 'dOR4', 'text' => 'Deliberately took FK from wrong position'],
]);

// Reason for Send-off ---------------------------------need kick or trip?
define('REASON_FOR_SENDOFF_ITEMS', [
    ['label' => 'none', 'text' => ''],
    ['label' => 'sO0', 'text' => 'Serious Foul Play'],
    ['label' => 'sO1', 'text' => 'Violent Conduct'],
    ['label' => 'sO2', 'text' => 'Bit or Spit at Someone'],
    ['label' => 'sO3', 'text' => 'Physical or Aggressive Behavior towards ANYONE'],
    ['label' => 'sO4', 'text' => 'Offensive, Insulting, or Abusive - Language ; Actions'],
    ['label' => 'sO5', 'text' => 'Second Caution'],
    ['label' => 'sO6', 'text' => 'DOGSO'],
    ['label' => 'sO7', 'text' => 'Threw Object or Ball with Excessive Force'],
    ['label' => 'sO8', 'text' => 'Delay of Opposing Restart - Held Ball, Kicked Ball, Impede Player'],
    ['label' => 'sO9', 'text' => 'Showed dissent / complained forcefully without regard for Technical Area'],
    ['label' => 'sO10', 'text' => 'Provoked or Inflamed a situation without regard for Technical Area'],
    ['label' => 'sO11', 'text' => 'Entered opposing technical area in an aggressive or confrontational manner'],
    ['label' => 'sO12', 'text' => 'Deliberately threw/kicked an object onto the field of play'],
    ['label' => 'sO13', 'text' => 'Entered the field to confront an official'],
    ['label' => 'sO14', 'text' => 'Entered the field to interfere with play, an opposing player, or an official'],
    ['label' => 'sO15', 'text' => 'Used or was assisted by unauthorised electronic or communication equipment'],
    ['label' => 'sO16', 'text' => 'Entered the video operation room (VOR)'],
]);

//DOGSO – Type – Send-Off 
define('SEND_OFF_DOGSO_ITEMS', [
    ['label' => 'none','text' => ''],
    ['label' => 'sOD0','text' => 'Handball'],
    ['label' => 'sOD1','text' => 'Free Kick Offense outside Penalty Area'],
    ['label' => 'sOD2','text' => 'Penalty Kick Offense without attempt to play the ball'],
    ['label' => 'sOD3','text' => 'Entered while a Sub or after Sent-Off']
]);