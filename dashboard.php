<?php
// dashboard.php — auto-redirect based on role
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    flash('error', 'Please sign in to continue.');
    redirect('user/login.php');
}

if (is_admin()) {
    redirect('admin/dashboard.php');
}

if (is_user()) {
    redirect('user/dashboard.php');
}

// Fallback
redirect('index.php');