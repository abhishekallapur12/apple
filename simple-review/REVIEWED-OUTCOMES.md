# Reviewed Outcomes — smarty-php-template-engine-archive
Date: 2026-11-20

> This is the short version, written for everyone. Every issue links to the file where it is explained in
> full: where it is, when it happens, what could happen, and how to fix it step by step.

## The full report
| File | What is in it | Issues |
|---|---|---|
| [1-bugs.md](1-bugs.md) | Bugs, errors and code standards | 16 (🔴 0 · 🟠 1 · 🟡 9 · ⚪ 6) |
| [2-risks.md](2-risks.md) | Security, speed, testing and reliability | 14 (🔴 0 · 🟠 2 · 🟡 8 · ⚪ 4) |
| [3-changes-pr.md](3-changes-pr.md) | The latest changes (PR review) and its verdict | 8 (🔴 0 · 🟠 2 · 🟡 3 · ⚪ 3) |
| [4-about-project.md](4-about-project.md) | What the project is, how it works, what it is built with | — |

The counts above are as each file first rated them. After double-checking and merging duplicates, there are 34 different issues (see section 4).

## 1. In one minute
This project is a saved copy of Smarty, a tool that fills web page layouts with data. It also has a small example website where people log in and manage a list of users. Recent unsaved work has made the example website much safer. It now uses safe database commands, strong password storage and anti-forgery codes on forms. The biggest problem is a process one: only the deleted files are marked to be saved. Saving now would keep the deletions but lose all the new work, and the example website would stop working (C-01). The most important thing to do is to mark all the changes before saving them. Then run the two database steps from the setup guide, and fix the login-attempt limit, which people can get around.

## 2. Overall condition
| Area | Condition | Why, in a few words (with IDs) |
|---|---|---|
| Bugs and errors | 🟡 Fair | Some engine errors leave half-built pages (B-01); login limit counts badly (B-04); smaller data bugs (B-02, B-03, B-05–B-08) |
| Code standards (clean, well-organised code) | 🟡 Fair | Copied code and fixed numbers (B-09); plugins open their own database connection (B-10); very large engine files (B-16) |
| Security | 🟡 Fair | Mostly well protected. Old weak passwords remain (R-01); login limit can be bypassed (R-02); no user roles (R-03) |
| Speed | 🟢 Good | No real speed problems found; the user list is shown in pages of 50 |
| Testing and reliability | 🟡 Fair | Only 6 small engine checks; login, delete and form codes have no tests (R-05 = C-05); important actions are not logged (R-09) |
| Latest changes (PR) | ⚠️ | Good work, but only half marked to be saved (C-01), and database steps are required first (C-02) |

## 3. Latest changes (PR review) in short
From `3-changes-pr.md`:
- **What was reviewed:** the recent changes on `main`. There was no other branch to compare with, so it covers the 3 saved changes and, above all, the large set of unsaved changes.
- **Verdict:** ⚠️ Accept after fixing C-01 and C-02. The code is good, but it is not all marked to be saved, and two database steps must be done before going live.
- **What changed:**
  - All database commands in the example website are now safe from SQL injection (a trick where an attacker types database commands into a form).
  - Passwords are stored with a strong method instead of MD5 (an old, weak method). Old MD5 passwords are upgraded when the person logs in again.
  - Forms carry anti-forgery codes, deleting needs a button press, and page text is made harmless automatically.
  - Repeated wrong logins are limited, and a logout button was added.
  - A duplicate copy of the engine (about 200 files) was removed. The automatic checks now run a working basic test.
- **Size:** 3 saved changes (2 only edit the README text, +10 / −1 lines) plus 237 unsaved files, +828 / −33,648 lines.
- **Problems in these changes:** 🔴 0 · 🟠 2 · 🟡 3 · ⚪ 3 — fix C-01 and C-02 before accepting.
- **Conflicts:** not checked. There is no other branch to compare with.

[Full PR review in 3-changes-pr.md](3-changes-pr.md)

## 4. All issues (after double-checking)
🔴 Fix now: 0 · 🟠 Fix soon: 5 · 🟡 Plan to fix: 16 · ⚪ Nice to have: 13

