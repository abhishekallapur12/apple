<?php
require __DIR__ . '/bootstrap.php';
demo_require_login();

$pageSize = demo_config('page_size');
$total = (int)demo_query('SELECT COUNT(*) FROM user')->fetchColumn();
$pages = max(1, (int)ceil($total / $pageSize));
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = min(max(1, $page), $pages);

$users = demo_query(
    'SELECT id, name, age FROM user ORDER BY id LIMIT ? OFFSET ?',
    array($pageSize, ($page - 1) * $pageSize)
)->fetchAll();

$smarty = demo_smarty();
$smarty->assign('users', $users);
$smarty->assign('shown', count($users));
$smarty->assign('page', $page);
$smarty->assign('pages', $pages);
$smarty->assign('total', $total);
$smarty->display('view.tpl');
