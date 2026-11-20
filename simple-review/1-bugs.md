# Bugs, errors and code standards — smarty-php-template-engine-archive
Date: 2026-11-20 · Part of the code review · Start with the [reviewed outcomes](REVIEWED-OUTCOMES.md)

We found 8 bugs (1 to fix soon, 7 to plan) and 2 code-standard issues, plus a few small tidy-ups. The most important one: when certain PHP errors happen while a page is being built, the engine does not clean up. Visitors then see half a page, and the engine leaves its own settings switched on (B-01).

| ID | Bug (plain words) | How serious | Where |
|---|---|---|---|
| B-01 | Some errors are not caught, so visitors see half a page and settings are left changed | 🟠 Fix soon | the part that builds pages (`libs/sysplugins/smarty_internal_templatebase.php`) |
| B-02 | Pages meant to be kept "forever" are saved as already out of date in the PDO page store | 🟡 Plan to fix | the PDO page-store example (`demo/plugins/cacheresource.pdo.php`) |
| B-03 | The PDO page store crashes when the database connection uses "real" prepared statements | 🟡 Plan to fix | the PDO page-store example (`demo/plugins/cacheresource.pdo.php`) |
| B-04 | The login-attempt limit can be passed by sending many tries at once, and fails silently | 🟡 Plan to fix | the login-attempt limit (`demo/bootstrap.php`) |
| B-05 | Two people acting at the same moment can create duplicate users or delete every user | 🟡 Plan to fix | add and delete user pages (`demo/insert.php`, `demo/delete.php`) |
| B-06 | Names with non-English letters are rejected as "too long" when they are not | 🟡 Plan to fix | the form check (`demo/bootstrap.php`) |
| B-07 | Saved times can be off by hours when the database and PHP use different time zones | 🟡 Plan to fix | the MySQL storage examples (`demo/plugins/*.mysql*.php`, `cacheresource.pdo.php`) |
| B-08 | A date that cannot be read is shown as today's date | 🟡 Plan to fix | the date helper (`libs/plugins/shared.make_timestamp.php`) |
| B-09 | The same code and limits are copied in several places | 🟡 Plan to fix | demo plugins and pages |
| B-10 | Database connections are created inside the code that uses them | 🟡 Plan to fix | demo plugins and `demo/bootstrap.php` |

### B-01 — Some errors are not caught, so visitors see half a page and settings are left changed
**How serious:** 🟠 Fix soon  ·  **Type:** Error handling  ·  **How sure:** Certain
**Where:** The part that builds a page from a template (`libs/sysplugins/smarty_internal_templatebase.php`, lines 198–271). The same pattern is in the template compiler (`libs/sysplugins/smarty_internal_templatecompilerbase.php`, line 473), the compiled-file step (`libs/sysplugins/smarty_template_compiled.php`, line 185), the cached-page step (`libs/sysplugins/smarty_template_resource_base.php`, line 128) and `libs/sysplugins/smarty_resource_recompiled.php`, line 53.
**What is wrong:** While building a page, the engine collects the output in a buffer (a holding area for text before it is sent). If something fails, a cleanup block is meant to empty the buffer and undo the settings it changed. That block only catches `Exception` (one kind of PHP error). Since PHP 7, many failures are of another kind, called `Error` (for example "wrong type" or "method called on nothing"). The cleanup never runs for those. No file in the engine catches `Throwable` (the name that covers both kinds).
**When it happens:** On PHP 8, a template uses `{$items|count}` and `$items` is empty (null). PHP throws a type `Error`. The half-built page stays in the buffer. The demo's error handler then adds "Something went wrong" to it. The visitor gets half a page with an error message mixed in. Also, the engine's error-reporting level and its optional "mute warnings" handler stay switched on for the rest of the request.
**What could happen:** Broken, confusing pages for visitors. Parts of the page that should never appear on their own (for example half a table) are shown. Later code in the same request runs with the wrong error settings, so real problems may be hidden.
**How to fix it:**
1. In each of the five places listed, change `catch (Exception $e)` to `catch (Throwable $e)`. PHP 7.1, the lowest version the project supports, already has `Throwable`.
2. Keep the existing cleanup lines (empty the buffers, switch the handler off, restore the error level) and then throw the error again, as now.
**How to check the fix worked:** Add a smoke test in `tests/smoke_test.php` that renders `string:before{$x|count}after` with `$x = null` on PHP 8. Check that an error is thrown, that `ob_get_level()` (the number of open buffers) is the same as before, and that nothing from "before" is printed.
**Size of the job:** small (under a day)

