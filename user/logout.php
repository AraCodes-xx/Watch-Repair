<?php
require_once '../config/config.php';

// Destroy session
session_destroy();

// Redirect to home
redirect(SITE_URL . '/index.php');
?>
