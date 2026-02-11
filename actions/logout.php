<?php
/**
 * PRO PLUS LOGOUT ENGINE
 * Features: Session Invalidation, Cookie Hijacking Defense, and Cache Clearing
 */

session_start();

// 1. Clear Session Data in Memory
$_SESSION = array();

// 2. Comprehensive Cookie Destruction
// We fetch current settings to ensure we wipe the cookie from the exact path/domain it was set on
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 3600, // Expire 1 hour ago
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Server-Side Destruction
// Removes the session file from the server's temporary storage
session_destroy();

/**
 * PRO PLUS SECURITY: Cache Control
 * These headers prevent the browser from storing a "snapshot" of the
 * private dashboard. Without this, a user could click "Back" after 
 * logging out and see the previous user's data.
 */
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// 4. Secure Redirect
// We use urlencode for the message and ensure no script injection occurs
$message = urlencode("You have been securely logged out.");

// 4. Secure Redirect
// Reverting to standard relative path which is safer for most subdirectory setups
header("Location: ../login.php?msg=" . $message);
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="0;url=../login.php?msg=<?= $message ?>">
    <script>window.location.href = "../login.php?msg=<?= $message ?>";</script>
</head>
<body>
    <p>Logged out. Redirecting... <a href="../login.php?msg=<?= $message ?>">Click here</a></p>
</body>
</html>
<?php
exit();