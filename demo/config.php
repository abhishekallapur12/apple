<?php
/**
 * Demo settings.
 *
 * Database login details are NOT written here. They are read from server
 * settings (environment variables) so they never end up in version control.
 * Use a limited database user that may only read and change the `user` table,
 * never "root".
 *
 *   DEMO_DB_HOST      (default: localhost)
 *   DEMO_DB_NAME      (default: staah)
 *   DEMO_DB_USER      (required)
 *   DEMO_DB_PASSWORD  (required)
 *   DEMO_DEBUG        set to 1 only on a developer's own computer
 */
return array(
    'db_host'     => getenv('DEMO_DB_HOST') ?: 'localhost',
    'db_name'     => getenv('DEMO_DB_NAME') ?: 'staah',
    'db_user'     => getenv('DEMO_DB_USER') ?: '',
    'db_password' => getenv('DEMO_DB_PASSWORD') ?: '',
    'debug'       => getenv('DEMO_DEBUG') === '1',
    'page_size'   => 50,
    'max_login_failures' => 5,
    'login_lock_seconds' => 900,
);
