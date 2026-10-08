<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

session_destroy();
session_start();
flash('success', 'You have been signed out.');
header('Location: ' . BASE_URL . '/index.php');
exit;