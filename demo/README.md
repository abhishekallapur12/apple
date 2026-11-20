# Demo user-management site

A small example site built with the Smarty engine in `../libs`. People log in,
then list, add, edit and delete users.

## Setting it up

1. **Create a limited database user** (never use `root`):

   ```sql
   CREATE USER 'smarty_demo'@'localhost' IDENTIFIED BY '<a strong password>';
   GRANT SELECT, INSERT, UPDATE, DELETE ON staah.user TO 'smarty_demo'@'localhost';
   ```

2. **Make the password column wide enough** for safe password hashes
   (`password_hash` produces about 60 characters; old MD5 values were 32):

   ```sql
   ALTER TABLE user MODIFY password VARCHAR(255) NOT NULL;
   ```

3. **Give the site its database login through server settings** (environment
   variables), not through the code:

   | Setting | Meaning | Default |
   |---|---|---|
   | `DEMO_DB_HOST` | Database server | `localhost` |
   | `DEMO_DB_NAME` | Database name | `staah` |
   | `DEMO_DB_USER` | Database user | *(required)* |
   | `DEMO_DB_PASSWORD` | Database password | *(required)* |
   | `DEMO_DEBUG` | `1` shows error details and the Smarty debug window. Developers only. | off |

   For example in Apache: `SetEnv DEMO_DB_USER smarty_demo`.

4. Make sure the web server can write to `templates_c/` and `cache/`, and that
   those folders are not reachable from the web (an `.htaccess` file that blocks
   them is included for Apache).

5. Open `insert.php` to create the first user, then log in at `login.php`.
   After the first user exists, only logged-in people can add, edit or delete users.

## Existing users

- Users whose password was stored with MD5 can still log in. Their password
  is upgraded to the safe method automatically at their next login.
- Users whose password was saved as plain text by the old edit page cannot log
  in. A logged-in person should set a new password for them on the edit page.

## Example plugins

The MySQL plugins in `plugins/` read their database login from
`SMARTY_DB_DSN`, `SMARTY_DB_USER` and `SMARTY_DB_PASSWORD`. If you already use
the `output_cache` table, add the new expiry column first; the SQL is in
`plugins/cacheresource.mysql.php`.