### B-02 — Pages meant to be kept "forever" are saved as already out of date in the PDO page store
**How serious:** 🟡 Plan to fix  ·  **Type:** Bug (rule fixed in one place but not another)  ·  **How sure:** Certain
**Where:** The PDO page-store example (a plugin that keeps finished pages in a database), in the save step (`demo/plugins/cacheresource.pdo.php`, lines 70, 78 and 254) and the "clear old pages" step (same file, lines 325–326).
**What is wrong:** In Smarty, a page lifetime of `-1` means "never expires". This plugin saves the expiry time as "now plus the lifetime", so `-1` becomes "one second ago". The "clear expired pages" command then deletes these pages. The MySQL version of the same plugin was fixed in this change (it now stores "no expiry" for `-1`). The PDO version was not.
**When it happens:** A site sets `cache_lifetime = -1` for a slow page and uses this plugin. A nightly job runs `clearAllCache(Smarty::CLEAR_EXPIRED)`. Every "forever" page is deleted and must be built again.
**What could happen:** Slower pages after each clean-up. People who set "never expires" do not get what they asked for.
**How to fix it:**
1. In the save step, do what the MySQL plugin does: when the lifetime is below 0, store "no expiry" (an empty value) instead of "now minus one second".
2. Change the table description at the top of the file so `expire` may be empty (NULL).
3. In the delete step, only remove rows where `expire` is set and is in the past.
**How to check the fix worked:** Save a page with lifetime `-1`, run "clear expired", and check the page is still in the table.
**Size of the job:** small (under a day)

### B-03 — The PDO page store crashes when the database connection uses "real" prepared statements
**How serious:** 🟡 Plan to fix  ·  **Type:** Bug (crash under a common setting)  ·  **How sure:** Certain (only with that setting)
**Where:** The save command of the PDO page-store example (`demo/plugins/cacheresource.pdo.php`, lines 63–79).
**What is wrong:** The save command uses the same named slot (for example `:name`, `:content`) twice: once to add a row and once to update it. PHP's database layer only allows this when it "emulates" (simulates) prepared statements. With real prepared statements switched on (`PDO::ATTR_EMULATE_PREPARES => false`), PHP stops with "Invalid parameter number".
**When it happens:** Someone reuses the demo's own database connection for this plugin. The demo switches emulation off (`demo/bootstrap.php`, line 95). Then the first time a page is saved, an error is thrown.
**What could happen:** Page caching does not work at all, and every cached page request ends in an error.
**How to fix it:**
1. In the update part of the command, use `VALUES(name)`, `VALUES(content)` and so on (or new slot names such as `:name2`) instead of repeating the same slots.
2. Or give each repeated value its own slot name and bind it twice in `save()`.
**How to check the fix worked:** Create the plugin with a connection that has emulation off, save the same page twice, and check both saves work.
**Size of the job:** small (under a day)

### B-04 — The login-attempt limit can be passed by sending many tries at once, and fails silently
**How serious:** 🟡 Plan to fix  ·  **Type:** Race condition (two things happening at once) / Error handling  ·  **How sure:** Certain
**Where:** The login-attempt limit (`demo/bootstrap.php`, lines 233–284) used by the login page (`demo/login.php`, lines 16–18 and 48).
**What is wrong:**
- The counter is read, increased and written back in three separate steps, with no lock around them. The lock (`LOCK_EX`) only covers the final write. Many tries sent at the same moment all read the old number, so most of them are not counted.
- The check "are you blocked?" runs before the password check. So all tries sent at the same moment pass it.
- If the temporary folder cannot be created or written, the `@` signs hide the error. The limit then never applies, and nobody is told.
- The block lasts from the *first* failure, not the last. The message always says "wait 15 minutes", but the real wait can be a few seconds.
**When it happens:** Someone sends 50 login attempts for one name at the same moment. All 50 are checked against the database, while the counter may only reach 5 or 6.
**What could happen:** The limit that should stop password guessing lets far more guesses through than set in `max_login_failures` (the setting for allowed failures). On a server with a read-only temporary folder, there is no limit at all.
**How to fix it:**
1. Open the counter file once with `fopen` and `flock` (a file lock), then read, increase and write while holding the lock. Or keep the counter in the database and use one `UPDATE … SET count = count + 1` command.
2. Count the attempt *before* checking the password, and block when the new number is over the limit.
3. Remove the `@` signs. If the folder or file cannot be written, log it and refuse the login with "please try again later".
4. Store the time of the last failure, and work out the real time left for the message.
**How to check the fix worked:** Send 20 wrong logins for one name at the same time (for example with a small script). Check that only the allowed number reach the password check, and that the counter shows 20.
**Size of the job:** small (under a day)

