<?php
/**
 * logout.php — keluar dari sesi admin
 */
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
