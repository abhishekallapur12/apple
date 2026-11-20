<?php
require __DIR__ . '/bootstrap.php';
$me = demo_require_login();

$isSave = $_SERVER['REQUEST_METHOD'] === 'POST';
$id = (int)($isSave ? (isset($_POST['id']) ? $_POST['id'] : 0) : (isset($_GET['id']) ? $_GET['id'] : 0));

$user = $id > 0 ? demo_query('SELECT id, name, age FROM user WHERE id = ?', array($id))->fetch() : false;
if (!$user) {
    demo_fail('User not found. It may have been deleted.', 404);
}

$errors = array();
$values = array('name' => $user['name'], 'age' => (string)$user['age']);

if ($isSave) {
    demo_check_csrf();
    $values['name'] = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
    $values['age'] = isset($_POST['age']) ? trim((string)$_POST['age']) : '';
    // An empty password box means "keep the current password".
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    $errors = demo_validate_user($values['name'], $values['age'], $password, false);
    if (!$errors && demo_query('SELECT COUNT(*) FROM user WHERE name = ? AND id <> ?', array($values['name'], $id))->fetchColumn() > 0) {
        $errors[] = 'Another user already has this name.';
    }

    if (!$errors) {
        if ($password !== '') {
            $changed = demo_query(
                'UPDATE user SET name = ?, age = ?, password = ? WHERE id = ?',
                array($values['name'], (int)$values['age'], demo_hash_password($password), $id)
            )->rowCount();
        } else {
            $changed = demo_query(
                'UPDATE user SET name = ?, age = ? WHERE id = ?',
                array($values['name'], (int)$values['age'], $id)
            )->rowCount();
        }
        if ($id === $me['id']) {
            $_SESSION['uname'] = $values['name'];
        }
        demo_flash($changed > 0 ? 'The changes were saved.' : 'Nothing was changed.');
        demo_redirect('view.php');
    }
}

$smarty = demo_smarty();
$smarty->assign('user_id', $id);
$smarty->assign('errors', $errors);
$smarty->assign('values', $values);
$smarty->display('edit.tpl');
