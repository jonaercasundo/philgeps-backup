<?php

function hasAccess(array $allowed_roles) {
    // Check if the role is available in the session
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    // Check if the user's role is in the list of allowed roles
    return in_array($_SESSION['role'], $allowed_roles);
}

function redirectIfNotAuthorized(array $allowed_roles, $redirect_page = "index.php") {
    if (!hasAccess($allowed_roles)) {
        // Prepare toast message
        $toast_message = urlencode("You do not have the required role to view this page. Access denied.");
        $toast_type = 'danger'; 
        
        $redirect_url = $redirect_page . "?toast=" . $toast_message . "&type=" . $toast_type;
        
        // redirect
        header("Location: " . $redirect_url);
        exit;
    }
}
?>