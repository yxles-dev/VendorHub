<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.html');
    exit;
}

$username = isset($_POST['username']) ? (string)$_POST['username'] : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';

if (admin_attempt_login($username, $password)) {
    // Successful login - redirect to admin files
    header('Location: admin_files.php');
    http_response_code(303);
    exit;
}

// Failed login - redirect back to login with error
header('Location: ../login.html?error=1');
http_response_code(303);
exit;
