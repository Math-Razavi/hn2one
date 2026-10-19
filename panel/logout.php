<?php
// logout.php — خروج از پنل
require_once __DIR__ . '/../files/api/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
header('Location: login.php');
exit;
