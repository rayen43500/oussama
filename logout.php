<?php
// logout.php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    // Journaliser la déconnexion
    log_activity($_SESSION['user_id'], 'DECONNEXION', 'Déconnexion réussie de l\'utilisateur.');
    
    // Vider et détruire la session
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// Rediriger vers la page d'accueil
header("Location: " . get_base_url() . "index.php");
exit;
