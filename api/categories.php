<?php
// api/catégories.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Protection admin
if (!is_logged_in() || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès interdit.']);
    exit;
}

$db = getDBConnection();
$adminId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Le nom de la catégorie est requis.']);
            exit;
        }

        try {
            // Vérifier doublon
            $stmt = $db->prepare("SELECT id FROM catégories WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Cette catégorie existe déjà.']);
                exit;
            }

            $ins = $db->prepare("INSERT INTO catégories (name, description) VALUES (?, ?)");
            $ins->execute([$name, $description]);

            log_activité($adminId, 'CREATION_CATEGORIE', "Catégorie créée : $name.");
            echo json_encode(['success' => true, 'message' => 'catégorie ajoutée avec succès.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur technique lors de l\'ajout.']);
        }
    } elseif ($action === 'update') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');

        if (!$id || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants.']);
            exit;
        }

        try {
            // Vérifier doublon sauf lui-même
            $stmt = $db->prepare("SELECT id FROM catégories WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Une autre catégorie porte déjà ce nom.']);
                exit;
            }

            $up = $db->prepare("UPDATE catégories SET name = ?, description = ? WHERE id = ?");
            $up->execute([$name, $description, $id]);

            log_activité($adminId, 'MODIFICATION_CATEGORIE', "Catégorie ID #$id mise à jour : $name.");
            echo json_encode(['success' => true, 'message' => 'catégorie mise à jour avec succès.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur technique lors de la mise à jour.']);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);

    try {
        $del = $db->prepare("DELETE FROM catégories WHERE id = ?");
        $del->execute([$id]);

        log_activité($adminId, 'SUPPRESSION_CATEGORIE', "Catégorie ID #$id supprimée.");
        echo json_encode(['success' => true, 'message' => 'catégorie supprimée avec succès.']);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de supprimer cette catégorie car elle contient des Réclamations actives. Réassignez les Réclamations d\'abord.'
        ]);
    }
}
