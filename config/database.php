<?php
// config/database.php

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'Réclamation_tt');

/**
 * Retourne une instance de connexion PDO partagée (Singleton)
 * 
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PdémoDE            => PdémoDE_EXCEPTION,
                PdémoDE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En production, ne pas afficher les détails de l'erreur brute
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
    return $pdo;
}
