<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const VENDORHUB_ADMIN_USERNAME = 'barangay';
const VENDORHUB_ADMIN_PASSWORD = 'admin';
const VENDORHUB_ADMIN_AUTH_COOKIE = 'vendorhub_admin_auth';
const VENDORHUB_ADMIN_AUTH_SECRET = 'VendorHub-Admin-Auth';

function admin_auth_cookie_value(): string {
    return hash_hmac('sha256', VENDORHUB_ADMIN_USERNAME . '|' . VENDORHUB_ADMIN_PASSWORD, VENDORHUB_ADMIN_AUTH_SECRET);
}

function admin_auth_cookie_options(int $expires = 0): array {
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function admin_is_logged_in(): bool {
    if (!empty($_SESSION['vendorhub_admin_logged_in'])) {
        return true;
    }

    if (empty($_COOKIE[VENDORHUB_ADMIN_AUTH_COOKIE])) {
        return false;
    }

    return hash_equals(admin_auth_cookie_value(), (string)$_COOKIE[VENDORHUB_ADMIN_AUTH_COOKIE]);
}

function admin_redirect_to_login(): void {
    header('Location: /login.html');
    http_response_code(303);
    exit;
}

function admin_attempt_login(string $username, string $password): bool {
    if (!hash_equals(VENDORHUB_ADMIN_USERNAME, $username) || !hash_equals(VENDORHUB_ADMIN_PASSWORD, $password)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['vendorhub_admin_logged_in'] = true;
    $_SESSION['vendorhub_admin_username'] = VENDORHUB_ADMIN_USERNAME;
    setcookie(
        VENDORHUB_ADMIN_AUTH_COOKIE,
        admin_auth_cookie_value(),
        admin_auth_cookie_options(time() + 60 * 60 * 24 * 30)
    );

    return true;
}

function admin_logout(): void {
    $_SESSION = [];

    setcookie(
        VENDORHUB_ADMIN_AUTH_COOKIE,
        '',
        admin_auth_cookie_options(time() - 3600)
    );

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

    admin_redirect_to_login();
}