# Security, speed and reliability — smarty-php-template-engine-archive
Date: 2026-11-20 · Part of the code review · Start with the [reviewed outcomes](REVIEWED-OUTCOMES.md)

The template engine itself (`libs/`) looks carefully written. The small user-management demo in `demo/` is not safe to put online: anyone can read, change or delete every user, and log in without a password.

| ID | Problem (plain words) | Type | How serious | Where |
|---|---|---|---|---|
| R-01 | Attackers can type database commands into the forms and links | Security | 🔴 Fix now | all demo pages (`demo/login.php`, `edit.php`, `insert.php`, `view.php`) |
| R-02 | No login check: anyone can see, change or delete any user | Security | 🔴 Fix now | the user list, edit and delete pages (`demo/view.php`, `demo/edit.php`) |
| R-03 | Passwords are stored weakly or as plain text, and shown on screen | Security | 🔴 Fix now | `demo/insert.php`, `demo/edit.php`, `demo/templates/view.tpl` |
| R-04 | Harmful scripts can be shown on pages to other users | Security | 🟠 Fix soon | `demo/view.php`, all demo templates |
| R-05 | The database is used as the all-powerful "root" user with no password | Security | 🟠 Fix soon | the database settings (`demo/config.php`) |
| R-06 | The automatic test run is broken and there are no tests | Testing | 🟠 Fix soon | `.github/workflows/ci.yml` |
| R-07 | The debug window is switched on for every visitor | Security | 🟡 Plan to fix | the example page (`demo/index.php`) |
| R-08 | No limit on login tries, and the login session is not renewed | Security | 🟡 Plan to fix | the login page (`demo/login.php`) |
| R-09 | Database errors are not caught or written to a log | Reliability | 🟡 Plan to fix | all demo pages, `demo/config.php` |
| R-10 | The user list loads every user at once, with no pages | Speed | 🟡 Plan to fix | the user list (`demo/view.php`) |
| R-11 | Generated and cached files are stored in the project | Reliability | 🟡 Plan to fix | `demo/templates_c/`, `demo/cache/` |

### R-01 — Attackers can type database commands into the forms and links
**How serious:** 🔴 Fix now  ·  **Type:** Security  ·  **How sure:** Certain
**Where:** The login page (`demo/login.php`, line 10), the edit page (`demo/edit.php`, lines 7 and 12), the add-user page (`demo/insert.php`, line 11) and the delete link on the user list (`demo/view.php`, line 21).
**What is wrong:** Whatever a visitor types (name, age, password, id) is glued straight into the database command. This allows SQL injection (a trick where an attacker types database commands into a form).
**When it happens:** Someone types `' OR '1'='1' -- ` as the name on the login page. The check then matches every user, and they are logged in without a password. The same trick in the `id` of the edit or delete link can change or delete all users at once.
**What could happen:** Anyone can log in as anyone, read all data, change it, or wipe the whole user table.
**How to fix it:**
1. On every page, replace the glued-together commands with "prepared statements" (a safe way to send user input separately from the command). In PHP this is `mysqli_prepare` with `bind_param`, or PDO with placeholders.
2. Also check the input type: the `id` and `age` should be whole numbers (for example `(int)$_GET['id']`).
**How to check the fix worked:** Try to log in with the name `' OR '1'='1' -- ` and any password. It must fail. Try `view.php?del=1' OR '1'='1` — no user, or only one, may disappear.
**Size of the job:** small (under a day)

