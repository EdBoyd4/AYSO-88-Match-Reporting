# RAPP — Referee Abuse Prevention Program reporting

Design/implementation plan. Standalone authenticated area in gss88 that lets a
referee/AR file an abuse-incident report (text and/or short voice file), and lets
authorized users review reports and related match data under role-based access.

Status: **design — pending referee-admin (RRA) sign-off.** Not started.

---

## 1. Page map & navigation

New standalone area (e.g. `/rapp/`). A small landing page with navigation buttons:

| Button | Audience | Goes to |
|---|---|---|
| **File a Referee Abuse Report** | Referees / ARs | OTP login (if not signed in) → submission page |
| **RAPP Reports** | RRA, Senior Board, Board Members, DCs | OTP login (if not signed in) → dashboard, contents by capability |

- Both buttons funnel through the **same OTP login**; post-login routing differs by
  intent + the user's capabilities.
- Shared sign-in / sign-out state indicator on every page.
- The public match-report form stays anonymous and untouched; it can carry a link
  to "File a Referee Abuse Report" so refs discover it.

---

## 2. Roles & capabilities

Roles are **additive** — real people wear more than one hat (a DC who is also a
board member). Model as `user_roles` (join table), not a single `role` column.

Code checks **capabilities**, never role-name strings. One `role → capabilities`
map in a config class; a constructor-injected `AccessPolicy` exposes
`can(User $u, string $capability, ?int $divisionId = null): bool`.

Capabilities:

- `rapp.submit` — file a report
- `rapp.view` — read report text + audio
- `rapp.notify` — receive the incident notice
- `rapp.retain` — flag a report to survive the 72h purge / close it
- `scores.view`
- `gamecard.view`
- `matchdata.view` — staffing issues / match issues / sanctions
- `users.manage`
- scope resolver: `all` vs `own_division`

Role → capability map:

