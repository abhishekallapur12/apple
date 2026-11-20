<?php
require __DIR__ . '/bootstrap.php';

if (demo_current_user() !== null) {
    demo_redirect('view.php');
}

$error = null;
$name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    demo_check_csrf();
    $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if (demo_login_blocked($name)) {
        $error = 'Too many failed login attempts. Please wait '
            . ceil(demo_config('login_lock_seconds') / 60) . ' minutes and try again.';
    } else {
        $found = null;
        $needsRehash = false;
        $rows = demo_query('SELECT id, name, password FROM user WHERE name = ?', array($name))->fetchAll();
        foreach ($rows as $row) {
            if (demo_check_password($password, $row['password'], $needsRehash)) {
                $found = $row;
                break;
            }
        }
        if (!$rows) {
            // Take the same time as a real check, so nobody can tell which names exist.
            password_verify($password, '$2y$10$abcdefghijklmnopqrstuuVv6W4Cn3qXy0Z9V7sOjS1g7S2l5mQ8e');
        }

        if ($found !== null) {
            if ($needsRehash) {
                // Upgrade an old MD5 password to the safe method.
                try {
                    demo_query('UPDATE user SET password = ? WHERE id = ?', array(demo_hash_password($password), (int)$found['id']));
                } catch (PDOException $e) {
                    error_log('[smarty-demo] Could not upgrade password for user ' . (int)$found['id'] . ': ' . $e->getMessage());
                }
            }
            demo_clear_login_failures($name);
            demo_log_in($found['id'], $found['name']);
            demo_redirect('view.php');
        }

        demo_record_login_failure($name);
        $error = 'Username or password is incorrect.';
    }
}

$smarty = demo_smarty();
$smarty->assign('error', $error);
$smarty->assign('name', $name);
$smarty->display('login.tpl');
