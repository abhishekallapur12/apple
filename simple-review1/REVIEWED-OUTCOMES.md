# Reviewed Outcomes — smarty-php-template-engine-archive
Date: 2026-11-20

> This is the short version, written for everyone. Every issue links to the file where it is explained in
> full: where it is, when it happens, what could happen, and how to fix it step by step.

## The full report
| File | What is in it | Issues (as first rated in each file) |
|---|---|---|
| [1-bugs.md](1-bugs.md) | Bugs and errors in the code | 14 (🔴 0 · 🟠 2 · 🟡 6 · ⚪ 6) |
| [2-risks.md](2-risks.md) | Security, speed, testing and reliability | 14 (🔴 3 · 🟠 3 · 🟡 5 · ⚪ 3) |
| [3-changes-pr.md](3-changes-pr.md) | The latest changes (PR review) and its verdict | 2 (🔴 0 · 🟠 1 · 🟡 1 · ⚪ 0) |
| [4-about-project.md](4-about-project.md) | What the project is, how it works, what it is built with | — |

All four files were complete. Section 3 below shows the levels after our double-check, with duplicates merged.

## 1. In one minute
This project is a stored copy of Smarty, a well-known template engine (a tool that fills page layouts with data to make web pages) for PHP. Next to it sits a small demo website that logs people in and lists, adds, edits and deletes users. The engine itself looks carefully written. The demo website is not safe to put online: attackers can type database commands into its forms, anyone can see or delete every user without logging in, and passwords are stored weakly and even shown on screen. The latest changes only add an "historical archive" note and make the demo login actually remember who logged in; they are fine to accept. The single most important thing: **never put the demo online as it is**. If it will be used, fix the three 🔴 security issues first (R-01, R-02, R-03).

## 2. Overall condition
| Area | Condition | Why, in a few words (with IDs) |
|---|---|---|
| Bugs and errors | 🔴 Needs work | Changing a password locks the person out, and database errors are never checked (B-01, B-02). Many smaller demo bugs (B-03 to B-08). |
| Security | 🔴 Needs work | Database command tricks, no login check, weak and visible passwords (R-01, R-02, R-03), plus unsafe page text and an all-powerful database login (R-04, R-05). |
| Speed | 🟡 Fair | The user list loads every user at once (R-10). The cache plugin example can wipe the whole cache (B-08). |
| Testing and reliability | 🟡 Fair | No tests and a broken automatic check (R-06), errors not logged (R-09), generated files and a second library copy stored in the project (R-11). |
| Latest changes (PR) | ⚠️ | Small and useful. Accept after adding a fresh session number at login (C-01), and save (commit) the unsaved login change. |

## 3. All issues (after double-checking)
🔴 Fix now: 3 · 🟠 Fix soon: 3 · 🟡 Plan to fix: 11 · ⚪ Nice to have: 7

