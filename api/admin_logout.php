<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'admin_auth.php';

admin_logout();

header('Location: ../index.html?logout=1');
http_response_code(303);
exit;
