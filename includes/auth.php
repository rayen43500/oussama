<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    // Configuration de session sécurisée
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    session_start();
}

/**
 * Détermine l'URL de base dynamique du projet
 * 
 * @return string Ex: '/Réclamation-tt/' ou '/'
 */
function get_base_url() {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($scriptName);
    $dir = str_replace('\\', '/', $dir);
    
    // Supprimer les dossiers enfants de la fin du chemin si présents
    $base = preg_replace('/(\/client|\/agent|\/admin|\/api|\/config|\/includes)$/i', '', $dir);
    return rtrim($base, '/') . '/';
}

/**
 * Vérifie si l'utilisateur est connecté
 * 
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Protège une page en forçant la connexion et éventuellement un ou plusieurs rôles
 * 
 * @param array|string $allowedRoles Un ou plusieurs rôles autorisés (ex: 'ADMIN' ou ['AGENT', 'ADMIN'])
 */
function require_role($allowedRoles = []) {
    $baseUrl = get_base_url();
    
    if (!is_logged_in()) {
        header("Location: " . $baseUrl . "login.php");
        exit;
    }
    
    // Si le compte est désactivé
    if (isset($_SESSION['status']) && $_SESSION['status'] == 0) {
        // Détruire la session et rediriger avec un message d'erreur
        session_unset();
        session_destroy();
        header("Location: " . $baseUrl . "login.php?error=account_disabled");
        exit;
    }
    
    if (!empty($allowedRoles)) {
        if (is_string($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        
        if (!in_array($_SESSION['role'], $allowedRoles)) {
            // Accès refusé : rediriger vers le dashboard approprié
            redirect_to_dashboard($_SESSION['role']);
        }
    }
}

/**
 * Redirige un utilisateur vers son tableau de bord corépondant
 * 
 * @param string $role
 */
function redirect_to_dashboard($role) {
    $baseUrl = get_base_url();
    switch (strtoupper($role)) {
        case 'ADMIN':
            header("Location: " . $baseUrl . "admin/dashboard.php");
            break;
        case 'AGENT':
            header("Location: " . $baseUrl . "agent/dashboard.php");
            break;
        case 'CLIENT':
            header("Location: " . $baseUrl . "client/dashboard.php");
            break;
        default:
            header("Location: " . $baseUrl . "index.php");
            break;
    }
    exit;
}
