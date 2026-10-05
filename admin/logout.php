<?php
require_once '../config/config.php';

// Destroy session
session_destroy();

// Redirect to admin login
redirect(SITE_URL . '/admin/login.php');
?>