### R-02 — No login check: anyone can see, change or delete any user
**How serious:** 🔴 Fix now  ·  **Type:** Security  ·  **How sure:** Certain
**Where:** The user list and delete action (`demo/view.php`, lines 8–23), the edit page (`demo/edit.php`, lines 7–14) and the add-user page (`demo/insert.php`, lines 7–14).
**What is wrong:** The pages never check whether the visitor is logged in or allowed to do this. `view.php` only uses the login to print "Welcome". Deleting is done by simply opening a link (`view.php?del=<id>`), with no confirmation step.
**When it happens:** Anyone who knows the address opens `view.php` and sees every user. Opening `view.php?del=5` deletes user 5. A bad website can also hide that link in an image, so a logged-in admin deletes users just by visiting it (this is called CSRF, cross-site request forgery).
**What could happen:** Strangers can read, change or delete all user accounts.
**How to fix it:**
1. At the top of `view.php`, `edit.php` and `insert.php`, start the session and send the visitor to `login.php` if `$_SESSION['uname']` is not set. Stop the page right after (`exit`).
2. Decide who may edit or delete whom (for example only admins, or only your own record), and check it before each change.
3. Make delete a form that is sent with POST (not a plain link), with a hidden one-time token that the server checks.
**How to check the fix worked:** Log out, then open `view.php`, `edit.php?id=1` and `view.php?del=1`. Each must send you to the login page, and no data may change.
**Size of the job:** medium (a few days)

