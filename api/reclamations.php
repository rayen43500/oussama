<?php
// api/Réclamations.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Détecter l'action demandée
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'suggest':
        // Suggestion intelligente de catégorie
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $description = $input['description'] ?? '';

        if (empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Description vide.']);
            exit;
        }

        $suggestion = suggest_category($description);
        
        // Trouver l'ID corépondant à ce nom de catégorie
        $stmt = $db->prepare("SELECT id FROM catégories WHERE name = ? LIMIT 1");
        $stmt->execute([$suggestion['category']]);
        $category = $stmt->fetch();
        $categoryId = $category ? $category['id'] : null;

        echo json_encode([
            'success' => true,
            'category' => $suggestion['category'],
            'category_id' => $categoryId,
            'confidence' => $suggestion['confidence']
        ]);
        break;

    case 'update_status':
        // Seuls les agents et admins peuvent changer le statut
        if ($userRole !== 'AGENT' && $userRole !== 'ADMIN' && $userRole !== 'CLIENT') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $RéclamationId = intval($input['Réclamation_id'] ?? 0);
        $newStatus = trim($input['status'] ?? '');

        // Liste des statuts valides
        $validStatuses = ['Ouverte', 'En cours', 'résolue', 'clôturée'];
        if (!in_array($newStatus, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Statut invalide.']);
            exit;
        }

        try {
            // récupérer la Réclamation et son statut actuel
            $stmt = $db->prepare("SELECT status, user_id, agent_id FROM Réclamations WHERE id = ?");
            $stmt->execute([$RéclamationId]);
            $Réclamation = $stmt->fetch();

            if (!$Réclamation) {
                echo json_encode(['success' => false, 'message' => 'Réclamation introuvable.']);
                exit;
            }

            // Les clients peuvent uniquement clore leurs propres Réclamations
            if ($userRole === 'CLIENT') {
                if ($Réclamation['user_id'] != $userId || $newStatus !== 'clôturée') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Action non autorisée pour votre profil.']);
                    exit;
                }
            }

            // Les agents doivent être assignés pour Modifié sauf s'ils la reprennent
            if ($userRole === 'AGENT' && $Réclamation['agent_id'] != $userId && $Réclamation['agent_id'] !== null) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Cette Réclamation est assignée à un autre agent.']);
                exit;
            }

            $oldStatus = $Réclamation['status'];

            if ($oldStatus === $newStatus) {
                echo json_encode(['success' => true, 'message' => 'Aucun changement de statut requis.']);
                exit;
            }

            // Mettre à jour le statut
            $resolvedAt = ($newStatus === 'résolue') ? date('Y-m-d H:i:s') : null;
            if ($newStatus === 'résolue') {
                $upStmt = $db->prepare("UPDATE Réclamations SET status = ?, resolved_at = ? WHERE id = ?");
                $upStmt->execute([$newStatus, $resolvedAt, $RéclamationId]);
            } else {
                $upStmt = $db->prepare("UPDATE Réclamations SET status = ? WHERE id = ?");
                $upStmt->execute([$newStatus, $RéclamationId]);
            }

            // Si un agent n'était pas assigné, l'assigner automatiquement
            if ($userRole === 'AGENT' && $Réclamation['agent_id'] === null) {
                $assignStmt = $db->prepare("UPDATE Réclamations SET agent_id = ? WHERE id = ?");
                $assignStmt->execute([$userId, $RéclamationId]);
            }

            // Enregistrer dans l'historique des statuts
            $histStmt = $db->prepare("INSERT INTO status_history (Réclamation_id, user_id, old_status, new_status) VALUES (?, ?, ?, ?)");
            $histStmt->execute([$RéclamationId, $userId, $oldStatus, $newStatus]);

            // Journaliser l'activité
            log_activité($userId, 'MODIFICATION_STATUT', "Réclamation #$RéclamationId : statut modifié de $oldStatus à $newStatus.");

            echo json_encode(['success' => true, 'message' => 'Le statut a été mis à jour.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()]);
        }
        break;

    case 'update_priority':
        if ($userRole !== 'AGENT' && $userRole !== 'ADMIN') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $RéclamationId = intval($input['Réclamation_id'] ?? 0);
        $newPriority = trim($input['priority'] ?? '');

        $validPriorities = ['Faible', 'Moyenne', 'Haute', 'Urgente'];
        if (!in_array($newPriority, $validPriorities)) {
            echo json_encode(['success' => false, 'message' => 'Priorité invalide.']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE Réclamations SET priority = ? WHERE id = ?");
            $stmt->execute([$newPriority, $RéclamationId]);

            log_activité($userId, 'MODIFICATION_PRIORITE', "Réclamation #$RéclamationId : priorité modifiée à $newPriority.");
            echo json_encode(['success' => true, 'message' => 'La priorité a été mise à jour.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour.']);
        }
        break;

    case 'assign':
        if ($userRole !== 'ADMIN') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès réservé aux administrateurs.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $RéclamationId = intval($input['Réclamation_id'] ?? 0);
        $agentId = $input['agent_id'] ? intval($input['agent_id']) : null;

        try {
            $stmt = $db->prepare("UPDATE Réclamations SET agent_id = ? WHERE id = ?");
            $stmt->execute([$agentId, $RéclamationId]);

            $desc = $agentId ? "Assignation de l'agent #$agentId à la Réclamation #$RéclamationId." : "Désassignation de l'agent pour la Réclamation #$RéclamationId.";
            log_activité($userId, 'ASSIGNATION_AGENT', $desc);

            echo json_encode(['success' => true, 'message' => 'Agent assigné avec succès.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'assignation.']);
        }
        break;

    case 'add_comment':
        $input = json_decode(file_get_contents('php://input'), true);
        $RéclamationId = intval($input['Réclamation_id'] ?? 0);
        $comment = trim($input['comment'] ?? '');

        if (empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Le commentaire ne peut pas être vide.']);
            exit;
        }

        try {
            // Vérifier que le client est propriétaire de la Réclamation ou que c'est un agent/admin
            $stmt = $db->prepare("SELECT user_id, agent_id FROM Réclamations WHERE id = ?");
            $stmt->execute([$RéclamationId]);
            $Réclamation = $stmt->fetch();

            if (!$Réclamation) {
                echo json_encode(['success' => false, 'message' => 'Réclamation introuvable.']);
                exit;
            }

            if ($userRole === 'CLIENT' && $Réclamation['user_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à commenter cette Réclamation.']);
                exit;
            }

            // Insérer le commentaire
            $insStmt = $db->prepare("INSERT INTO comments (Réclamation_id, user_id, comment) VALUES (?, ?, ?)");
            $insStmt->execute([$RéclamationId, $userId, $comment]);

            log_activité($userId, 'AJOUT_COMMENTAIRE', "Commentaire ajouté sur la Réclamation #$RéclamationId.");

            // Si l'agent commente, passer automatiquement le statut en "En cours" s'il était à "Ouverte"
            if ($userRole === 'AGENT' || $userRole === 'ADMIN') {
                $statusStmt = $db->prepare("SELECT status FROM Réclamations WHERE id = ?");
                $statusStmt->execute([$RéclamationId]);
                $currStatus = $statusStmt->fetchColumn();

                if ($currStatus === 'Ouverte') {
                    $upStatus = $db->prepare("UPDATE Réclamations SET status = 'En cours' WHERE id = ?");
                    $upStatus->execute([$RéclamationId]);

                    $histStmt = $db->prepare("INSERT INTO status_history (Réclamation_id, user_id, old_status, new_status) VALUES (?, ?, 'Ouverte', 'En cours')");
                    $histStmt->execute([$RéclamationId, $userId]);

                    // Si l'agent n'est pas déjà défini
                    if ($userRole === 'AGENT' && $Réclamation['agent_id'] === null) {
                        $assignStmt = $db->prepare("UPDATE Réclamations SET agent_id = ? WHERE id = ?");
                        $assignStmt->execute([$userId, $RéclamationId]);
                    }
                }
            }

            echo json_encode(['success' => true, 'message' => 'Commentaire ajouté avec succès.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du commentaire.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
        break;
}
