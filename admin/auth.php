<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (!is_logged_in()) {
    flash('error', 'Admin access required.');
    redirect('login.php');
}
if (!is_admin()) {
    flash('error', 'You do not have admin access.');
    redirect('../user/dashboard.php');
}