| Role | Capabilities |
|---|---|
| `ref` | `rapp.submit` |
| `dc` | `scores.view`, `gamecard.view`, `matchdata.view` — **own division**; division-scoped non-RAPP notices (unchanged from today's email fan-out) |
| `authorized_board` | `scores.view`, `gamecard.view` — **all divisions** (playoff scheduling) |
| `senior_board` | `rapp.view`, `rapp.notify`, `rapp.retain`, `scores.view`, `gamecard.view`, `matchdata.view` — all divisions |
| `rra` | everything + `users.manage` + alternate management |

Confirmed: **DCs get zero RAPP visibility** (no `rapp.*`).

---

## 3. Data model

New tables (`_id` PK convention, matching existing schema):

**`users`** — `_id`, `email` (unique), `full_name`, `last_name`, `active` (tinyint), `created_at`

**`user_roles`** — `_id`, `user_id` (FK), `role` (enum: `ref`,`dc`,`authorized_board`,`senior_board`,`rra`), `division_id` (nullable FK → `divisions_with_coordinators`, used for `dc`)

**`otp_codes`** — `_id`, `email`, `code_hash`, `expires_at`, `attempts`, `consumed_at` (nullable), `created_at`

**`auth_sessions`** — `_id`, `user_id` (FK), `token_hash`, `created_at`, `last_seen_at`, `expires_at` (server-side sessions, hashed tokens, for the audit trail)

**`rapp_reports`** — `_id`, `scheduled_match_id` (FK → `scheduled_matches`), `submitted_by_user_id` (FK), `body_text` (nullable — purged at 72h), `has_audio` (tinyint), `status` (enum: `new`,`acknowledged`,`retained`,`closed`), `retain` (tinyint — set by `rapp.retain`), `created_at`, `content_purged_at` (nullable)

**`rapp_media`** — `_id`, `rapp_report_id` (FK), `filename`, `mime`, `bytes`, `created_at`, `purged_at` (nullable). File lives on disk in a non-public dir.

**`rapp_alternates`** — `_id`, `alternate_user_id` (FK), `activated_by_user_id` (FK), `starts_at`, `ends_at` (nullable), `active` (tinyint)

**`rapp_media_access_log`** — `_id`, `user_id`, `rapp_report_id`, `accessed_at` (audit trail for who listened to what)

Schema changes are applied **directly on the A2 production DB** — `.env`-based DB
access is a local dev copy (see project memory).

---

## 4. OTP login flow

1. User enters email.
2. Server looks up an active user. Response is always "if that address is
   registered, a code is on its way" — never reveal whether the email exists.
3. Generate a 6-digit code; store `code_hash` (hashed), `expires_at` = now + 10 min,
   `attempts` = 0.
4. Email the code via PHPMailer, using the **shared mailer config** (see TODO:
   move SMTP creds out of `controller-match-report-email.php`).
5. User enters the code → verify hash, not expired, `attempts` < 5, not consumed.
   On success: mark consumed, create `auth_sessions` row, set secure/httponly/
   samesite cookie.
6. Session lifetime — **RRA to decide.** Suggest short (a few hours) for
   report-viewer sessions given the sensitivity; a ref filing a report only needs
   one session anyway.
7. Rate-limit code requests per email + per IP.

Open: build this on `boyds-little-login-library-for-php` or as a small
purpose-built module. OTP + a capability map is simple enough to stand alone; the
library may be heavier than needed here.

---

## 5. Submission page (`rapp.submit`)

Standalone page, built from the existing collapsible-module component style
(`RefStaffingIssueGPT2`, `MatchNotesFieldset`). A checkbox/button reveals the
segment; submission requires an active OTP session (else route through login and
back).

Fields:

- **Match picker** — Option A: recent scheduled matches, same source as
  `selectScheduledMatchOptionsFromDatabaseEXP()`. Always linked to a scheduled match.
- **Textarea** — written account.
- **Audio** — `<input type="file" accept="audio/*" capture>` (native recorder on
  phones) and/or in-browser record. Text and audio each optional; **at least one
  required.**
- **Standing guidance text:**
  - Record **only yourself**, speaking **privately** — do not record the incident,
    other people, or a conversation.
  - Refer to individuals the way the misconduct reports do — team, player/coach,
    jersey number, description — rather than by full name where possible
    (mirrors the "Description of the Player / Coach" convention in sanction entry).

Validation: match chosen; ≥1 of text/audio; audio MIME + extension + size
(fork the photo-upload validator in `controller-match-report-photo-upload.php`,
drop the image-specific integrity checks; allow `audio/mpeg`, `audio/mp4`,
`audio/webm`, `audio/ogg` + matching extensions).

Save: `rapp_reports` row; move audio into a non-public dir
(`RAPPReports/<year>/<report_id>/`), `rapp_media` row; fire the notice.
Confirmation screen echoes **no** submitted content back.

---

## 6. Report views

- **RAPP list + detail** (`rapp.view`) — table (date, division, match, submitting
  ref, status); detail shows `body_text` + an `<audio>` element whose `src` is an
  **authed streaming endpoint** (`/rapp/media?id=…`, checks `rapp.view`, streams the
  file, logs the access). Never a direct file URL, including in emails. `retain` /
  `close` actions for `rapp.retain`.
- **Division match data** (`matchdata.view`, `own_division` for DCs) — staffing
  issues, match issues, sanctions, scores, gamecards for the DC's division only.
  Reuses existing query logic, filtered by `division`.
- **Scores + gamecards** (`scores.view` + `gamecard.view`) — read-only, all
  divisions, for `authorized_board`. Score table + gamecard viewer (images via the
  same authed media endpoint pattern).
- **User management** (`users.manage`) — list / add / deactivate users; assign
  role(s) + division; designate the **active alternate** with a date range.

---

## 7. Notifications

- On submit: email everyone with `rapp.notify` — RRA, Senior Board, and the
  **active alternate** if one is set — immediately (or on the existing cron
  cadence). **One email, sent once, no reminders.** Body = full incident detail +
  a link to the authed detail page. Audio is **not** attached.
- Alternate is activated **only when the RRA is away** — a dated entry in
  `rapp_alternates`, set from the user-management screen. When none is active, only
  RRA + Senior Board are notified.
- PHPMailer, shared SMTP config.
- Existing non-RAPP match-report fan-out to DCs is unchanged.

---

## 8. Retention cron

Daily (or hourly). For `rapp_reports` where
`created_at < now - 72h AND retain = 0 AND content_purged_at IS NULL`:

- Delete the audio file(s); set `rapp_media.purged_at`.
- Null `rapp_reports.body_text`; set `content_purged_at`; set `status = closed`.
- **Keep the metadata row** (date, division, match, submitting ref, status) — it
  feeds the season's archived data.

`retain = 1` (set by RRA or the designated alternate) skips the purge; content
persists until someone closes it.

---

## 9. Storage & security

- Audio dir **outside the web root**, `0755`, writable by `www-data`.
- All media (RAPP audio + gamecard images) served through PHP with a capability
  check; no direct file URLs anywhere.
- OTP codes hashed at rest; sessions server-side with hashed tokens.
- HTTPS only (already on gss88.org); secure + httponly + samesite cookies.
- CSRF token on every POST (submission, user management, retain/close).
- Log every media access (`rapp_media_access_log`).

---

## 10. Dependencies / blockers

1. **`www-data` filesystem write permission** — same blocker as the game-card
   photo bug (open in `TODO- GSS88MatchReports.md`). Must be fixed for RAPP audio
   to land on disk.
2. **Auth foundation** — relates to open TODOs "add login library compatibility"
   and "ref logins". Decide: build on the login library or a purpose-built OTP
   module.
3. **Schema applied directly on the A2 production DB** (not via `.env` dev copy).
4. **SMTP creds moved to shared config** (`smtp_config.php` is already stubbed).
5. **User roster seed** — the RRA's list of refs/ARs, board members, and DCs with
   email addresses.

---

## 11. Rough sequencing

1. Schema + user-roster seed.
2. OTP login + sessions + capability layer (testable behind a stub page).
3. Standalone nav page — the two buttons + sign-in/out.
4. RAPP submission page (needs the `www-data` write fix in parallel).
5. Notification on submit.
6. RAPP report list + detail + authed media endpoint.
7. Retention cron.
8. Division data view + scores/gamecard view + user-management screen.
9. SMTP config refactor (shared with the mailer TODO).

---

## Open questions for the RRA

- Session lifetime for report-viewer sign-ins (suggest a few hours).
- Auth foundation: login library vs purpose-built OTP module.
- Confirm the audio format allow-list is acceptable (phones typically produce
  `.m4a` / `mp4` or `.webm`).
