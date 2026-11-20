# Security, speed and reliability — smarty-php-template-engine-archive
Date: 2026-11-20 · Part of the code review · Start with the [reviewed outcomes](REVIEWED-OUTCOMES.md)

The reworked demo site is mostly well protected. It uses safe database commands, form tokens, safe password storage and automatic escaping of page content. The biggest risks are old weak passwords still sitting in the database, a login limit that people can get around, and almost no automatic tests for the demo site.

| ID | Problem (plain words) | Type | How serious | Where |
|---|---|---|---|---|
| R-01 | Old passwords stay in the database as plain text or in a weak form | Security | 🟠 Fix soon | password check and the setup guide (`demo/bootstrap.php`, `demo/README.md`) |
| R-02 | The login-attempt limit can be bypassed, used to lock people out, and does not work on more than one server | Security | 🟠 Fix soon | login-attempt limit (`demo/bootstrap.php`, `demo/login.php`) |
| R-03 | Every logged-in person can change anyone's password and delete anyone | Security | 🟡 Plan to fix | edit and delete pages (`demo/edit.php`, `demo/delete.php`) |
| R-04 | Changing a password does not log out other open sessions | Security | 🟡 Plan to fix | login check (`demo/bootstrap.php`) |
| R-05 | The demo site's key features have no automatic tests | Testing | 🟡 Plan to fix | `tests/smoke_test.php`, `.github/workflows/ci.yml` |
| R-06 | The example page shows an unescaped server value and keeps it in a shared cache | Security | 🟡 Plan to fix | example page (`demo/index.php`, `demo/templates/index.tpl`) |
| R-07 | Anyone can create the first account on a new install | Security | 🟡 Plan to fix | add-user page (`demo/insert.php`) |
| R-08 | Logins may travel without encryption when the site sits behind a proxy | Security | 🟡 Plan to fix | session setup (`demo/bootstrap.php`) |
| R-09 | Important actions are not written to a log | Reliability | 🟡 Plan to fix | login, edit and delete pages |
| R-10 | The engine fully trusts whoever writes templates | Security | 🟡 Plan to fix | the "math" feature of the engine (`libs/plugins/function.math.php`) |

### R-01 — Old passwords stay in the database as plain text or in a weak form
**How serious:** 🟠 Fix soon  ·  **Type:** Security  ·  **How sure:** Likely
**Where:** the password check (`demo/bootstrap.php`, lines 195–208) and the setup guide (`demo/README.md`, lines 42–47)
**What is wrong:** Older versions of the site saved passwords as MD5 (an old, fast method that is easy to crack) or even as plain text. The new code only upgrades an MD5 password when that person logs in again. Plain-text passwords are never removed. The guide says so itself: "users whose password was saved as plain text … cannot log in".
**When it happens:** A user who never logs in again keeps their weak or readable password in the database for ever. The same is true for every database backup.
**What could happen:** Anyone who sees the database or a backup can read or quickly crack these passwords. People often reuse passwords, so their other accounts are at risk too.
**How to fix it:**
1. Write a one-time clean-up step. For each MD5 value, store `password_hash` (the safe method) of that MD5 value. Then, in the password check, accept "safe hash of the MD5 of the typed password" and upgrade it at the next login.
2. In the same clean-up step, replace every plain-text password with an empty value that can never match. Ask those users to set a new password.
3. After the clean-up, remove the plain MD5 branch from the password check.
**How to check the fix worked:** Search the password column. No value should be 32 letters and digits long, and no value should be readable text. Old users must still be able to log in.
**Size of the job:** small (under a day)

