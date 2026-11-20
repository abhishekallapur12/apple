# Bugs and errors — smarty-php-template-engine-archive
Date: 2026-11-20 · Part of the code review · Start with the [reviewed outcomes](REVIEWED-OUTCOMES.md)

We found 8 bugs. Almost all are in the small "user list" demo website in the `demo` folder. The most important one: changing a password on the edit page saves it in a different form than the login page expects, so that person can no longer sign in.

| ID | Bug (plain words) | How serious | Where |
|---|---|---|---|
| B-01 | After a password is changed on the edit page, that person can no longer sign in | 🟠 Fix soon | the edit page (`demo/edit.php`) |
| B-02 | Database problems are never checked, so pages crash or claim success when nothing was saved | 🟠 Fix soon | all demo pages (`demo/config.php`, `demo/view.php`, `demo/insert.php`, `demo/edit.php`) |
| B-03 | Deleting a user can still show that user in the list, and the page keeps the delete link in the address bar | 🟡 Plan to fix | the list page (`demo/view.php`) |
| B-04 | Pages keep running after they send the visitor somewhere else | 🟡 Plan to fix | add, edit and list pages (`demo/insert.php`, `demo/edit.php`, `demo/view.php`) |
| B-05 | Saving the edit form, or opening it with no or unknown user, gives warnings and empty forms | 🟡 Plan to fix | the edit page (`demo/edit.php`, `demo/templates/edit.tpl`) |
| B-06 | An empty user list causes a warning on the list page | 🟡 Plan to fix | the list page (`demo/view.php`) |
| B-07 | The new-style user table shows the wrong column titles, and its buttons do nothing | 🟡 Plan to fix | the list page layout (`demo/templates/view.tpl`) |
| B-08 | The "clear expired cache" action in the example MySQL cache plugin deletes the whole cache | 🟡 Plan to fix | example database cache plugin (`demo/plugins/cacheresource.mysql.php`) |

