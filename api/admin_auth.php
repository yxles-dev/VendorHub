<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const VENDORHUB_ADMIN_USERNAME = 'barangay';
const VENDORHUB_ADMIN_PASSWORD = 'admin';

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
    header('Location: ../index.html?auth=denied');
    http_response_code(401);
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

    return true;
}

function admin_attempt_login(string $username, string $password): bool {
    if (!hash_equals(VENDORHUB_ADMIN_USERNAME, $username) || !hash_equals(VENDORHUB_ADMIN_PASSWORD, $password)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['vendorhub_admin_logged_in'] = true;
    $_SESSION['vendorhub_admin_username'] = VENDORHUB_ADMIN_USERNAME;

    return true;
}

function admin_logout(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookieParams = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
    }

    session_destroy();
}

function admin_require_login(): void {
    if (admin_is_logged_in()) {
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