| ID | Problem (plain words) | How serious | Size of fix | Details |
|---|---|---|---|---|
| C-01 | Only the deletions are marked to be saved. Saving now would break the example website | 🟠 Fix soon | small | [3-changes-pr.md](3-changes-pr.md) |
| C-02 | Database changes must be made before going live, or logins can break | 🟠 Fix soon | small | [3-changes-pr.md](3-changes-pr.md) |
| R-02 = B-04 = C-03 | The login-attempt limit can be bypassed with many tries at once, can lock real users out, and fails silently | 🟠 Fix soon | medium | [2-risks.md](2-risks.md), [1-bugs.md](1-bugs.md), [3-changes-pr.md](3-changes-pr.md) |
| R-01 | Old passwords stay in the database as plain text or as weak MD5 | 🟠 Fix soon | small | [2-risks.md](2-risks.md) |
| B-01 | Some errors are not caught, so visitors see half a page and settings stay changed | 🟠 Fix soon | small | [1-bugs.md](1-bugs.md) |
| R-07 = C-04 | On a new install, anyone can create the first account | 🟡 Plan to fix | small | [2-risks.md](2-risks.md), [3-changes-pr.md](3-changes-pr.md) |
| R-03 | Every logged-in person can change anyone's password and delete anyone | 🟡 Plan to fix | medium | [2-risks.md](2-risks.md) |
| B-05 | Two people acting at the same moment can create duplicate users or delete every user | 🟡 Plan to fix | medium | [1-bugs.md](1-bugs.md) |
| R-04 | Changing a password does not log out other open sessions | 🟡 Plan to fix | small | [2-risks.md](2-risks.md) |
| R-06 | The example page shows an unchecked server value and caches it for everyone | 🟡 Plan to fix | small | [2-risks.md](2-risks.md) |
| R-08 | Logins may travel without encryption behind a proxy | 🟡 Plan to fix | small | [2-risks.md](2-risks.md) |
| R-10 | The engine fully trusts whoever writes templates | 🟡 Plan to fix | small | [2-risks.md](2-risks.md) |
| R-05 = C-05 | Login, delete and form-code logic have no automatic tests | 🟡 Plan to fix | medium | [2-risks.md](2-risks.md), [3-changes-pr.md](3-changes-pr.md) |
| R-09 | Important actions (logins, deletes, password changes) are not logged | 🟡 Plan to fix | small | [2-risks.md](2-risks.md) |
| B-02 | "Keep forever" pages are saved as already out of date in the PDO page store | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-03 | The PDO page store crashes with a common database setting | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-06 | Names with non-English letters are wrongly rejected as "too long" | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-07 | Saved times can be off by hours when time zones differ | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-08 | A date that cannot be read is shown as today's date | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-09 | The same code and limits are copied in several places | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-10 | Database connections are created inside the code that uses them | 🟡 Plan to fix | medium | [1-bugs.md](1-bugs.md) |
| B-11 | "Shorten in the middle" gives one character too few | ⚪ Nice to have | small | [1-bugs.md](1-bugs.md) |
| B-12 | A failed file write leaves a temporary file behind | ⚪ Nice to have | small | [1-bugs.md](1-bugs.md) |
| B-13 | "Nothing was changed" is shown when the user was already deleted | ⚪ Nice to have | small | [1-bugs.md](1-bugs.md) |
| B-14 | Clearing one cache group can clear others | ⚪ Nice to have | small | [1-bugs.md](1-bugs.md) |
| B-15 | Required values after optional ones cause PHP 8 warnings | ⚪ Nice to have | small | [1-bugs.md](1-bugs.md) |
| B-16 | Very large engine files that do many jobs | ⚪ Nice to have | large | [1-bugs.md](1-bugs.md) |
| R-11 | No Content-Security-Policy header | ⚪ Nice to have | small | [2-risks.md](2-risks.md) |
| R-12 | Visitor addresses are sent to Google Fonts | ⚪ Nice to have | small | [2-risks.md](2-risks.md) |
| R-13 | Generated files are only protected on Apache web servers | ⚪ Nice to have | small | [2-risks.md](2-risks.md) |
| R-14 | The automatic check has broad rights and unpinned outside tools | ⚪ Nice to have | small | [2-risks.md](2-risks.md) |
| C-06 | The basic test leaves temporary folders behind | ⚪ Nice to have | small | [3-changes-pr.md](3-changes-pr.md) |
| C-07 | The debug setting is read in two places | ⚪ Nice to have | small | [3-changes-pr.md](3-changes-pr.md) |
| C-08 | Login answers faster for old MD5 accounts, which shows which names exist | ⚪ Nice to have | small | [3-changes-pr.md](3-changes-pr.md) |

## 5. What to do next
- **This week:**
  - **C-01** — Mark all changed and new files to be saved, not only the deletions, before saving.
  - **C-02** — Run the two database steps from the setup guide (wider password column, new cache expiry column) as a required release step.
  - **R-02 = B-04 = C-03** — Count each login try before checking the password, with a lock, in one shared place. Do not hide errors.
  - **R-01** — Run a one-time clean-up that protects old MD5 passwords and removes plain-text ones.
  - **B-01** — In five places in the engine, catch every kind of error (`Throwable`) so the clean-up always runs.
- **This month:** protect first-user setup (R-07 = C-04), decide on user roles (R-03), stop duplicate users and "everyone deleted" (B-05), log out other sessions after a password change (R-04), add tests for login, delete and form codes (R-05 = C-05), and add an activity log (R-09). Also fix R-06, R-08 and R-10.
- **Later:** the data bugs in the example plugins and helpers (B-02, B-03, B-06, B-07, B-08), the code tidy-up (B-09, B-10), and the ⚪ small tidy-ups.

