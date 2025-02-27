<?php
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    // Log error for debugging
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    
    // Only handle 404-like errors
    if ($errno == E_WARNING && (
        strpos($errstr, 'failed to open stream') !== false ||
        strpos($errstr, 'undefined index') !== false ||
        strpos($errstr, 'undefined variable') !== false
    )) {
        // Redirect to 404 page
        header("Location: /Library-System/Admin/404.php");
        exit();
    }
    
    // Return false to allow PHP's internal error handler to handle other errors
    return false;
}

// Set the custom error handler
set_error_handler("custom_error_handler");
?>
