<?php
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    demo_check_csrf();
    demo_log_out();
}
demo_redirect('login.php');