### R-02 — The login-attempt limit can be bypassed, used to lock people out, and does not work on more than one server
**How serious:** 🟠 Fix soon  ·  **Type:** Security  ·  **How sure:** Likely
**Where:** the login-attempt limit (`demo/bootstrap.php`, lines 233–284) and the login page (`demo/login.php`, lines 16–48)
**What is wrong:** The limit reads the count of failed tries from a small file, checks the password, and only then writes the new count. Many tries sent at the same moment all read the old count before any of them writes. The count is also kept per name, so anyone can block a real person. The files live in the server's own temporary folder and are never cleaned up.
**When it happens:** An attacker sends 200 password guesses at the same moment: all 200 are checked, not 5. Someone else types a known user name wrong 5 times, and that user cannot log in for 15 minutes. If the site runs on two servers, each server keeps its own count. Every new name tried leaves one more file behind.
**What could happen:** Passwords can be guessed much faster than intended. Real users can be locked out on purpose. The temporary folder slowly fills up.
**How to fix it:**
1. Keep the counts in one shared place that can add one in a single step (for example a database table with "add one" updates, or a shared cache such as Redis).
2. Add one to the count **before** checking the password, not after.
3. Count per visitor address and name together, so a stranger cannot lock out a real user. Use a short growing delay instead of a hard lock.
4. Delete old count entries regularly.
**How to check the fix worked:** Send 50 wrong logins at the same moment with a small script. Only the allowed number should be checked. A different visitor address should still be able to log in as that user.
**Size of the job:** medium (a few days)

### R-03 — Every logged-in person can change anyone's password and delete anyone
**How serious:** 🟡 Plan to fix  ·  **Type:** Security  ·  **How sure:** Certain
**Where:** the edit page (`demo/edit.php`, lines 3 and 16–44) and the delete page (`demo/delete.php`, lines 7–30)
**What is wrong:** There are no roles (such as "admin" and "normal user"). Any logged-in person can set a new password for any other user and delete other users. Changing your own password also does not ask for your current password.
**When it happens:** One user sets a new password for a colleague, then logs in as that colleague. Or someone uses a computer that was left logged in to change the owner's password.
**What could happen:** One weak or stolen account gives full control over all accounts. Actions may look as if another person did them.
**How to fix it:**
1. Decide if this is wanted. If all users are meant to be admins, write this down in `demo/README.md`.
2. If not, add a role column (for example "is admin"). Allow editing and deleting other users only for admins.
3. When people change their own password, ask for the current password first.
**How to check the fix worked:** Log in as a normal user. Try to open `edit.php?id=` with another user's number and try to delete them. Both must be refused.
**Size of the job:** medium (a few days)

### R-04 — Changing a password does not log out other open sessions
**How serious:** 🟡 Plan to fix  ·  **Type:** Security  ·  **How sure:** Certain
**Where:** the login check (`demo_require_login`, the step that checks if a visitor is logged in, `demo/bootstrap.php`, lines 132–143)
**What is wrong:** A session (the "logged-in" state kept by the server) only remembers the user number. The check only asks "does this user still exist?". It does not notice that the password was changed.
**When it happens:** Someone has stolen a user's session. The user or an admin changes the password, but the thief stays logged in.
**What could happen:** Changing a password does not stop an attacker who is already in.
**How to fix it:**
1. Save a short "password version" (for example a fingerprint of the stored password, or a counter) in the session at login.
2. In the login check, compare it with the database on each page. If it differs, log the visitor out.
**How to check the fix worked:** Log in from two browsers. Change the password in one. The other browser must go to the login page on its next click.
**Size of the job:** small (under a day)

### R-05 — The demo site's key features have no automatic tests
**How serious:** 🟡 Plan to fix  ·  **Type:** Testing  ·  **How sure:** Certain
**Where:** the only test file (`tests/smoke_test.php`, 6 checks) and the automatic check on every change (`.github/workflows/ci.yml`, lines 56–63)
**What is wrong:** The automatic check runs on every change, which is good. But it only checks for typing errors in the code and runs 6 small engine checks. Nothing tests login, the form tokens (CSRF protection — a check that a form really came from this site), the login limit, paging or the "cannot delete yourself" rule. The original large Smarty test suite is not in this archive, even though `composer.json` still lists the test tool PHPUnit.
**When it happens:** Someone changes the login code and breaks the password check or the form token check. No test notices.
**What could happen:** Security protections can silently stop working and go live unnoticed.
**How to fix it:**
1. Add tests for the shared start-up code: password check (new and old MD5), user validation, form token check and login limit.
2. Add a few page tests that use a small test database (for example SQLite in memory): log in, add, edit, delete, and the "delete yourself" refusal.
3. Add more engine tests for escaping and the template features the demo uses.
4. Run all of them in the existing automatic check.
**How to check the fix worked:** Break the form token check on purpose in a test branch. The automatic check must fail.
**Size of the job:** medium (a few days)