| ID | Problem (plain words) | How serious | Size of fix | Details |
|---|---|---|---|---|
| R-01 | Attackers can type database commands into the forms and links, and log in without a password | 🔴 Fix now | small | [2-risks.md](2-risks.md) |
| R-02 | No login check: anyone can see, change or delete any user | 🔴 Fix now | medium | [2-risks.md](2-risks.md) |
| R-03 = B-01 | Passwords are stored weakly (MD5) or as plain text and shown on screen; after a password edit the person cannot log in | 🔴 Fix now | small | [2-risks.md](2-risks.md), [1-bugs.md](1-bugs.md) |
| B-02 = R-09 | Database problems are never checked or logged, so pages crash or claim success when nothing was saved | 🟠 Fix soon | small | [1-bugs.md](1-bugs.md), [2-risks.md](2-risks.md) |
| R-04 | Harmful scripts typed as a user name run in other visitors' browsers | 🟠 Fix soon | small | [2-risks.md](2-risks.md) |
| R-05 | The demo uses the all-powerful database "root" login with no password, written in the code | 🟠 Fix soon | small | [2-risks.md](2-risks.md) |
| R-06 = C-02 | No tests, and the automatic check fails on every change | 🟡 Plan to fix | medium | [2-risks.md](2-risks.md), [3-changes-pr.md](3-changes-pr.md) |
| C-01 = R-08 | The session number is not renewed at login, and login tries are unlimited | 🟡 Plan to fix | small | [3-changes-pr.md](3-changes-pr.md), [2-risks.md](2-risks.md) |
| B-03 | After a delete, the deleted user can still appear in the list | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-04 | Pages keep running after sending the visitor to another page | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-05 | The edit page gives warnings and empty forms when saving or with an unknown user | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-06 | An empty user list shows a warning | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-07 | The user table shows wrong column titles, and its buttons do nothing | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| B-08 | "Clear expired cache" in the example MySQL cache plugin deletes the whole cache | 🟡 Plan to fix | small | [1-bugs.md](1-bugs.md) |
| R-07 | The debug window is on for every visitor of the example page | 🟡 Plan to fix | small | [2-risks.md](2-risks.md) |
| R-10 | The user list loads every user at once, with no pages | 🟡 Plan to fix | small | [2-risks.md](2-risks.md) |
| R-11 = B-12 = B-13 | Generated files and a second copy of the engine are stored in the project | 🟡 Plan to fix | small | [2-risks.md](2-risks.md), [1-bugs.md](1-bugs.md) |
| B-09 | Pages are built with two page headers | ⚪ Nice to have | small | [1-bugs.md](1-bugs.md) |
| B-10 | Page title shows "foo -" | ⚪ Nice to have | small | [1-bugs.md](1-bugs.md) |
| B-11 | The add page shows the same form twice | ⚪ Nice to have | small | [1-bugs.md](1-bugs.md) |
| B-14 | The date picker stops at 2010 | ⚪ Nice to have | small | [1-bugs.md](1-bugs.md) |
| R-12 | Password boxes show the typed text | ⚪ Nice to have | small | [2-risks.md](2-risks.md) |
| R-13 | Outside scripts are loaded without a safety check | ⚪ Nice to have | small | [2-risks.md](2-risks.md) |
| R-14 | Example database logins are written in the plugins | ⚪ Nice to have | small | [2-risks.md](2-risks.md) |

## 4. What to do next
First decide one thing: is the demo only kept for reference, or will it run anywhere? If it stays an archive, make sure it is never put online and switch off the broken automatic check (R-06). If it will run, follow this plan:

- **This week:**
  - R-01 — Send user input to the database separately from the command (prepared statements).
  - R-02 — Send visitors who are not logged in to the login page; make delete a confirmed form, not a link.
  - R-03 = B-01 — Store passwords with PHP's safe `password_hash`, use the same method on add, edit and login, and stop showing passwords.
  - B-02 = R-09 — Check every database step, show a friendly message and write the real error to a log.
  - R-04 — Turn on automatic escaping (making text harmless before showing it) in every demo page.
  - R-05 — Use a limited database login with a strong password, kept outside the code.
  - Save (commit) the unsaved login change and add a fresh session number at login (C-01).
- **This month:** R-06 = C-02 (restore tests or switch the check off), R-08 (limit login tries), B-03 to B-07 (demo page bugs), B-08 (cache plugin), R-07 (debug window off), R-10 (pages in the user list), R-11 (remove generated files and the second engine copy).
- **Later:** the ⚪ tidy-ups (B-09, B-10, B-11, B-14, R-12, R-13, R-14).

## 5. The top 3, explained simply
1. **R-01 — Attackers can type database commands into the forms.** Anyone can log in as anyone, read all data, or wipe the user table, with a single line typed into the name box. It comes first because it needs no skill and no login. [Full fix in 2-risks.md](2-risks.md)
2. **R-02 — No login check.** Anyone who knows a page address can see every user and delete them by opening a link. It comes right after R-01 because together they leave the demo fully open. [Full fix in 2-risks.md](2-risks.md)
3. **R-03 = B-01 — Weak, visible passwords that also lock people out.** Passwords can be read from the screen or easily worked out from the database, and people often reuse them elsewhere. Fixing it with one shared password method also ends the lockout after a password change. [Full fix in 2-risks.md](2-risks.md) and [1-bugs.md](1-bugs.md)

