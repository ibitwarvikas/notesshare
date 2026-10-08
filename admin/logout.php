<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

session_destroy();
session_start();
flash('success', 'Admin signed out.');
header('Location: ' . BASE_URL . '/index.php');
exit;