## 6. The top 3, explained simply
1. **C-01 — Only half of the work is marked to be saved.** If someone saves now, the new security work is lost and every page of the example website stops working. It takes minutes to fix and blocks everything else. [Full fix](3-changes-pr.md)
2. **C-02 — Database steps must come first.** New passwords are longer than the old ones. If the database column is not widened first, people may never be able to log in again. [Full fix](3-changes-pr.md)
3. **R-02 = B-04 = C-03 — The login-attempt limit can be bypassed.** An attacker who sends many guesses at the same moment gets far more tries than allowed, and can also lock real users out. This limit is the main guard against password guessing, so it must work. [Full fix](2-risks.md)

## 7. Double-check of serious issues
All five serious issues were checked in the code and confirmed: C-01, C-02, R-01, R-02 and B-01. These decisions changed or merged:

| Issue | First rated | Our decision | Why (plain words) |
|---|---|---|---|
| R-02 = B-04 = C-03 | R-02 🟠 · B-04 🟡 · C-03 🟡 | Merged, 🟠 Fix soon | All three describe the same login-attempt limit (`demo/bootstrap.php`, lines 233–284). The code checks "are you blocked?" before the password and only records the failure afterwards, with no lock, so parallel tries slip through. It is the main guard against password guessing, so the higher level is kept. |
| R-07 = C-04 | 🟡 · 🟡 | Merged, 🟡 Plan to fix | Both describe the open first-user setup in the add-user page (`demo/insert.php`, lines 6–9). The "two people at once" case is also mentioned in B-05, which stays separate because it covers other cases. |
| R-05 = C-05 | 🟡 · 🟡 | Merged, 🟡 Plan to fix | Both describe the missing tests for login, delete and form codes. |

## 8. What we could not check
- Nothing was run: not the website, not the tests, not the automatic checks (review rules: look only).
- The real database layout of the `user` table is not in the project: password column size, whether names must be unique, and how many old MD5 or plain-text passwords exist (C-02, R-01, B-05).
- How the real web server is set up (Host value handling, HTTPS, proxy). This decides R-06 and R-08.
- The real server time zones, which decide B-07.
- The engine's machine-generated template reader files (about 4,000 lines) and most of the 47 built-in template helpers were not read line by line.
- The deleted `demo/libs` files were only read as a list. We assume they were an unchanged copy of `libs/`.
- Whether the outside style and script files (fonts, Bootstrap, jQuery) match their integrity codes.
- The MySQL and Composer versions are not stated anywhere in the project. Version support was not checked online.

## 9. Words used in this review
- **Template engine** — a tool that fills page layouts with data, keeping the look apart from the program.
- **Template** — a page layout with placeholders, such as `{$name}`.
- **Compiling** — turning a template into a ready-to-run PHP file, so it shows faster next time.
- **Cache / page store** — finished pages saved and reused, so they do not have to be built again.
- **Plugin** — a small add-on that gives the engine an extra feature.
- **Buffer** — a holding area where page text is collected before it is sent to the visitor.
- **Exception / Error / Throwable** — PHP's kinds of failure; `Throwable` covers both.
- **SQL injection** — a trick where an attacker types database commands into a form.
- **XSS (cross-site scripting)** — a harmful script that runs in other visitors' browsers.
- **CSRF token (anti-forgery code)** — a hidden code that proves a form really came from this site.
- **Hashing / `password_hash`** — a strong one-way scramble for storing passwords safely.
- **MD5** — an old, fast scramble that is easy to crack.
- **Session** — the "logged-in" state the server remembers for a visitor.
- **Race condition** — a problem when two things happen at the same moment and get in each other's way.
- **Lock (`flock`)** — a way to make sure only one request changes a file at a time.
- **Transaction** — all-or-nothing saving in the database.
- **Unique rule (unique index)** — a database rule that stops two rows having the same value.
- **PDO / prepared statement** — PHP's built-in database layer; a command where user input is sent separately.
- **Proxy / load balancer** — a server in front of the site that passes visitors on to it.
- **Secure cookie flag** — a setting that sends the login cookie over encrypted connections only.
- **Content-Security-Policy** — a header that tells browsers which scripts the site allows.
- **CI (automatic checks)** — the checks that run on every upload (here GitHub Actions).
- **Smoke test** — a quick test that the basics work.
- **Staging area / intent to add** — git's list of changes picked for the next save; "intent to add" means the file is named there but its content is not.
- **Commit (saved change)** — one saved step in the project's history.
- **Dependency inversion** — giving code its database connection instead of letting it make its own.
- **Don't repeat yourself / magic values** — keep each rule in one place; avoid fixed numbers typed straight into the code.
- **Release candidate** — a test version made just before the final release.
