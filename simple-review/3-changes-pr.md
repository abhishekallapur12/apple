# Latest changes (PR review) — smarty-php-template-engine-archive
Date: 2026-11-20 · Part of the code review · Start with the [reviewed outcomes](REVIEWED-OUTCOMES.md)

**What we reviewed:** Mode B — the recent changes on `main`. The branch has nothing that `origin/main` lacks, so there was no branch to compare with. The project has only 3 saved changes (commits). The last 2 only edit the `README.md` text (+10 / −1 lines). Almost all the real work is in the **unsaved changes**: 237 files, +828 / −33,648 lines (spacing-only changes not counted). Most of the removed lines are a second copy of the Smarty engine in `demo/libs` and some generated files.

### In short
These changes turn the old, unsafe demo website (a small site where people log in and manage a list of users) into a much safer one. Database commands are now built safely. Passwords are stored with a strong one-way scramble (hashing) instead of MD5 (an old, weak method). Forms now carry an anti-forgery code (a CSRF token, which stops other websites from sending forms in a user's name). Deleting now needs a button press, the page escapes text automatically, and repeated wrong logins are limited. The automatic checks (CI, the build that runs on every upload) now run a working smoke test (a quick basic test). The code itself looks good. But the changes are only half "staged" (marked to be saved), and some database steps must be done before going live.

**Verdict:** ⚠️ Accept after fixing C-01 and C-02

### What changed
**1. Safe database access everywhere in the demo** — All pages now use one shared helper that sends user input separately from the database command, so SQL injection (an attacker typing database commands into a form) is closed. Database logins come from server settings instead of the code. *Where:* shared start-up file (`demo/bootstrap.php`, functions `demo_db`, `demo_query`); settings (`demo/config.php`). *Risk:* low.

**2. Login, passwords and sessions rebuilt** — Passwords use `password_hash` (PHP's built-in safe hashing). Old MD5 passwords still work once and are upgraded at the next login. The login session gets a new ID at login (stops "session fixation", where an attacker plants a known session ID). A logout button was added. *Where:* `demo/login.php`, `demo/logout.php`, `demo/bootstrap.php` (`demo_check_password`, `demo_log_in`). *Risk:* medium (C-02).

**3. Limit on wrong login attempts** — After 5 wrong tries for a name, or 20 from one internet address, login is blocked for 15 minutes. *Where:* `demo/bootstrap.php`, lines 233–284. *Risk:* low–medium (C-03).

**4. Deleting is now safe** — Deleting used to happen when someone opened a link (`view.php?del=…`). It now needs a form with an anti-forgery code. You cannot delete yourself, and several users can be deleted at once. *Where:* new page `demo/delete.php`; user list (`demo/templates/view.tpl`). *Risk:* low.

**5. Add, edit and list pages tidied up** — Input is checked (name, age 0–150, password at least 8 characters) and duplicate names are refused. An empty password box on the edit page keeps the old password. The list no longer shows passwords and is split into pages of 50. *Where:* `demo/insert.php`, `demo/edit.php`, `demo/view.php` and their templates. *Risk:* low (C-04).

**6. Automatic escaping and safer page settings** — Smarty's `escape_html` (makes every shown value harmless, stopping XSS, where an attacker hides scripts in text) is switched on. Browser safety headers were added. The debug window only appears when `DEMO_DEBUG=1`. *Where:* `demo/bootstrap.php` (`demo_smarty`), `demo/index.php`, line 11. *Risk:* low.

**7. Demo now uses the main engine; generated files removed** — The duplicate engine copy in `demo/libs` (about 200 files) and the generated template files are deleted. `.gitignore` (the list of files git should ignore) and web-blocking `.htaccess` files were added. *Where:* `demo/libs/`, `demo/templates_c/`, `demo/cache/`, `.gitignore`. *Risk:* medium (C-01).

**8. Automatic checks replaced, example plugins changed** — The CI no longer calls the missing `phpunit.sh` file. It now checks every PHP file for syntax errors and runs a new smoke test. The MySQL example plugins now read their login from server settings, and the cache plugin records an expiry time. *Where:* `.github/workflows/ci.yml`, `tests/smoke_test.php`, `demo/plugins/`. *Risk:* medium (C-02).

### Files changed
| File | + / − | What changed, in plain words |
|---|---|---|
| `demo/bootstrap.php` (new) | +304 / −0 | Shared start-up: errors, session, database, login, form codes, passwords, login limit, Smarty |
| `demo/templates/view.tpl` | +73 / −158 | User list: delete buttons with form codes, paging, logout, no passwords shown |
| `demo/templates/insert.tpl` | +24 / −290 | Add-user form simplified, error messages, form code |
| `demo/login.php` | +48 / −16 | Safe login with hashing, MD5 upgrade, attempt limit |
| `demo/edit.php` | +45 / −11 | Safe edit with checks; empty password keeps the old one |
| `demo/insert.php` | +37 / −11 | Safe add with checks; first user may be created without login |
| `demo/delete.php` (new) | +33 / −0 | Delete one or many users, POST + form code only |
| `demo/plugins/` (3 files) | +50 / −8 | Database login from server settings; cache expiry column |
| `demo/config.php` | +24 / −8 | Settings read from server settings, no password in the code |
| `demo/view.php` | +17 / −22 | List with paging; deleting moved out |
| `demo/templates/edit.tpl`, `login.tpl`, `header.tpl`, `index.tpl` | +36 / −21 | Form codes, labels, password boxes, valid HTML |
| `.github/workflows/ci.yml` | +5 / −15 | Syntax check + smoke test instead of the missing phpunit step |
| `tests/smoke_test.php` (new) | +49 / −0 | Six basic checks of the engine, including escaping |
| `demo/README.md` (new), `demo/logout.php` (new), `.gitignore`, 2 × `.htaccess` | +81 / −0 | Setup guide, logout, ignore and block generated files |
| `demo/libs/` (about 200 files) + 13 generated files | 0 / −33,000+ | Duplicate engine copy and generated files deleted |
| `README.md` (last 2 saved changes) | +10 / −1 | Labels the project as a historical archive |

### Problems found in these changes

### C-01 — Only the deletions are staged; saving now would break the demo
**How serious:** 🟠 Fix soon  ·  **Type:** Process / Release  ·  **How sure:** Certain
**Where:** The whole set of unsaved changes (git's "staging area", the list of changes picked for the next save).
**What is wrong:** Only the 213 deleted files (the `demo/libs` copy and the generated files) are staged. The 20 changed files are not staged. The 9 new files (such as `demo/bootstrap.php`, `demo/delete.php`, `tests/smoke_test.php`) are only marked "will be added later" (git's "intent to add"), so their content is not staged either.
**When it happens:** If someone runs a plain `git commit` now, only the deletions are saved.
**What could happen:** In that saved version, the old demo pages still load `libs/Smarty.class.php` from the deleted folder, so every demo page stops working. The new security work would not be saved at all.
**How to fix it:**
1. Stage everything that belongs to this change (the changed files and the new files) before saving.
2. Before you save, check that `git diff --cached --stat` lists all 237 files and shows `demo/bootstrap.php` with its full content.
**How to check the fix worked:** After saving, `git status` lists no demo or test files, and opening `demo/login.php` from a fresh copy works.
**Size of the job:** small (under a day)

### C-02 — Database changes must be done before going live, or logins can break
**How serious:** 🟠 Fix soon  ·  **Type:** Database / Deployment  ·  **How sure:** Likely
**Where:** Password saving (`demo/login.php`, line 38; `demo/insert.php`, line 27; `demo/edit.php`, the update step). Cache plugin save (`demo/plugins/cacheresource.mysql.php`, line 77).
**What is wrong:** New password hashes are about 60 characters long. Old MD5 values were 32. The code assumes the `password` column (the database field for the password) has already been widened to 255 characters. The cache plugin also now writes a new `expire` column (the time the cached page runs out). Both steps are only written in `demo/README.md`. Nothing in the code checks them.
**When it happens:** The site is deployed but nobody runs the `ALTER TABLE` commands from the README.
**What could happen:** If the database cuts long text silently (MySQL's non-strict mode), the upgraded hash is saved cut short. That user can then never log in again, and new users cannot log in either. In strict mode, adding users fails with an error. Existing users of the cache plugin get errors on every cache save.
**How to fix it:**
1. Treat the two `ALTER TABLE` commands as a required release step. Put them in a small, numbered upgrade file (for example `demo/sql/upgrade-1.sql`) and name it in the PR description.
2. Optionally, add a start-up check that refuses to save a password if the column is shorter than 255 characters.
**How to check the fix worked:** Log in with an old MD5 account. Log out and log in again. The second login must still work, and the stored value must start with `$2y$`.
**Size of the job:** small (under a day)

### C-03 — Anyone can lock a known user out for 15 minutes
**How serious:** 🟡 Plan to fix  ·  **Type:** Security / Availability  ·  **How sure:** Certain
**Where:** The login-attempt limit (`demo/bootstrap.php`, lines 233–284; used in `demo/login.php`, line 16).
**What is wrong:** The limit per name is checked before the password. After 5 wrong tries, even the right password is refused for 15 minutes. The counter is also read and then written in two separate steps, so many guesses sent at the same moment can be counted as fewer.
**When it happens:** Someone who knows a user's name types 5 wrong passwords, over and over.
**What could happen:** The real user cannot log in for as long as the attacker keeps going. Fast parallel guessing can also get a few more tries than the limit allows.
**How to fix it:**
1. Keep the per-address limit strict. Make the per-name lock shorter, or let it grow step by step (for example 1, 2, 4 minutes) instead of a flat 15 minutes.
2. Lock the counter file while reading and writing it (for example `flock`, PHP's file lock), so parallel tries are all counted.
**How to check the fix worked:** Make 5 wrong tries for a name from one machine. From another machine, the right password works again within a short wait.
**Size of the job:** small (under a day)

### C-04 — On a fresh install, whoever opens the "add user" page first becomes the first user
**How serious:** 🟡 Plan to fix  ·  **Type:** Security / Setup  ·  **How sure:** Certain
**Where:** The add-user page (`demo/insert.php`, lines 6–9).
**What is wrong:** While the `user` table is empty, `insert.php` lets anyone create a user without logging in. This is on purpose, so a new site can be set up. But anyone who finds a fresh site first gets in. Two people doing it at the same moment can both succeed.
**When it happens:** The site is put online before the owner creates the first user.
**What could happen:** A stranger creates the first account and can then add, edit and delete every user.
**How to fix it:**
1. Allow the first-user setup only when a setup setting is switched on (for example `DEMO_ALLOW_SETUP=1`), or only from the server itself. Or create the first user with a command line script.
2. Add a unique rule on the `name` column in the database, so duplicate names cannot slip in at the same moment.
**How to check the fix worked:** On an empty database without the setting, `insert.php` sends you to the login page.
**Size of the job:** small (under a day)

### C-05 — The new login, delete and form-code logic has no tests
**How serious:** 🟡 Plan to fix  ·  **Type:** Testing  ·  **How sure:** Certain
**Where:** The new smoke test (`tests/smoke_test.php`) only tests the template engine. Nothing tests `demo/bootstrap.php`, `demo/login.php` or `demo/delete.php`.
**What is wrong:** The riskiest new parts have no automatic checks: checking passwords, upgrading MD5 passwords, the attempt limit, form codes, and refusing to delete yourself.
**When it happens:** A later change breaks one of these quietly.
**What could happen:** A security protection could stop working without anyone noticing.
**How to fix it:**
1. Add small tests for `demo_check_password` (MD5, new hash, wrong password), `demo_validate_user` and the attempt-limit functions. These need no database.
2. Run them in the CI next to the smoke test.
**How to check the fix worked:** The CI output lists the new checks, and they fail if you break one of these functions on purpose.
**Size of the job:** medium (a few days)

### Small tidy-ups
- **C-06 — Smoke test leaves temporary folders behind** — `tests/smoke_test.php`, `make_smarty`: delete the temporary folder at the end.
- **C-07 — The debug setting is read in two places** — `demo/index.php`, line 11, reads `DEMO_DEBUG` itself: use the shared setting from `demo/config.php`.
- **C-08 — The fake login check takes a different time for old MD5 accounts** — `demo/login.php`, lines 22–32: users who still have MD5 passwords answer faster, so it is possible to tell which names exist. This goes away once all users are upgraded.

### Conflicts
Not checked — there is no main branch to compare with (we are on `main`, level with `origin/main`).

### How to test it
1. Before testing, run the two `ALTER TABLE` steps from `demo/README.md` (C-02). Set `DEMO_DB_USER` and `DEMO_DB_PASSWORD`. Without them, every page should show "The service is not set up yet".
2. With an empty `user` table, open `insert.php` and create a user. You should be sent to the login page with "The first user was created" (see C-04).
3. Log in with a wrong password 5 times. The 6th try should say "Too many failed login attempts", even with the right password (C-03).
4. Log in with an old MD5 account. Log out and log in again. Both should work, and the database value should now start with `$2y$` (C-02).
5. On the user list, type `<script>alert(1)</script>` as a name on the add page. The list should show it as plain text, with no pop-up.
6. Delete a user with the bin icon and with "Delete selected". Each should ask for confirmation and show "1 user was deleted". Opening `delete.php` in the address bar should show "can only be deleted with the Delete button".
7. Edit a user with an empty password box and log in as them with the old password. It should still work. Your own row should have no delete box.
8. Open `templates_c/` in the browser (on Apache). You should get "Forbidden". Check that the CI run passes the syntax check and the smoke test.

### PR description (ready to paste)
```markdown
**Secure the demo user-management site and fix CI**
- Safe database access (prepared statements); DB login from environment variables
- Passwords hashed with password_hash; old MD5 passwords upgraded at login; logout added
- CSRF tokens on all forms; delete is POST-only; login-attempt limit; auto HTML escaping
- Removed duplicate demo/libs engine copy and generated files; added .gitignore and .htaccess
- CI: PHP syntax check + new smoke test instead of the missing phpunit.sh
Risks: run the ALTER TABLE steps in demo/README.md first (C-02); first-user setup is open on an empty table (C-04)
Test: follow "How to test it" in simple-review/3-changes-pr.md
```

### What we could not check
- We did not run the site, the tests or the CI (review rules: look only). We also did not check that the `ALTER TABLE` commands work on the real database.
- We could not see the real database structure (column sizes, unique rules), so C-02 depends on how the `password` column is set up today.
- We read the deleted `demo/libs` files only as a list. We assume they were an unchanged copy of `libs/`.
- We did not check that the outside style and script files (fonts, Bootstrap, jQuery) match the new integrity codes (`integrity`, a fingerprint the browser checks).