## 6. Double-check of serious issues
We read the code behind every 🔴 and 🟠 issue. All are really there. These decisions changed:

| Issue | First rated | Our decision | Why (plain words) |
|---|---|---|---|
| C-01 (= R-08) | 🟠 Fix soon | 🟡 Plan to fix, merged with R-08 | The problem is real, but no demo page actually checks the login before showing or changing data (see R-02). Taking over a session today only shows a "Welcome" line. Once R-02 is fixed, this fix must be done with it. |
| R-06 (= C-02) | 🟠 Fix soon | 🟡 Plan to fix, merged with C-02 | The check really fails on every change (the test script is missing). But the project says it is a historical archive, so little new code is expected. The quickest safe step may be to switch the check off. |
| B-01 | 🟠 Fix soon | Merged into R-03 (🔴 Fix now) | Same place and same fix: the edit page saves the password differently from the add and login pages. |
| B-02 | 🟠 Fix soon | Merged with R-09 (stays 🟠) | Both describe the same missing database error checks. |
| R-11 | 🟡 Plan to fix | Merged with B-12 and B-13 | Same generated folders and second engine copy. |

All other serious issues (R-01, R-02, R-03, R-04, R-05) were confirmed at their level.

## 7. What we could not check
- Nothing was run: no PHP, no database, no tests. All findings come from reading the code.
- The main engine (`libs/`, about 400 files, including the compiler and parser) was only sampled, not read line by line.
- The math feature runs built-up code (`libs/plugins/function.math.php`, line 98). It is only risky if untrusted people can write templates. Its filters need checking.
- The database layout (the `user` table) and the MySQL version are not in the project.
- How the demo would be hosted (web server settings, HTTPS, which folders are public) is unknown.
- Server settings that change behaviour (output buffering, error display, PHP version) could not be seen.
- The test script and test files used by the automatic check are missing. There is no lock file, so exact tool versions are unknown.
- There is no other branch, so the PR review could not check for conflicts.

## 8. Words used in this review
- **Template engine** — a tool that fills page layouts with data to make web pages. Smarty is one.
- **PHP** — a common programming language for websites.
- **MySQL** — a common database (data store). **PDO** and **mysqli** are PHP's ways to talk to it.
- **SQL injection** — a trick where an attacker types database commands into a form.
- **Prepared statement** — a safe way to send user input to the database separately from the command.
- **MD5** — an old, fast way to scramble passwords that is easy to reverse for common passwords.
- **password_hash / password_verify** — PHP's built-in safe way to store and check passwords.
- **XSS (cross-site scripting)** — harmful script typed by one user that runs in other users' browsers.
- **Escaping** — making text harmless so the browser shows it instead of running it.
- **CSRF (cross-site request forgery)** — a bad website makes a logged-in person's browser do an action without them knowing.
- **Session / session ID** — the server's memory of who you are, and the hidden number in a cookie that links you to it.
- **Session fixation** — an attacker plants a known session number before the victim logs in, then uses it.
- **Redirect** — the page tells the browser to go to another page.
- **Output buffering** — a server setting that holds back page text until the end.
- **Cache** — a stored copy of a finished page so it can be shown faster next time.
- **Compiled templates** — layouts the engine has turned into ready-to-run PHP code.
- **Root user** — the database owner account that may do everything.
- **Environment variables** — settings given by the server, kept outside the code.
- **Debug window** — a pop-up that shows all data given to a page. Meant only for developers.
- **Automatic check (CI)** — tests that run by themselves on every change to the code.
- **PHPUnit** — a tool for writing and running automatic tests in PHP.
- **Composer / lock file** — PHP's package manager, and the file that records exact library versions.
- **Release candidate** — a test version published just before the final release.
- **Commit** — saving a change in the project history.
- **PR (pull request)** — a proposed set of changes, reviewed before it is accepted.
