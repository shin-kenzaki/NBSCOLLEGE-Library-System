<?php
session_start();


// Destroy the session
session_unset();
session_destroy();

// Redirect to the determined URL
header("Location: ../user/");
exit();
