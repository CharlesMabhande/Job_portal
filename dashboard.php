<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$roleId = (int)($_SESSION['role_id'] ?? 0);

switch ($roleId) {
    case 1:
        redirect('/candidate/dashboard.php');
    case 2:
        redirect('/hr/dashboard.php');
    case 3:
        redirect('/management/dashboard.php');
    case 4:
        redirect('/admin/dashboard.php');
    default:
        redirect('/index.php');
}

