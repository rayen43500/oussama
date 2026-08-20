<?php
// scratch/test_suite.php

// Inclure la configuration de base de données et les fonctions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== Tunisie Telecom - Test Suite ===\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest($description, $assertion) {
    global $testsPassed, $testsFailed;
    if ($assertion) {
        echo "✅ PASS : $description\n";
        $testsPassed++;
    } else {
        echo "❌ FAIL : $description\n";
        $testsFailed++;
    }
}

// 1. Test de la connexion à la base de données
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT 1");
    $result = $stmt->fetchColumn();
    assertTest("Connexion à la base de données via PDO", $result == 1);
} catch (Exception $e) {
    assertTest("Connexion à la base de données via PDO (Erreur : " . $e->getMessage() . ")", false);
}

// 2. Test du hachage de mot de passe
$rawPassword = "DemoPassword123!";
$hashed = password_hash($rawPassword, PASSWORD_DEFAULT);
$verifyOk = password_verify($rawPassword, $hashed);
$verifyFail = password_verify("WrongPassword!", $hashed);
assertTest("Vérification correcte du mot de passe haché", $verifyOk === true);
assertTest("Échec de vérification pour mot de passe erroné", $verifyFail === false);

// 3. Test du moteur intelligent de suggestion de catégorie
$testCases = [
    "Ma connexion Internet ADSL est extrêmement lente depuis hier" => "ADSL",
    "Impossible d'appeler avec mon téléphone portable, réseau indisponible" => "Téléphonie mobile",
    "J'ai reçu une facture de smart box incorrecte avec un montant trop élevé" => "Facturation",
    "Les techniciens ont coupé le câble de la fibre optique dans ma rue" => "Fibre optique",
    "Je n'ai plus de tonalité sur ma ligne fixe classique" => "Téléphonie fixe",
    "Je veux recharger ma carte avec un ticket de recharge Mobirachid" => "Recharge",
    "Paiement refusé par carte bancaire sur le portail en ligne" => "Paiement",
    "L'accueil en agence a été désagréable" => "Service client"
];

foreach ($testCases as $phrase => $expectedCategory) {
    $res = suggest_category($phrase);
    assertTest(
        "Auto-catégorisation de : '" . substr($phrase, 0, 30) . "...' -> Attendue : $expectedCategory, Suggérée : " . $res['category'] . " (Confiance: " . $res['confidence'] . ")",
        $res['category'] === $expectedCategory
    );
}

echo "\n====================================\n";
echo "Rapport de tests :\n";
echo "Tests réussis : $testsPassed\n";
echo "Tests échoués : $testsFailed\n";
echo "====================================\n";

if ($testsFailed > 0) {
    exit(1);
} else {
    exit(0);
}
