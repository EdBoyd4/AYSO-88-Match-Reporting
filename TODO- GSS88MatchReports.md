# Project TODO

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
