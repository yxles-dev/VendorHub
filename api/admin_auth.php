<?php

// Configure session settings for persistence
ini_set('session.gc_maxlifetime', 2592000); // 30 days in seconds
ini_set('session.cookie_lifetime', 2592000); // 30 days in seconds

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const VENDORHUB_ADMIN_USERNAME = 'barangay';
const VENDORHUB_ADMIN_PASSWORD = 'admin';
const VENDORHUB_REMEMBER_TOKEN_COOKIE = 'vendorhub_remember_token';

function admin_is_logged_in(): bool {
    return !empty($_SESSION['vendorhub_admin_logged_in']);
}

function admin_basic_auth_user(): string {
    if (!empty($_SERVER['PHP_AUTH_USER'])) {
        return (string)$_SERVER['PHP_AUTH_USER'];
    }

    if (!empty($_SERVER['REMOTE_USER'])) {
        return (string)$_SERVER['REMOTE_USER'];
    }

    return '';
}

function admin_basic_auth_password(): string {
    return !empty($_SERVER['PHP_AUTH_PW']) ? (string)$_SERVER['PHP_AUTH_PW'] : '';
}

function admin_send_auth_challenge(): void {
    header('WWW-Authenticate: Basic realm="VendorHub Admin", charset="UTF-8"');
    http_response_code(401);
    header('Location: ../index.html?auth=denied');
    exit;
}

function admin_redirect_home_with_warning(): void {
    header('Location: ../index.html?auth=denied');
    http_response_code(303);
    exit;
}

function admin_try_basic_auth(): bool {
    $username = admin_basic_auth_user();
    $password = admin_basic_auth_password();

    if ($username === '' && $password === '') {
        return false;
    }

    if (!hash_equals(VENDORHUB_ADMIN_USERNAME, $username) || !hash_equals(VENDORHUB_ADMIN_PASSWORD, $password)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['vendorhub_admin_logged_in'] = true;
    $_SESSION['vendorhub_admin_username'] = VENDORHUB_ADMIN_USERNAME;
    $_SESSION['vendorhub_admin_login_time'] = time();

    return true;
}

function admin_set_remember_cookie(): void {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + 2592000; // 30 days
    setcookie(VENDORHUB_REMEMBER_TOKEN_COOKIE, $token, [
        'expires' => $expiry,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

function admin_check_remember_cookie(): bool {
    if (empty($_COOKIE[VENDORHUB_REMEMBER_TOKEN_COOKIE])) {
        return false;
    }

    // If we have a remember cookie, restore the session
    $_SESSION['vendorhub_admin_logged_in'] = true;
    $_SESSION['vendorhub_admin_username'] = VENDORHUB_ADMIN_USERNAME;
    $_SESSION['vendorhub_admin_login_time'] = time();

    return true;
}

function admin_attempt_login(string $username, string $password, bool $remember = false): bool {
    if (!hash_equals(VENDORHUB_ADMIN_USERNAME, $username) || !hash_equals(VENDORHUB_ADMIN_PASSWORD, $password)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['vendorhub_admin_logged_in'] = true;
    $_SESSION['vendorhub_admin_username'] = VENDORHUB_ADMIN_USERNAME;
    $_SESSION['vendorhub_admin_login_time'] = time();

    if ($remember) {
        admin_set_remember_cookie();
    }

    return true;
}

function admin_logout(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookieParams = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
    }

    // Clear remember cookie
    setcookie(VENDORHUB_REMEMBER_TOKEN_COOKIE, '', time() - 3600, '/', true, false, 'Strict');

    session_destroy();
}

function admin_require_login(): void {
    if (admin_is_logged_in()) {
        return;
    }

    if (admin_check_remember_cookie()) {
        return;
    }

    if (admin_try_basic_auth()) {
        return;
    }

    if (admin_basic_auth_user() !== '' || admin_basic_auth_password() !== '') {
        admin_redirect_home_with_warning();
    }

    admin_send_auth_challenge();
}