# Project TODO — RAPP (Referee Abuse Prevention Program)

## 🔧 In Progress
- [ ]

## 📌 Next Tasks
- [ ] No DB-level protection on rapp_reports.scheduled_match_id (2026-09-23).
      rapp_reports lives in its own database (xnbglkce_gss88_rapp_reports),
      separate from match_reports (xnbglkce_gss88_match_reports) -- a
      deliberate split for reputation/child-data segregation. The join in
      RappReportRepository.php is cross-database (`{matchReportsDb}.scheduled_matches
      sm ON sm._id = r.scheduled_match_id`), so MySQL cannot enforce this as a
      real foreign key. Nothing today stops a scheduled_matches row from being
      edited or deleted out from under an active RAPP report, and nothing
      flags a match as "has an active RAPP report" to anyone editing it.
      Two things worth doing, likely together:
        1. Snapshot the identifying match fields (date, division, opponent/
           teams, assigned officials) onto rapp_reports at createReport() time,
           sourced from MatchDataQueries -- so the report's context survives a
           later correction to the match record, for evidentiary integrity.
        2. Some association/guard so editing or deleting a scheduled_match
           that has an active RAPP report is at least flagged, ideally blocked
           -- application-level, since the DB can't enforce it here.
      Needs an RRA/policy decision on how strict (2): warn-only vs. hard block,
      and whether a corrected match record should ever retroactively change an
      already-filed report's stored context.
- [X] Reduce navigation friction filing a RAPP report from match-report context
      -- built 2026-09-23. report.php now accepts ?match=<scheduled_match_id>:
      when present and within the reporting window, the "which match?" dropdown
      is skipped and MatchDataQueries::matchRow() is shown read-only instead --
      Match Details (date/time/division/field/teams/officials) then Additional
      Notes (mr.match_issue, carried over verbatim from the match report, if
      any was recorded), with the RAPP narrative textarea below both, per
      policy: the report captures both the basic match data and the RAPP
      info from one page, in the same presentation fashion as the match-report
      form's own fieldsets -- without literally reusing those fieldset view
      classes, since they're wired for anonymous match-report.php's own JS/
      picker/POST target, not a read-only display on an authenticated page.
      No dropdown shown when ?match= is absent/stale/outside the window --
      falls back to the original picker, unchanged. Nothing yet generates a
      report.php?match= link from anywhere (match-report.php itself, being
      anonymous, can't know which authenticated referee it's about to hand off
      to) -- the mechanism exists, the entry point into it doesn't yet.
      Live-verified end to end (OTP sign-in, pre-filled read-only display,
      submission, DB row, and dropdown fallback with no ?match=).
- [X] Filing window enforced -- built 2026-09-23, alongside the above.
      Revised 2026-09-27: the original 48h policy note was a miscommunication
      -- 48h is when a referee *should* file, not a deadline to enforce
      programmatically. Referees are volunteers, and a Saturday match reported
      the following Wednesday isn't unreasonable, so the actual window is 5
      days (120h). GSS88_RAPP_REPORT_WINDOW_HOURS (default 120) drives both
      the picker (RappReportQueries::recentScheduledMatches(), hour-precision
      via TIMESTAMP(match_date, match_time), not calendar-day) and a
      server-side check at submission (isWithinReportWindow()) regardless of
      how the match was chosen -- closes a gap the old code had, where
      scheduledMatchExists() would silently accept ANY match id, however old,
      as a fallback past the picker's own day window. UI copy (report.php,
      the inline section on match-report.php) encourages prompt filing rather
      than presenting 5 days as a strict deadline. To change the window later:
      one .env value, no code change.
- [X] RappReportRepository split into RappReportQueries + RappReportInserter
      -- 2026-09-24. Moved out of rapp/src/ into components-model-report-
      entry-AYSO-88/ (model-rapp-report-query.php / model-rapp-report-insert.php),
      matching the match-data models' naming. Read methods (findReport,
      listReports, mediaForReport, findMedia, reportsDueForPurge,
      activeAlternateUserIds, listActiveAlternates, notificationRecipients,
      recentScheduledMatches, isWithinReportWindow) went to RappReportQueries,
      which keeps the three cross-database names (match_reports/user_access/
      otp_users). Write methods (createReport, addMedia, setStatus, setRetain,
      logMediaAccess, markContentPurged, addAlternate, deactivateAlternate)
      went to RappReportInserter, which turned out to need only a PDO
      connection -- none of them ever touched another database, a
      simplification the split exposed rather than just a rename. Dropped
      scheduledMatchExists(): dead, no callers left once isWithinReportWindow()
      replaced its one use in report.php. rapp-bootstrap.php now exposes
      $rappReportQueries and $rappReportInserter (was $rappReports); every
      caller (report.php, media.php, reports.php, users.php,
      rapp-retention.php) updated to call the right one. Live-verified every
      method that changed -- report.php's full request/verify/submit round
      trip, plus findReport/setStatus/setRetain/logMediaAccess/addAlternate/
      listActiveAlternates/deactivateAlternate/notificationRecipients
      called directly against the real database.
- [ ] match-report.php's inline RAPP flow only blocks submission client-side
      while a code is outstanding (2026-10-01). controller-rapp-incident-
      section.js tracks "code requested but not verified" purely in JS and
      disables the submit button / hard-blocks the submit event
      (explainUnverifiedCode()) -- but nothing mirrors that state server-side.
      The session only ever records the *verified* result
      ($_SESSION['rapp_inline_verified_user_id']); a request that's issued but
      never verified leaves no trace there. So a referee with JS disabled, a
      browser extension interfering, or a raw/crafted POST bypasses the guard
      entirely, and match-report.php silently drops the RAPP portion exactly
      like before this feature existed -- the match report itself still
      saves fine either way (that part's already enforced server-side, see
      getHeaderDataFromPOST()).
      Two ways to close this, pick one:
        1. Mirror the state server-side: match-report-rapp-otp.php sets
           $_SESSION['rapp_inline_pending_email'] on a code request, clears
           it on verify, and match-report.php's POST handler rejects the
           submission if that flag is set without a matching verified id.
           Needs a matching "cancel" round trip too (collapsing the section
           client-side doesn't currently touch the server at all) -- without
           one, a referee who requests a code then changes their mind gets
           permanently stuck unable to submit even their mandatory match
           report, without reloading the page from scratch.
        2. Drop the email-OTP identity step for this flow entirely and
           authenticate inline RAPP submissions by password instead (e.g.
           require signing in via the existing user_access/AuthManager path
           before the RAPP section unlocks) -- removes the async
           request-then-verify gap this bug class lives in altogether, at
           the cost of no longer supporting self-service/unregistered
           referees filing inline.
      No decision made yet on which.
- [ ]

## 🧭 Future Enhancements
- [ ]

## 🧪 Testing Checklist
- [ ]

## 🗂 Notes
- [ ]