### B-05 — Two people acting at the same moment can create duplicate users or delete every user
**How serious:** 🟡 Plan to fix  ·  **Type:** Race condition / Data problem  ·  **How sure:** Likely (needs two requests at the same moment)
**Where:** The add-user page (`demo/insert.php`, lines 6–9 and 21–29), the edit page (`demo/edit.php`, line 24) and the delete page (`demo/delete.php`, lines 24–30).
**What is wrong:** These pages first *check* something and then *save*, in two separate steps with no lock or database rule in between:
- "Is this name free?" then "add the user". The demo's setup notes do not add a unique rule (unique index) on the name column.
- "Is the table empty?" then "let a visitor who is not logged in create the first user".
- "Are you deleting yourself?" only protects your own account. Two people can delete each other.
**When it happens:**
- Two admins add "anna" at the same second. Both checks say "free". Two "anna" accounts are created.
- The only two admins delete each other at the same second. Both are still logged in when they press Delete, so both deletes run. The table is now empty. Anyone who opens `insert.php` can then create the "first user" without logging in and take over the site.
- On a new install, two visitors open `insert.php` at the same time. Both become users.
**What could happen:** Confusing duplicate accounts. In the worst case the site is left with no users, and the next stranger who visits can create an account and manage all data.
**How to fix it:**
1. Add a unique rule on `user.name` in the setup notes (`demo/README.md`). Then catch the "duplicate" database error and show "A user with this name already exists".
2. In the delete page, refuse a delete that would leave no users. Use one database command, for example "delete only if at least one other user remains", inside a transaction (all-or-nothing saving).
3. For the first user, do the count and the insert inside one transaction with a lock (`SELECT … FOR UPDATE` on the table, or a setup flag that can only be set once).
**How to check the fix worked:** Send two "add anna" forms at the same moment and check only one account exists. Try the "delete each other" case with two browsers and check one user always remains.
**Size of the job:** medium (a few days)

### B-06 — Names with non-English letters are rejected as "too long" when they are not
**How serious:** 🟡 Plan to fix  ·  **Type:** Bug (wrong measure)  ·  **How sure:** Certain
**Where:** The check of the add and edit forms (`demo/bootstrap.php`, line 216). The forms allow 100 characters (`demo/templates/insert.tpl`, line 41; `demo/templates/edit.tpl`, line 18).
**What is wrong:** The code uses `strlen` (counts bytes, the pieces a letter is stored in), but the message says "characters". Letters like "é", "ß", Hindi or Chinese letters take 2–4 bytes each.
**When it happens:** Someone types a name of 40 Chinese characters. The browser allows it (under 100 characters), but the server counts 120 bytes and says "The name may be at most 100 characters long."
**What could happen:** People with non-English names cannot be added or edited, and the error message is wrong, which confuses them.
**How to fix it:**
1. Use `mb_strlen($name, 'UTF-8')` (counts real characters) in the name check.
2. Make sure the database column allows 100 characters in `utf8mb4` (the full Unicode character set).
**How to check the fix worked:** Add a user with a name of 40 Chinese characters and check it is saved. Try 101 characters and check it is refused.
**Size of the job:** small (under a day)

### B-07 — Saved times can be off by hours when the database and PHP use different time zones
**How serious:** 🟡 Plan to fix  ·  **Type:** Bug (dates and time zones)  ·  **How sure:** Needs checking (depends on server settings)
**Where:** The MySQL storage examples: the page store (`demo/plugins/cacheresource.mysql.php`, lines 102 and 125), the PDO page store (`demo/plugins/cacheresource.pdo.php`, line 207) and the two template stores (`demo/plugins/resource.mysql.php`, lines 292 and 314; `demo/plugins/resource.mysqls.php`, line 397).
**What is wrong:** The database writes "modified" times in *its* time zone, as plain text without a zone. PHP then reads that text with `strtotime` (turns text into a time) in *PHP's* time zone. If the two zones differ, every time is shifted by the difference.
**When it happens:** The database runs in UTC and PHP runs in India time (5½ hours ahead). A template saved in the database at 10:00 looks like it was saved 5½ hours earlier. A cached page with a 2-minute life looks 5½ hours old and is never used. Or, with the zones the other way round, edits to a template in the database are not picked up for hours.
**What could happen:** Page caching silently stops working (slower site), or people see old pages and old templates for hours after a change.
**How to fix it:**
1. Read the time from the database as a number: `SELECT UNIX_TIMESTAMP(modified) …` and use that number directly, without `strtotime`.
2. Or set the database connection's time zone to match PHP's when the connection is opened.
**How to check the fix worked:** Set PHP and the database to different time zones, save a page with a 2-minute life, and check it is reused within those 2 minutes.
**Size of the job:** small (under a day)

