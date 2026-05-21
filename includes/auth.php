<?php
/**
 * Session and Authentication Helper Functions
 * Project: osu! Web Clone
 */

// Always call session_start() when included as per the PRD rules
session_start();

/**
 * Checks if the current user is logged in.
 * Returns true if $_SESSION['username'] is set and not empty, otherwise false.
 *
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['username']) && !empty($_SESSION['username']);
}

/**
 * Checks if the current logged-in user is an admin.
 * Returns true if $_SESSION['role'] is set and equals "admin", otherwise false.
 *
 * @return bool
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Protects a page by requiring the user to be logged in.
 * If not logged in, immediately redirects to index.php and stops execution.
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: /index.php");
        exit();
    }
}

/**
 * Protects an admin page by requiring the user to be an admin.
 * If not an admin, immediately redirects to home.php and stops execution.
 * NOTE: require_login() should usually be called right before this.
 */
function require_admin() {
    if (!is_admin()) {
        header("Location: /home.php");
        exit();
    }
}

/**
 * Retrieves the currently logged-in user's username.
 * Returns the string value of $_SESSION['username'].
 *
 * @return string
 */
function current_user() {
    return isset($_SESSION['username']) ? (string)$_SESSION['username'] : '';
}