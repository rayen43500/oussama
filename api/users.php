<?php
// api/users.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Protection stricte Admin
if (!is_logged_in() || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès interdit. Réservé aux administrateurs.']);
    exit;
}

$db = getDBConnection();
$adminId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $input = json_decode(file_get_contents('php://input'), true);
        $first_name = trim($input['first_name'] ?? '');
        $last_name = trim($input['last_name'] ?? '');
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone = trim($input['phone'] ?? '');
        $role = trim($input['role'] ?? 'CLIENT');
        $password = $input['password'] ?? '';

        if (empty($first_name) || empty($last_name) || !$email || empty($phone) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs requis.']);
            exit;
        }

        try {
            // Vérifier email unique
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Cette adresse email est déjà prise.']);
                exit;
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $ins->execute([$first_name, $last_name, $email, $phone, $hashedPassword, $role]);

            log_activité($adminId, 'CREATION_UTILISATEUR', "Admin a créé l'utilisateur $email ($role).");
            echo json_encode(['success' => true, 'message' => 'Utilisateur créé avec succès.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur technique lors de la création.']);
        }
    }
    elseif ($action === 'update') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $first_name = trim($input['first_name'] ?? '');
        $last_name = trim($input['last_name'] ?? '');
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone = trim($input['phone'] ?? '');
        $role = trim($input['role'] ?? '');
        $status = isset($input['status']) ? intval($input['status']) : 1;

        if (!$id || empty($first_name) || empty($last_name) || !$email || empty($phone) || empty($role)) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis.']);
            exit;
        }

        try {
            // Vérifier email unique sauf lui-même
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Cette adresse email est déjà utilisée.']);
                exit;
            }

            $up = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
            $up->execute([$first_name, $last_name, $email, $phone, $role, $status, $id]);

            log_activité($adminId, 'MODIFICATION_UTILISATEUR', "Admin a mis à jour l'utilisateur ID #$id ($email).");
            echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur technique lors de la mise à jour.']);
        }
    }
    elseif ($action === 'toggle_status') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);

        if ($id === $adminId) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas suspendre votre propre compte.']);
            exit;
        }

        try {
            $stmt = $db->prepare("SELECT status, email FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $u = $stmt->fetch();

            if (!$u) {
                echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
                exit;
            }

            $newStatus = $u['status'] == 1 ? 0 : 1;
            $up = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $up->execute([$newStatus, $id]);

            $act = $newStatus == 1 ? 'ACTIVATION_COMPTE' : 'SUSPENSION_COMPTE';
            log_activité($adminId, $act, "Changement de statut pour l'utilisateur ID #$id (" . $u['email'] . ").");

            echo json_encode(['success' => true, 'message' => 'Statut mis à jour.', 'new_status' => $newStatus]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur technique lors de la mise à jour.']);
        }
    }
}
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);

    if ($id === $adminId) {
        echo json_encode(['success' => false, 'message' => 'Action impossible. Vous ne pouvez pas supprimer votre propre compte.']);
        exit;
    }

    try {
        // Suppression définitive
        $del = $db->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$id]);

        log_activité($adminId, 'SUPPRESSION_UTILISATEUR', "Admin a supprimé définitivement l'utilisateur ID #$id.");
        echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès.']);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Impossible de supprimer cet utilisateur car des Réclamations ou commentaires lui sont rattachés. Veuillez plutôt suspendre son compte.'
        ]);
    }
}