### B-01 — After a password is changed on the edit page, that person can no longer sign in
**How serious:** 🟠 Fix soon  ·  **Type:** Bug (mismatch)  ·  **How sure:** Certain
**Where:** The step that saves the edit form (`demo/edit.php`, line 12). Compare with the add page (`demo/insert.php`, line 11) and the sign-in page (`demo/login.php`, line 10).
**What is wrong:** The add page and the sign-in page both pass the password through `md5` (a function that turns a password into a fixed code before it is stored or compared). The edit page saves the typed password as plain text, without `md5`. The edit form is also filled with the stored code, not the real password.
**When it happens:** An admin opens "Update" for user Anna and types a new password "summer". The database now holds "summer". When Anna signs in with "summer", the sign-in page compares the code of "summer" with "summer". They never match.
**What could happen:** Anyone whose password was edited is locked out. If the admin edits only the name, the form re-saves the old code as it is, so that case still works. This makes the bug look random to users.
**How to fix it:**
1. In the edit page, store the password the same way as the add page does (pass it through the same function before saving).
2. Do not put the stored password code into the edit form. Leave the password box empty and only change the password when the box is filled in.
3. Better still, use one shared password function for add, edit and sign-in, so the three places cannot drift apart again (for example PHP's `password_hash` and `password_verify`, which are built for this).
**How to check the fix worked:** Add a user, edit their password, then sign in with the new password. It must work. Edit only the name, then sign in with the old password. It must still work.
**Size of the job:** small (under a day)

### B-02 — Database problems are never checked, so pages crash or claim success when nothing was saved
**How serious:** 🟠 Fix soon  ·  **Type:** Error handling  ·  **How sure:** Certain
**Where:** The database connection (`demo/config.php`, line 7); reading the list (`demo/view.php`, lines 14–18); adding a user (`demo/insert.php`, lines 11–13); saving an edit (`demo/edit.php`, lines 12–13); deleting (`demo/view.php`, line 21).
**What is wrong:** No page checks whether the database connection or a database command worked. The result is used as if it always succeeded.
**When it happens:** The database is down, the `staah` database or `user` table is missing, or the add page gets text such as "ten" in the Age box while the database only accepts numbers. Depending on the PHP version, the page either stops with a technical error page, or the command quietly fails and the visitor is still sent to the list page.
**What could happen:** Visitors see a broken page with internal details. Or a person thinks a user was added or changed when nothing was saved. Staff then lose time looking for records that were never stored.
**How to fix it:**
1. Right after connecting in `config.php`, check whether the connection worked. If not, show a short friendly message ("The service is not available, please try again later"), write the real error to the server log, and stop.
2. After each add, edit and delete command, check the result. Only send the visitor back to the list when it worked. Otherwise show a clear message on the same form.
3. Check the form values before saving (for example, Age must be a whole number, Name must not be empty) and show a message if they are wrong.
**How to check the fix worked:** Stop the database and open the list page: a friendly message must appear. Type "ten" as Age on the add page: you must see a message and stay on the form.
**Size of the job:** small (under a day)

### B-03 — Deleting a user can still show that user in the list, and the delete link stays in the address bar
**How serious:** 🟡 Plan to fix  ·  **Type:** Bug (wrong order)  ·  **How sure:** Likely (depends on a server setting)
**Where:** The list page (`demo/view.php`): the list is read at lines 14–18, the delete runs later at line 21, and "Welcome …" is printed earlier at line 11.
**What is wrong:** The page reads the list of users first and deletes afterwards. It then tries to send the visitor back to a clean list page. But when someone is signed in, the page has already printed "Welcome …". On a server without output buffering (holding back page text until the end), PHP can no longer redirect at that point. The page then shows the old list.
**When it happens:** A signed-in person clicks "Delete" next to Bob. On a server where output buffering is off (PHP's built-in default), they see a warning "headers already sent", and Bob is still in the list, even though he was deleted.
**What could happen:** People think the delete did not work and click again, or report a bug. The address bar keeps `view.php?del=…`, so a refresh or bookmark sends the delete again.
**How to fix it:**
1. Move the delete step to the very top of the page, before anything is printed and before the list is read.
2. After the delete, redirect to the plain list page and stop the page right away (see B-04).
3. Print the "Welcome" text through the page template instead of with `echo` (printing text straight to the page) at the start of the script.
**How to check the fix worked:** Turn output buffering off, sign in, delete a user. The page must reload to the clean list address, and the user must be gone.
**Size of the job:** small (under a day)

### B-04 — Pages keep running after they send the visitor somewhere else
**How serious:** 🟡 Plan to fix  ·  **Type:** Bug  ·  **How sure:** Certain
**Where:** The add page (`demo/insert.php`, lines 12–16), the edit page (`demo/edit.php`, lines 13–17) and the list page (`demo/view.php`, lines 22–26).
**What is wrong:** After `header("location:view.php")` (the line that tells the browser to go to the list page), the code does not stop. It goes on to print "Record added successfully" and builds the whole form page as well. The sign-in page already stops correctly (`demo/login.php`, line 17); the other pages do not.
**When it happens:** Every time a user is added, edited or deleted.
**What could happen:** The message "Record added successfully" is never seen by anyone, because the browser has already left. The server does extra work for nothing. If a redirect fails (see B-03), the visitor gets a confusing mix of page and message.
**How to fix it:**
1. Put `exit;` (stop the page) right after every redirect line in the add, edit and list pages.
2. If a success message is wanted, keep it in the session (the visitor's short-term memory on the server) and show it on the list page.
**How to check the fix worked:** Add a user. The browser must go straight to the list. If you add a success message, it must appear there once.
**Size of the job:** small (under a day)

### B-05 — Saving the edit form, or opening it with no or unknown user, gives warnings and empty forms
**How serious:** 🟡 Plan to fix  ·  **Type:** Crash / unchecked input  ·  **How sure:** Certain
**Where:** The edit page (`demo/edit.php`, lines 7–8) and its form (`demo/templates/edit.tpl`, line 3).
**What is wrong:** The page always reads the user id from the address bar (`$_GET['id']`). But the form sends to `edit.php` without the id in the address. So when the form is saved, the id is missing. The page also never checks whether a user with that id exists.
**When it happens:** (a) Someone presses "Edit Record": PHP shows "Undefined array key id", runs a useless lookup, and the warning can block the redirect (same effect as in B-03). (b) Someone opens `edit.php` with no id or with a deleted user's id: an empty form appears. Saving it changes nothing and gives no message.
**What could happen:** Warnings on screen, failed redirects, and people thinking they saved a change when nothing happened.
**How to fix it:**
1. Only read the user from the address when the form is not being saved.
2. If no id is given, or no user is found, show "User not found" with a link back to the list, and stop.
3. When saving, check that the update really changed a row; if not, show a message.
**How to check the fix worked:** Open `edit.php` with no id, and with an id that does not exist: a clear message must show. Save a normal edit with all warnings switched on: no warning, straight back to the list.
**Size of the job:** small (under a day)

### B-06 — An empty user list causes a warning on the list page
**How serious:** 🟡 Plan to fix  ·  **Type:** Crash  ·  **How sure:** Certain
**Where:** The list page (`demo/view.php`, lines 14–18 and 25).
**What is wrong:** The list variable `$data` is only created inside the loop that reads users. If there are no users, it never exists, but it is still passed to the template.
**When it happens:** On a new install, or after the last user is deleted.
**What could happen:** PHP shows "Undefined variable $data" on the page. That warning can also block the redirect after deleting the last user (see B-03).
**How to fix it:**
1. Start with an empty list (`$data = array();`) before the loop.
2. Optionally show "No users yet" in the template when the list is empty.
**How to check the fix worked:** Empty the user table and open the list page: no warning, and a clean empty table.
**Size of the job:** small (under a day)

### B-07 — The new-style user table shows the wrong column titles, and its buttons do nothing
**How serious:** 🟡 Plan to fix  ·  **Type:** Bug (mismatch)  ·  **How sure:** Certain
**Where:** The list page layout (`demo/templates/view.tpl`): column titles at lines 295–298, row values at lines 311–314, buttons at lines 281–282, tick boxes at line 307.
**What is wrong:**
- The column titles say "Name, Email, Address, Phone", but the cells show id, name, age and password. So "Name" shows the id, "Email" shows the name, and so on.
- "Add New Employee" and "Delete" open pop-up windows (`#addEmployeeModal`, `#deleteEmployeeModal`) that do not exist in the page, so nothing happens.
- Every row's tick box has the same id `checkbox1` and the same value `1`, so even a working "Delete selected" could not tell rows apart.
**When it happens:** Every time the list page is opened.
**What could happen:** People read the wrong data under the wrong heading, and press buttons that silently do nothing.
**How to fix it:**
1. Change the column titles to match the data (ID, Name, Age), or change the cells to match the titles.
2. Either add the two pop-up windows, or point the buttons to the add page and remove the bulk "Delete" button.
3. Give each tick box the user's own id as its id and value.
**How to check the fix worked:** Open the list page: every heading matches its column, and every button does something.
**Size of the job:** small (under a day)

### B-08 — The "clear expired cache" action in the example MySQL cache plugin deletes the whole cache
**How serious:** 🟡 Plan to fix  ·  **Type:** Bug (wrong logic)  ·  **How sure:** Certain
**Where:** The delete step of the example database cache plugin (`demo/plugins/cacheresource.mysql.php`, lines 166–169). The special value is defined in the main library (`libs/Smarty.class.php`, line 120).
**What is wrong:** Smarty uses the special value `-1` (`CLEAR_EXPIRED`, meaning "remove only cached pages that have run out of time"). This plugin does not know that value. It treats -1 as a number of seconds and removes everything older than "now plus one second". That is every saved page. The table also has no expiry column, so it cannot tell which pages are expired. The sister plugin (`demo/plugins/cacheresource.pdo.php`, lines 325–330) handles the value correctly.
**When it happens:** A site that copied this plugin runs a nightly job "clear expired cache pages".
**What could happen:** The whole page cache is wiped every time. The site slows down until every page has been rebuilt, which can overload the database at busy times.
**How to fix it:**
1. In the delete step, check for the special value `Smarty::CLEAR_EXPIRED` first, as the PDO plugin does.
2. Add an expiry column to the example table and fill it when saving, so expired pages can be found.
**How to check the fix worked:** Save two cached pages, one short-lived and one long-lived. Wait until the short one expires and run "clear expired". Only the short one must be gone.
**Size of the job:** small (under a day)

### Small tidy-ups
- **B-09 — Pages are built with two page headers** — the add and list layouts (`demo/templates/insert.tpl`, lines 2–7; `demo/templates/view.tpl`, lines 2–6): the shared header already starts the page, then a second `<!DOCTYPE html>`/`<html>` follows, so browsers switch to an old display mode. Remove the duplicate start and end, or stop including the shared header.
- **B-10 — Page title shows "foo -"** — all demo templates (`demo/templates/*.tpl`, line 2, with `demo/templates/header.tpl`, line 3): `title=foo` passes the word "foo", and `$Name` is not set on these pages. Pass a real title.
- **B-11 — The add page shows the same form twice** — `demo/templates/insert.tpl`, lines 277–294 and 301–323: two forms with the same field ids. Keep one.
- **B-12 — Two copies of the template library** — `libs/` and `demo/libs/`: `demo/index.php` uses one, the other demo pages use the other. A fix in one copy is missed in the other. Point all pages at one copy.
- **B-13 — Old generated files are stored with the code** — `demo/templates_c/` and `demo/cache/`: these are rebuilt automatically and can be out of date. Remove them from the project and ignore those folders.
- **B-14 — Date picker stops at 2010** — the demo home page layout (`demo/templates/index.tpl`, line 70): `end_year=2010`. Use a range relative to today (for example `end_year="+1"`).

### What we checked
- Signing in → seeing the welcome text and user list (`demo/login.php`, `demo/view.php`).
- Adding a user → back to the list (`demo/insert.php`).
- Editing a user → saving → back to the list (`demo/edit.php`).
- Deleting a user from the list (`demo/view.php`).
- The demo home page and all demo templates and the settings file (`demo/index.php`, `demo/templates/`, `demo/configs/test.conf`).
- The example plugins for storing templates and cached pages in MySQL, PDO (a common PHP way to talk to databases), Memcache and APC (`demo/plugins/`).
- A sweep of the main template library (`libs/`) for TODO/FIXME notes and empty error handlers, plus a closer read of the text-shortening modifier and the custom cache base code. We confirmed no bug there.

### What we could not check
- The main template library (`libs/`, about 400 files) is far too large to read line by line in this review. We only sampled it.
- We did not run anything (no PHP, no database), so behaviour that depends on server settings (output buffering, error display, PHP version) is described from the code.
- The database layout for the `user` table is not in the project, so we could not confirm column types (for example whether Age must be a number).
