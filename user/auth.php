<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (!is_logged_in()) {
    flash('error', 'Please sign in to continue.');
    redirect('login.php');
}
if (is_admin()) redirect('../admin/dashboard.php');