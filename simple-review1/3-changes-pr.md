# Latest changes (PR review) — smarty-php-template-engine-archive
Date: 2026-11-20 · Part of the code review · Start with the [reviewed outcomes](REVIEWED-OUTCOMES.md)

**What we reviewed:** Mode B — the recent saved changes on `main`. There is no separate work branch, so there is nothing to compare against. The full history has only 3 saved changes. The first one adds the whole project (446 files), so it is covered by the other reviews. That leaves 2 saved changes, 1 file, +10 / −0 lines (spacing-only changes not counted). Unsaved changes: 1 file (`demo/login.php`, +2 lines). That unsaved change is the only change to the program itself.

### In short
The saved changes only add a note to the project description, saying this is a historical archive. The unsaved change fixes the demo login page. Before, a successful login was never remembered, so it did nothing. Now it is remembered. Both changes are small and fine to accept. One weakness comes with the new working login (C-01). It is a small fix.

**Verdict:** ⚠️ Accept after fixing C-01 (a small change to the login page). The unsaved change also still needs to be saved (committed) before it can be shared.

### What changed
**1. Demo login now actually keeps you logged in** — The login page now starts a "session" (the server's memory of who you are between pages) before it saves the user name. Before, it saved the name without starting a session, so the name was lost and the next page did not know you. *Where:* the demo login page (`demo/login.php`, line 2). *Risk:* medium (C-01).

**2. Login page stops cleanly after sending you on** — After a correct login, the page now stops at once after sending the browser to the welcome page. Before, it kept running and also showed the login form. *Where:* the demo login page (`demo/login.php`, line 17). *Risk:* low.

**3. Project description marked as a historical archive** — A note at the top of the project description says the code is the Smarty template engine, kept for reference. It also links to the owner's profile. *Where:* the project description (`README.md`, lines 1–9). *Risk:* none (text only).

### Files changed
| File | + / − | What changed, in plain words |
|---|---|---|
| `README.md` | +10 / −0 | Added a "historical archive" note at the top |
| `demo/login.php` (unsaved) | +2 / −0 | Login now starts a session and stops after sending you on |

### Problems found in these changes

### C-01 — A logged-in visitor keeps the same session ID they had before login
**How serious:** 🟠 Fix soon  ·  **Type:** Security  ·  **How sure:** Likely
**Where:** the step that runs after a correct password on the demo login page (`demo/login.php`, lines 2 and 15)
**What is wrong:** Logins now work because a session is started. But the session ID (the hidden number in a cookie that tells the server who you are) is not changed when someone logs in. The number from before login stays valid after login.
**When it happens:** Someone sets a known session number in a victim's browser, for example with a crafted link on a shared computer. The victim logs in. The other person can then use the same number and is treated as logged in. This is called "session fixation".
**What could happen:** Someone else could act as the logged-in user on the demo pages that check the session (`demo/view.php`).
**How to fix it:**
1. On the login page, right after the password is accepted and before saving the user name, call `session_regenerate_id(true)` (gives the visitor a fresh session number and throws away the old one).
**How to check the fix worked:** Note the session cookie value in the browser before login. Log in. The value should now be different, and the old value should no longer work.
**Size of the job:** small (under a day)

### C-02 — No automatic tests check the login change
**How serious:** 🟡 Plan to fix  ·  **Type:** Testing  ·  **How sure:** Certain
**Where:** the whole change. The project has no tests for the demo pages (`demo/` folder).
**What is wrong:** The login fix can only be checked by hand. Nothing would warn the team if it broke again later.
**When it happens:** Someone later edits the login page or the welcome page and removes the session start again.
**What could happen:** Login would quietly stop working, as it did before this change.
**How to fix it:**
1. Write down the hand test below as a short checklist next to the demo, or add a simple automatic test that logs in and opens the welcome page.
**How to check the fix worked:** The checklist or test passes with the fix and fails if `session_start()` is removed from the login page.
**Size of the job:** small (under a day)

### Conflicts
Not checked — there is no main branch to compare with (the work is directly on `main`).

### How to test it
1. Open the demo login page (`demo/login.php`) and log in with a correct name and password. You should go straight to the welcome page and see "Welcome <name>".
2. Check that the welcome page does **not** show the login form under it or before it.
3. Log in with a wrong password. You should see "username or password is incorrect" and the login form.
4. After a correct login, open the welcome page again in the same browser. It should still greet you by name.
5. Close the browser (or delete its cookies) and open the welcome page. It should not greet you.
6. After fixing C-01, follow the check in C-01: the session cookie value must change at login.
7. Open the project description (`README.md`) on the code site. The "Historical Archive" note should show at the top, and the profile link should work.

### PR description (ready to paste)
```markdown
Fix demo login session and mark repository as historical archive
- Start a session on the demo login page so a successful login is remembered
- Stop the login page right after the redirect to the welcome page
- Add a "Historical Archive" note to the README
Risks: session ID is not refreshed at login (C-01, session fixation); no automatic tests (C-02)
Test: log in with right and wrong passwords; the welcome page should greet you only after a correct login
```

### What we could not check
- We did not run the demo, so the login steps above are based on reading the code.
- We did not compare with another branch: there is none, so there was no conflict check.
- The first saved change (the whole project) is not reviewed here. Problems in the old login code, such as how the password check builds its database query, are covered in the other review files.