### R-06 — The example page shows an unescaped server value and keeps it in a shared cache
**How serious:** 🟡 Plan to fix  ·  **Type:** Security  ·  **How sure:** Needs checking
**Where:** the example page (`demo/index.php`, lines 7–14) and its template (`demo/templates/index.tpl`, lines 14 and 16)
**What is wrong:** This page does not use the shared start-up code, so automatic escaping is off. It prints the server name, which some web servers take from the address the visitor sent. The page is also cached (saved and reused for everyone) for 2 minutes.
**When it happens:** If the web server copies the visitor's "Host" value into the server name, an attacker sends a request with a harmful script in it. That version is cached and shown to every visitor for 2 minutes.
**What could happen:** XSS (cross-site scripting — a harmful script runs in other visitors' browsers).
**How to fix it:**
1. In `demo/index.php`, switch on automatic escaping (`escape_html = true`), or use the shared start-up code.
2. Do not print `$smarty.server` values on the page, or escape them with `|escape`.
**How to check the fix worked:** Request the page with a made-up Host value such as `<b>x</b>`. The page must show it as plain text, not as bold text.
**Size of the job:** small (under a day)

### R-07 — Anyone can create the first account on a new install
**How serious:** 🟡 Plan to fix  ·  **Type:** Security  ·  **How sure:** Certain
**Where:** the add-user page (`demo/insert.php`, lines 6–9)
**What is wrong:** While the user table is empty, the add-user page is open to everyone, without login. This is meant to make setup easy.
**When it happens:** The site is put online before the owner creates the first account. A stranger who finds `insert.php` first creates their own account.
**What could happen:** The stranger gets full control of the user list. Two people sending the form at the same time could also both get in.
**How to fix it:**
1. Require a one-time setup code from a server setting (environment variable) to create the first user, or create the first user with a command-line script.
2. Turn the setup path off once a user exists, inside the same database step as the insert.
**How to check the fix worked:** On an empty database, open `insert.php` without the setup code. It must be refused.
**Size of the job:** small (under a day)

### R-08 — Logins may travel without encryption when the site sits behind a proxy
**How serious:** 🟡 Plan to fix  ·  **Type:** Security  ·  **How sure:** Needs checking
**Where:** session setup (`demo/bootstrap.php`, lines 50–57)
**What is wrong:** The "secure" cookie flag (which keeps the login cookie on encrypted connections only) is set only when PHP itself sees HTTPS. The site never sends visitors from plain HTTP to HTTPS.
**When it happens:** The site runs behind a load balancer or proxy that handles HTTPS. PHP sees plain HTTP, so the cookie is not marked secure. Or a visitor opens the plain `http://` address.
**What could happen:** On public Wi-Fi, someone could read the password or the login cookie and take over the account.
**How to fix it:**
1. Always send visitors to HTTPS (in the web server settings) and add the "Strict-Transport-Security" header (tells browsers to use HTTPS only).
2. Set the secure cookie flag always in production, or trust the proxy's "this was HTTPS" header from known proxies only.
**How to check the fix worked:** Open the plain `http://` address. You should be sent to `https://`, and the browser should show the session cookie as "Secure".
**Size of the job:** small (under a day)

### R-09 — Important actions are not written to a log
**How serious:** 🟡 Plan to fix  ·  **Type:** Reliability  ·  **How sure:** Certain
**Where:** the login page (`demo/login.php`, lines 16–49), the edit page (`demo/edit.php`, lines 28–44) and the delete page (`demo/delete.php`, lines 29–30)
**What is wrong:** Unexpected errors are logged well. But failed logins, lock-outs, password changes and deletions are not logged with who did what and when.
**When it happens:** Users are deleted, or an account is taken over. Nobody can find out who did it or when.
**What could happen:** Attacks and mistakes cannot be noticed or looked into.
**How to fix it:**
1. Add one small log function and call it for: failed login, lock-out, successful login, password change, user edit and delete.
2. Write the acting user number, the target user number, the time and the visitor address. Never write passwords.
**How to check the fix worked:** Delete a test user and enter a wrong password. Both events must appear in the log with the right user numbers.
**Size of the job:** small (under a day)

### R-10 — The engine fully trusts whoever writes templates
**How serious:** 🟡 Plan to fix  ·  **Type:** Security  ·  **How sure:** Needs checking
**Where:** the "math" feature of the engine (`libs/plugins/function.math.php`, lines 53–98); the demo never switches on Smarty's safety mode (`Smarty_Security`, the setting that limits what templates may do)
**What is wrong:** The math feature runs the formula as PHP code (`eval`). It only blocks a few characters, and a carefully written formula may slip through. Templates can also use other powerful features. This is safe only while the developers alone write the templates.
**When it happens:** Someone reuses this engine where templates come from users or a database. The included example plugin that loads templates from a database (`demo/plugins/resource.mysql.php`) is one such case.
**What could happen:** A person who can edit a template could run their own commands on the server.
**How to fix it:**
1. Never let untrusted people write templates for this engine copy. Write this in `README.md`.
2. If templates can come from a database or from users, switch on the safety mode (`enableSecurity()`) and turn off `math`, `fetch` and PHP functions in templates.
3. If the engine is used for real work, move to a maintained Smarty release (see [about the project](4-about-project.md)).
**How to check the fix worked:** With the safety mode on, a template that uses `{math}` or `{fetch}` must be refused.
**Size of the job:** small (under a day)

### Small tidy-ups
- **R-11 — No Content-Security-Policy header** — shared start-up code (`demo/bootstrap.php`, lines 41–45): add a header that only allows scripts from this site and the listed CDNs.
- **R-12 — Visitor addresses are sent to Google Fonts** — page templates (`demo/templates/view.tpl`, line 7 and 9; `demo/templates/insert.tpl`, line 7): host the fonts on the site itself.
- **R-13 — Generated files are only protected on Apache** — folders for generated pages (`demo/templates_c/`, `demo/cache/`): move them outside the public web folder, so other web servers (such as nginx) cannot serve them.
- **R-14 — The automatic check has broad rights and uses outside tools by name only** — `.github/workflows/ci.yml`, lines 41–49: add `permissions: contents: read` and pin outside actions to an exact version.

### What is already done well
- All database commands use placeholders, so user input cannot change them (no SQL injection).
- Passwords are stored with PHP's safe `password_hash`, and login uses a fresh session number.
- Every form that changes data checks a form token, and delete only works with a POST form.
- Automatic escaping is on for the user pages, so typed names cannot run as scripts.
- Database logins come from server settings, not from the code, and errors show only a friendly message to visitors.

### Speed
We found no real speed problems. The user list is shown in pages of 50, and each page makes only a few small database requests. The only scaling limit (counts kept in local files) is part of R-02.

### What we could not check
- How the real web server is set up (Host header handling, HTTPS, proxy). This decides R-06 and R-08.
- The real database: how many old MD5 or plain-text passwords exist (R-01), and whether user names have a unique rule.
- We did not run the tests, the site or the automatic check (rule 3).
- The large engine code in `libs/` was only checked in a few spots (the math feature, debug window setting, how compiled pages are loaded). It was not checked line by line.
