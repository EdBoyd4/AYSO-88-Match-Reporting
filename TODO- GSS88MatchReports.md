# Project TODO

## ✅ Completed
- [X] Match-data models reorganized -- 2026-09-24. components-model-report-
      entry-AYSO-88/model-insert.php and model-query.php renamed to
      model-match-data-insert.php and model-match-data-query.php.
      matchReporting/model/MatchDataQueries.php (all match-data reads) moved
      into model-match-data-query.php -- one class, unchanged, just relocated.
      model-insert.php's procedural mysqli functions (global $dBConnection,
      bind_param type strings) converted to a new PDO/OO class,
      MatchDataInserter, in model-match-data-insert.php -- same tables, same
      columns, same "omit a null field" behavior, same transaction/rollback
      shape. model-query.php's one remaining function, getMatchDetailsForEmail2()
      (fed the notification email only), ported to
      MatchDataQueries::matchDetailsForEmail() -- identical SQL, identical
      flat/unpivoted return shape, so controller-match-report-email.php's
      consumption of it is unchanged (just calls it via `global $matchDataQueries`
      now instead of a free function). GSS88_MATCH_REPORTING_MODEL constant
      removed (nothing points at matchReporting/ anymore); GSS88_MODELS_REPORTS
      now hosts all four match-data + RAPP-report model files. Live-verified:
      match-report.php loads and its full insertMatchData() path (match
      report + officiants + gamecards + 2 sanctions, transactional) round-
      tripped correctly against the real schema, including a deliberate
      failure case confirming the transaction rolls back completely rather
      than leaving partial rows.

## 🔧 In Progress
- [ ] refactor display-match-details.js and controller-match-details.js
- [ ] fieldAndAgeMatchCheckAndSet() in controller-match-report-sanitize-and-enter.php - not called yet, kept in place for future use
- [ ] choose / crop pictures for background 
- [ ] 

## 📌 Next Tasks
- [ ] update git and github
- [ ] add login library compatability
- [ ] table report for previous week - with gamecard photos
- [ ] ref logins
- [ ] The "Please check Here..." and the "Submit the Match Results" needs to be separated.
- [ ] update js methods that populate the four <select> elements in "Match Details"
- [ ] convert repeating subsections to use <template> 
- [ ] update nmber column 
- [ ] update division_number column
- [ ] refactor sanction description inputs 
- [ ] refactor the css file to make it more succinct
- [ ] move SMTP credentials out of controller-match-report-email.php (currently hard-coded; smtp_config.php is already stubbed for this) so the RAPP notifier and any future mailer read from one shared config instead of duplicating creds
- [ ] 

## 🧭 Future Enhancements
- [ ] 

## 🧪 Testing Checklist
- [ ] new SELECT query for match selection
- [ ] flip GSS88_EMAIL_DEV_MODE (in public/login.php, formerly public/88-match-report-redesign.php) to false before going live - it currently suppresses every notification-email recipient except edwin.b@ayso88.org
- [ ] www-data can't write to AYSORegion88GameCards (mkdir/move_uploaded_file both fail with Permission denied) - game card photos aren't actually landing on disk yet even though the DB row + filename get saved
- [ ] 

## 🗂 Notes
- [ ] 
