<?php
require __DIR__ . '/bootstrap.php';

// Only logged-in people may add users. The one exception is the very first
// user, so a new install can be set up without touching the database by hand.
$firstUser = (int)demo_query('SELECT COUNT(*) FROM user')->fetchColumn() === 0;
if (!$firstUser) {
    demo_require_login();
}

$errors = array();
$values = array('name' => '', 'age' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    demo_check_csrf();
    $values['name'] = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $values['age'] = isset($_POST['age']) ? trim((string)$_POST['age']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    $errors = demo_validate_user($values['name'], $values['age'], $password, true);
    if (!$errors && demo_query('SELECT COUNT(*) FROM user WHERE name = ?', array($values['name']))->fetchColumn() > 0) {
        $errors[] = 'A user with this name already exists.';
    }

    if (!$errors) {
        demo_query(
            'INSERT INTO user (name, age, password) VALUES (?, ?, ?)',
            array($values['name'], (int)$values['age'], demo_hash_password($password))
        );
        if (demo_current_user() === null) {
            demo_flash('The first user was created. Please log in.');
            demo_redirect('login.php');
        }
        demo_flash('User "' . $values['name'] . '" was added.');
        demo_redirect('view.php');
    }
}

$smarty = demo_smarty();
$smarty->assign('errors', $errors);
$smarty->assign('values', $values);
$smarty->assign('first_user', $firstUser);
$smarty->display('insert.tpl');