### R-03 — Passwords are stored weakly or as plain text, and shown on screen
**How serious:** 🔴 Fix now  ·  **Type:** Security  ·  **How sure:** Certain
**Where:** Adding a user (`demo/insert.php`, line 11) and logging in (`demo/login.php`, line 10) use MD5; editing a user saves the password as plain text (`demo/edit.php`, line 12). The user list shows the password column (`demo/templates/view.tpl`, lines 314 and 341), and the edit form shows it in a normal text box (`demo/templates/edit.tpl`, line 19).
**What is wrong:** MD5 (an old, very fast scrambling method) can be reversed in seconds for common passwords. After an edit, the password is not scrambled at all. Then the list page prints every password for anyone to read.
**When it happens:** Every time a user is added, edited or the user list is opened.
**What could happen:** Anyone who sees the page or a copy of the database learns users' passwords. People often reuse passwords, so their other accounts are at risk too.
**How to fix it:**
1. Save passwords with `password_hash()` (PHP's built-in safe method) in both `insert.php` and `edit.php`.
2. On login, load the user by name and check with `password_verify()`.
3. Remove the password column from both tables in `view.tpl` and the password value from `edit.tpl`. Offer "set a new password" instead, left empty by default.
4. Ask existing users to set a new password, because the old MD5 values cannot be upgraded safely.
**How to check the fix worked:** Add a user, then look in the database: the password should start with `$2y$` (or similar). The user list must show no passwords. Logging in must still work.
**Size of the job:** small (under a day)

### R-04 — Harmful scripts can be shown on pages to other users
**How serious:** 🟠 Fix soon  ·  **Type:** Security  ·  **How sure:** Certain
**Where:** The welcome line (`demo/view.php`, line 11) and every place a name or age is shown in the templates (`demo/templates/view.tpl`, lines 311–316 and 338–342; `demo/templates/edit.tpl`, lines 4–19). Automatic escaping is off by default (`libs/Smarty.class.php`, line 535, `escape_html` (the setting that makes the engine make text safe before showing it)).
**What is wrong:** Names typed by users are shown on the page exactly as typed. Text is never "escaped" (made harmless so the browser shows it instead of running it). This is called XSS (cross-site scripting).
**When it happens:** Someone adds a user with the name `<script>…</script>`. Every person who opens the user list runs that script.
**What could happen:** The script can act as the visitor, for example delete users or steal their login session.
**How to fix it:**
1. In each demo page, turn on `$smarty->escape_html = true;` right after creating Smarty.
2. In `view.php`, print the welcome text with `htmlspecialchars($user)`, or better, move it into the template.
**How to check the fix worked:** Add a user named `<b>test</b>`. The list must show the text `<b>test</b>`, not bold text.
**Size of the job:** small (under a day)

### R-05 — The database is used as the all-powerful "root" user with no password
**How serious:** 🟠 Fix soon  ·  **Type:** Security  ·  **How sure:** Certain
**Where:** The database settings (`demo/config.php`, lines 2–5). The login details are written in the code.
**What is wrong:** The app connects as "root" (the database owner who may do everything) and the password is empty. The settings are part of the code, so they are shared with everyone who sees it.
**When it happens:** Always. Combined with R-01, an attacker gets full power over the whole database server, not only this app.
**What could happen:** Loss or theft of all databases on that server.
**How to fix it:**
1. Create a separate database user that may only read and change the `user` table, with a strong password.
2. Read the login details from server settings (environment variables) or a file outside the project that is not stored in version control.
**How to check the fix worked:** Log in to the database as the new user and try to delete another table. It must be refused.
**Size of the job:** small (under a day)

### R-06 — The automatic test run is broken and there are no tests
**How serious:** 🟠 Fix soon  ·  **Type:** Testing  ·  **How sure:** Certain
**Where:** The automatic check on every change (`.github/workflows/ci.yml`, line 77) and the project root.
**What is wrong:** The automatic check runs `./phpunit.sh`, but that file is not in the project. There is also no `tests` folder and no `composer.lock` (the file that fixes exact library versions). So no test runs, and the check fails every time.
**When it happens:** On every push and every pull request.
**What could happen:** Mistakes in the template engine or demo reach users unnoticed. A check that is always red is also ignored by the team, so real failures get missed.
**How to fix it:**
1. Either add back the test suite and the `phpunit.sh` script, or change the last step to run `vendor/bin/phpunit` with a real test folder.
2. Start with tests for the most important parts: compiling templates, escaping (`modifier.escape`), caching, and the security policy (`smarty_security.php`).
3. If this project is only a read-only archive, say so and switch the check off instead, so nobody trusts it.
**How to check the fix worked:** Push a small change and see the check turn green. Break one test on purpose and see it turn red.
**Size of the job:** medium (a few days)

### R-07 — The debug window is switched on for every visitor
**How serious:** 🟡 Plan to fix  ·  **Type:** Security  ·  **How sure:** Certain
**Where:** The example page (`demo/index.php`, line 10).
**What is wrong:** `debugging = true` (a setting that opens a pop-up with all data given to the page) is on for everyone.
**When it happens:** Every time someone opens `index.php`.
**What could happen:** Visitors see internal data and file paths. If this pattern is copied into a real page, private data is shown to the public.
**How to fix it:** Remove the line, or turn it on only on a developer's own computer (for example through a setting that is off on the live server).
**How to check the fix worked:** Open `index.php` on the server. No debug pop-up should appear.
**Size of the job:** small (under a day)

### R-08 — No limit on login tries, and the login session is not renewed
**How serious:** 🟡 Plan to fix  ·  **Type:** Security  ·  **How sure:** Certain
**Where:** The login page (`demo/login.php`, lines 8–22).
**What is wrong:** Someone can try passwords as often and as fast as they want. After a good login the session ID (the hidden key in the browser that says who you are) stays the same.
**When it happens:** An attacker runs a program that tries thousands of passwords, or plants a known session ID before the user logs in (session fixation).
**What could happen:** Accounts with weak passwords get taken over.
**How to fix it:**
1. Count failed tries per name and per address, and wait or block after about 5 failures.
2. Call `session_regenerate_id(true)` right after a successful login.
**How to check the fix worked:** Enter a wrong password 6 times; the 6th try must be refused or delayed. The session cookie value must change after login.
**Size of the job:** small (under a day)

### R-09 — Database errors are not caught or written to a log
**How serious:** 🟡 Plan to fix  ·  **Type:** Reliability  ·  **How sure:** Certain
**Where:** The database connection (`demo/config.php`, line 7) and every query in `demo/edit.php`, `demo/insert.php`, `demo/login.php` and `demo/view.php`.
**What is wrong:** The code never checks if the connection or a query worked. Nothing is written to a log, and there is no safety net for unexpected errors.
**When it happens:** The database is down, or a query fails (for example because of a quote in a name).
**What could happen:** Users see broken pages or raw PHP warnings with internal details. The team has no record to find the cause, and an "update" can look successful when nothing was saved.
**How to fix it:**
1. Turn on error reporting for the database with `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)`, so failures become clear errors.
2. Add one shared error handler that writes the details to a log file and shows the user a friendly "something went wrong" page.
3. Turn off `display_errors` on the live server.
**How to check the fix worked:** Stop the database and open `view.php`. You should see a friendly message, and a log line should appear.
**Size of the job:** small (under a day)

### R-10 — The user list loads every user at once, with no pages
**How serious:** 🟡 Plan to fix  ·  **Type:** Speed  ·  **How sure:** Certain
**Where:** The user list (`demo/view.php`, lines 14–18).
**What is wrong:** All columns of all users are read into memory and shown in two tables on one page. The delete step (line 21) also runs only after the whole list is loaded, which is wasted work.
**When it happens:** Once there are thousands of users.
**What could happen:** The page becomes slow and can run out of memory.
**How to fix it:**
1. Read only the needed columns and show, for example, 50 users per page (`LIMIT` and `OFFSET`), with "next" and "previous" links.
2. Do the delete first and redirect, before loading the list.
3. Show the list once instead of twice in `view.tpl`.
**How to check the fix worked:** Fill the table with 10,000 test users. The page should still open quickly and show 50 rows.
**Size of the job:** small (under a day)

### R-11 — Generated and cached files are stored in the project
**How serious:** 🟡 Plan to fix  ·  **Type:** Reliability  ·  **How sure:** Certain
**Where:** The compiled templates (`demo/templates_c/`, 11 files) and the page cache (`demo/cache/`, 1 file). The engine library is also copied twice (`libs/` and `demo/libs/`).
**What is wrong:** These are files the engine makes by itself, and they contain PHP code that runs. Keeping them in the project means old versions can be used by mistake. Two copies of the library must be updated separately.
**When it happens:** After a template changes but the stored compiled file is newer on another server, or when a fix is applied to only one copy of the library.
**What could happen:** Visitors see outdated pages, or a security fix silently misses the demo.
**How to fix it:**
1. Remove `demo/templates_c/` and `demo/cache/` from version control and add them to `.gitignore`. Make sure the folders are created on the server and are not reachable from the web.
2. Let the demo load the single copy in `libs/` (as `demo/index.php` already does) and delete `demo/libs/`.
**How to check the fix worked:** A fresh copy of the project has no compiled files, and the demo still works after the first visit.
**Size of the job:** small (under a day)

### Small tidy-ups
- **R-12 — Password boxes show the typed text** — `demo/templates/login.tpl` line 14, `insert.tpl` lines 288 and 316: use `type="password"`.
- **R-13 — Outside scripts loaded without a safety check** — `demo/templates/view.tpl` lines 10–16 and `insert.tpl` lines 11–17: add `integrity` (a fingerprint that stops changed files) to each script and style link, or host them yourself.
- **R-14 — Example database user written in the plugins** — `demo/plugins/resource.mysql.php` line 53, `resource.mysqls.php` line 48, `cacheresource.mysql.php` line 55: read the login from settings instead.

### What is already done well
- The ready-made database cache plugin uses safe placeholders for input (`demo/plugins/cacheresource.pdo.php`, around line 252).
- The engine includes a security policy feature (`libs/sysplugins/smarty_security.php`) to limit what templates may do.
- The math feature only allows a fixed list of safe functions and blocks `$` and backticks (`libs/plugins/function.math.php`).
- The automatic check is set up to run on several PHP versions for every push and pull request (once fixed, see R-06).

### What we could not check
- We did not run the app, the tests or a database, so all findings come from reading the code.
- The math feature builds code and runs it with `eval` (`libs/plugins/function.math.php`, line 98). It is only risky if untrusted people can write templates; we could not fully confirm its filters are complete. Needs checking.
- The deep inner parts of the template engine (the compiler and parser, thousands of lines) were checked only lightly.
- We could not see how the demo would be hosted (web server settings, HTTPS, which folders are public).
