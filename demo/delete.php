<?php
/**
 * Deletes one or more users. Only accepts a POST form with a valid form token,
 * so a link or image on another website cannot delete users.
 */
require __DIR__ . '/bootstrap.php';
$me = demo_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    demo_fail('Users can only be deleted with the Delete button on the user list.', 405);
}
demo_check_csrf();

$ids = isset($_POST['ids']) ? (array)$_POST['ids'] : array();
$ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
    return $id > 0;
})));

if (!$ids) {
    demo_flash('No users were selected.');
    demo_redirect('view.php');
}
if (in_array($me['id'], $ids, true)) {
    demo_flash('You cannot delete your own account while you are logged in.');
    demo_redirect('view.php');
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$deleted = demo_query('DELETE FROM user WHERE id IN (' . $placeholders . ')', $ids)->rowCount();

demo_flash($deleted === 1 ? '1 user was deleted.' : $deleted . ' users were deleted.');
demo_redirect('view.php');
