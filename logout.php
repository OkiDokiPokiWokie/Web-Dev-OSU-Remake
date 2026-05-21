<?php
/**
 * Logout Handler
 * Project: osu! Web Clone
 * * This file clears the session data, destroys the session on the server,
 * and handles immediate redirection back to the root page.
 */

// 1. Access the active session
session_start();

// 2. Clear all session variables and completely destroy the session
$_SESSION = array(); // Clear the in-memory array
session_destroy();  // Destroy the session data on the server

// 3. Redirect to index.php
header("Location: /index.php");
exit();