### B-08 — A date that cannot be read is shown as today's date
**How serious:** 🟡 Plan to fix  ·  **Type:** Bug (wrong data shown)  ·  **How sure:** Certain
**Where:** The helper that turns text into a date for the `date_format` modifier (a template tool that formats dates) (`libs/plugins/shared.make_timestamp.php`, lines 41–46).
**What is wrong:** If the text is not a date PHP understands, the helper quietly returns "now". The `default_date` option (the value meant for missing dates) is never used in this case.
**When it happens:** A template shows `{$order.shipped|date_format:"%d.%m.%Y":"–"}`, and the value is `31/12/2025` (a day-first date that PHP reads as month 31) or the text `pending`. The page shows today's date instead of "–".
**What could happen:** Customers and staff see wrong dates (for example an order "shipped today" that never shipped), with no error to warn anyone.
**How to fix it:**
1. When the text cannot be read, return `false` instead of `time()`.
2. In `smarty_modifier_date_format` (`libs/plugins/modifier.date_format.php`), when the result is `false`, use `default_date` if given, otherwise return an empty text.
**How to check the fix worked:** Render `{"pending"|date_format:"%Y":"none"}` and check it shows "none", not the current year.
**Size of the job:** small (under a day)

### B-09 — The same code and limits are copied in several places
**How serious:** 🟡 Plan to fix  ·  **Type:** Code standard (don't repeat yourself, no magic values)  ·  **How sure:** Certain
**Where:** The database login code in three plugins (`demo/plugins/cacheresource.mysql.php`, lines 57–73; `demo/plugins/resource.mysql.php`, lines 50–68; `demo/plugins/resource.mysqls.php`, lines 46–64). The page head with styles and outside links in `demo/templates/view.tpl` (lines 1–178) and `demo/templates/insert.tpl` (lines 1–23), instead of the shared `header.tpl`. The form limits 100, 150 and 8 written in `demo/bootstrap.php` (lines 216–223) and again in `demo/templates/insert.tpl` and `edit.tpl`.
**What is wrong:** "Don't repeat yourself" means each rule or piece of code lives in one place. Here the same code is copied. "Magic values" are fixed numbers written straight in the code. Here the limits are typed in both the server check and the forms.
**When it happens:** Someone changes the maximum age to 120 in the server check but forgets the forms. Or someone fixes the connection code in one plugin but not the other two. This review already found such a case: a rule fixed in the MySQL page store but not in the PDO one (B-02).
**What could happen:** The browser and the server disagree, so people get confusing messages. Fixes are applied in one place and missed in others.
**How to fix it:**
1. Put the connection code in one small shared function or base class that the three plugins use.
2. Make `view.tpl` and `insert.tpl` include `header.tpl` and `footer.tpl`, and move the shared styles into one stylesheet.
3. Put the limits in `demo/config.php` (for example `name_max_length`), give them to the templates, and use them in both places.
**How to check the fix worked:** Change one limit in `config.php` and check the form and the server both follow it.
**Size of the job:** small (under a day)

### B-10 — Database connections are created inside the code that uses them
**How serious:** 🟡 Plan to fix  ·  **Type:** Code standard (dependency inversion, separation of layers)  ·  **How sure:** Certain
**Where:** The constructors of the MySQL plugins (`demo/plugins/cacheresource.mysql.php`, line 65; `demo/plugins/resource.mysql.php`, line 63; `demo/plugins/resource.mysqls.php`, line 69). The demo's shared settings and connection, kept in a global variable and a hidden static variable (`demo/bootstrap.php`, lines 10–15 and 80–102). The pages mix form handling, database commands and screen output in one script (for example `demo/edit.php`).
**What is wrong:** "Dependency inversion" means the main code is *given* its database connection instead of making it itself. Then it can be swapped or tested. These plugins read server settings and open their own connection. The demo pages call global helper functions for the database. The PDO page store (`cacheresource.pdo.php`, line 125) already does this the better way: it receives the connection.
**When it happens:** Someone wants to test the plugins or pages without a real MySQL server, or to share one connection with the rest of their site. They cannot, without editing the plugin.
**What could happen:** No automatic tests are possible for these parts. Extra connections are opened. Changes are slower and riskier.
**How to fix it:**
1. Give the MySQL plugins a constructor that takes a `PDO` connection, as the PDO plugin does. Keep a small helper that builds the connection from server settings.
2. In the demo, create the connection and settings once and pass them to small functions or classes (for example a `UserRepository` that holds all user database commands).
**How to check the fix worked:** Write a test that gives a plugin a SQLite in-memory connection and saves and reads a page.
**Size of the job:** medium (a few days)

### Small tidy-ups
- **B-11 — "Shorten in the middle" gives one character too few** — the `truncate` modifier (`libs/plugins/modifier.truncate.php`, lines 45–46 and 59): with an odd length, both halves are rounded down (e.g. 9 characters instead of 10). Without the `mbstring` extension and a length shorter than "...", the *whole* text is returned. Use `floor` and `ceil` for the two halves, and return only "..." when the length is 0.
- **B-12 — A failed file write leaves a temporary file and a changed file-permission setting** — the file writer (`libs/sysplugins/smarty_internal_runtime_writefile.php`, lines 48–89): delete the temporary file and run `umask($old_umask)` (restore the file-permission setting) before each `throw`.
- **B-13 — "Nothing was changed" is shown when the user was deleted meanwhile** — the edit page (`demo/edit.php`, line 43): when 0 rows changed, check the user still exists and say "User not found" if not.
- **B-14 — Clearing one cache group can clear others** — the delete step in both MySQL page stores (`demo/plugins/cacheresource.mysql.php`, line 197; `cacheresource.pdo.php`, line 317): `_` and `%` in a cache name act as wildcards in `LIKE`; escape them first.
- **B-15 — Required values after optional ones in the PDO plugin** — `fetch()` and `save()` (`demo/plugins/cacheresource.pdo.php`, lines 199 and 247): PHP 8 warns about this ("deprecated"); remove the `= null` defaults.
- **B-16 — Very large files that do many jobs** — the template compiler (`libs/sysplugins/smarty_internal_templatecompilerbase.php`, 1,744 lines) and the main class (`libs/Smarty.class.php`, 1,397 lines): this is the original engine code; only split it if the engine is developed further.

### Code standards at a glance
| Standard (plain words) | Followed? | In a few words (with IDs) |
|---|---|---|
| Each part does one job (single responsibility) | ⚠️ Partly | Engine has 1,000+ line files; demo `bootstrap.php` does 8 jobs but is in clear sections (B-16) |
| New cases can be added without editing old code (open/closed) | ✅ Yes | Engine uses plugins for new tags, modifiers and storage |
| Specialised versions behave like the general one (Liskov substitution) | ⚠️ Partly | PDO page store handles "never expires" differently from the others (B-02) |
| No "do-everything" interfaces (interface segregation) | ✅ Yes | Storage plugins only need a few methods |
| Main logic is given its database, not making it itself (dependency inversion) | ❌ No | MySQL plugins and demo pages open their own connection (B-10) |
| Don't repeat yourself | ❌ No | Connection code, page head and limits copied (B-09) |
| Keep it simple | ⚠️ Partly | Demo is simple; engine compiler is long and deeply nested (B-16) |
| Clear names | ✅ Yes | Demo names are clear; some short engine names (`$_tpl`) are older style |
| No magic values | ⚠️ Partly | Most settings are in `config.php`; form limits are not (B-09) |
| Separation of layers (screens, rules, database) | ⚠️ Partly | Templates are separate; pages mix rules and database commands (B-10) |
| The project's style rules | ⚠️ Partly | No style config file (such as `.editorconfig` or `phpcs.xml`); PHP uses spaces, templates use tabs, and files mix line endings |

### What we checked
- Signing in → upgrading an old password → being sent to the user list → signing out (`demo/login.php`, `demo/bootstrap.php`, `demo/logout.php`).
- Creating the first user → adding a user → editing a user (with and without a new password) → deleting one or several users (`demo/insert.php`, `demo/edit.php`, `demo/delete.php`).
- Showing the user list page by page (`demo/view.php` and `view.tpl`), and the example page (`demo/index.php`).
- Rendering a template in the engine: fetch → compile → write the compiled file → page cache → error cleanup.
- The changed database storage plugins (`demo/plugins/`) and the smoke test (`tests/smoke_test.php`).
- A sweep for `catch` blocks, `TODO`/`FIXME` notes, file writing, dates and the text-shortening tool.

### What we could not check
- The engine's generated parser and lexer files (`smarty_internal_templateparser.php`, `smarty_internal_templatelexer.php`, about 4,000 lines). They are machine-made and were not read line by line.
- Most of the 47 built-in template tools in `libs/plugins/`. Only the date, text-shortening and timestamp helpers were read.
- The real database layout of the demo's `user` table (for example, whether `name` already has a unique rule) — it is not in the project.
- Nothing was run (by the review rules), so B-07 depends on the real server time zones.
