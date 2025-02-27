<?php
function getProfileImagePath($imagePath) {
    // Remove any potential double dots for security
    $imagePath = str_replace('..', '', $imagePath);
    
    // If path starts with /Images, prepend the correct root path
    if (strpos($imagePath, '/Images/') === 0) {
        return '../' . ltrim($imagePath, '/');
    }
    
    // If path contains Images/Profile, ensure correct path
    if (strpos($imagePath, 'Images/Profile/') !== false) {
        return '../Images/Profile/' . basename($imagePath);
    }
    
    // Default fallback
    return '../Images/Profile/default-avatar.jpg';
}

function displayProfileImage($imagePath) {
    $path = getProfileImagePath($imagePath);
    if (!file_exists($path)) {
        $path = '../Images/Profile/default-avatar.jpg';
    }
    return $path;
}
?>
