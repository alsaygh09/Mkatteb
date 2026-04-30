<?php
/**
 * public/logout.php
 * Fully destroys the session and redirects to login.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

logoutUser();
flashSet('success', 'You have been logged out.');
redirect('login.php');
