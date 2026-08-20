<?php
// includes/functions.php

require_once __DIR__ . '/../config/database.php';

/**
 * Sécurise les entrées utilisateur contre les failles XSS
 * 
 * @param string $data
 * @return string
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Obtient l'adresse IP réelle de l'utilisateur
 * 
 * @return string
 */
function get_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Traiter les cas de proxies multiples
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

/**
 * Enregistre une action dans la table activité
 * 
 * @param int|null $userId
 * @param string $action
 * @param string|null $description
 * @return bool
 */
function log_activité($userId, $action, $description = null) {
    try {
        $db = getDBConnection();
        $ip = get_ip_address();
        $stmt = $db->prepare("INSERT INTO activité (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $action, $description, $ip]);
    } catch (PDOException $e) {
        // Ignorer silencieusement pour éviter d'interrompre l'exécution principale en cas d'erreur de log
        return false;
    }
}

/**
 * Suggère une catégorie de Réclamation à partir de sa description
 * 
 * @param string $description
 * @return array Contient la catégorie ('category') et l'indice de confiance ('confidence')
 */
function suggest_category($description) {
    $description = mb_strtolower($description, 'UTF-8');
    
    // Mots-clés avec poids associés
    $rules = [
        'ADSL' => [
            'keywords' => ['adsl', 'vdsl', 'modem adsl', 'dsl', 'voyant dsl', 'smart adsl', 'débit adsl', 'routeur adsl'],
            'weight' => 2.0
        ],
        'Fibre optique' => [
            'keywords' => ['fibre', 'optique', 'ftth', 'raccordement fibre', 'boitier fibre', 'câble fibre', 'technicien fibre'],
            'weight' => 2.0
        ],
        'Internet' => [
            'keywords' => ['internet', 'connexion', 'wifi', 'modem', 'débit', 'dns', 'lenteur', 'lent', 'debit', 'charger', 'page', 'web', 'أنتيرنات', 'كونيكسيون'],
            'weight' => 1.0
        ],
        'Téléphonie mobile' => [
            'keywords' => ['mobile', '4g', '3g', '5g', 'appel', 'sms', 'sim', 'carte sim', 'puce', 'forfait', 'reseau mobile', 'réseau mobile', 'téléphoner', 'réseau', 'rizo', 'ريزو', 'تلفون'],
            'weight' => 1.0
        ],
        'Téléphonie fixe' => [
            'keywords' => ['fixe', 'téléphone fixe', 'tonalité', 'grésillement', 'friture', 'ligne fixe', 'فic', 'فيكس'],
            'weight' => 1.0
        ],
        'Facturation' => [
            'keywords' => ['facture', 'facturation', 'facturé', 'tarif', 'prix', 'trop-perçu', 'montant', 'frais', 'factures', 'فاتورة', 'فاتورات'],
            'weight' => 1.5
        ],
        'Paiement' => [
            'keywords' => ['paiement', 'payer', 'carte bancaire', 'e-dinar', 'otp', 'transaction', 'rejeté', 'payé'],
            'weight' => 1.5
        ],
        'Recharge' => [
            'keywords' => ['recharge', 'recharger', 'ticket', 'code de recharge', 'mobirachid', 'carte de recharge', 'solde', 'crédit'],
            'weight' => 1.5
        ],
        'Service client' => [
            'keywords' => ['service client', 'agence', 'accueil', 'impolie', 'irrespectueux', 'attente', 'boutique', 'vendeur', 'conseiller', 'support'],
            'weight' => 1.2
        ]
    ];

    $scores = [];
    foreach ($rules as $cat => $data) {
        $score = 0;
        foreach ($data['keywords'] as $kw) {
            // Compter les occurrences du mot-clé
            $count = mb_substr_count($description, $kw);
            if ($count > 0) {
                $score += $count * $data['weight'];
            }
        }
        if ($score > 0) {
            $scores[$cat] = $score;
        }
    }

    if (empty($scores)) {
        return [
            'category' => 'Autre',
            'confidence' => 0.50
        ];
    }

    // Trier par score décroissant
    arsort($scores);
    
    $bestCat = key($scores);
    $bestScore = current($scores);
    
    // Normalisation de l'indice de confiance entre 0.65 et 0.98
    $confidence = 0.65 + (min($bestScore, 8) / 8) * 0.33;
    $confidence = round($confidence, 2);

    return [
        'category' => $bestCat,
        'confidence' => $confidence
    ];
}

/**
 * Traite et enregistre une pièce jointe pour une Réclamation
 * 
 * @param array $file Élément de $_FILES
 * @param int $RéclamationId
 * @return array ['success' => bool, 'message' => string, 'path' => string|null]
 */
function upload_attachment($file, $RéclamationId) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Aucun fichier téléchargé ou erreur de transfert.'];
    }

    $fileName = trim($file['name']);
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileType = $file['type'];

    // Vérification de la taille (5 Mo max)
    $maxSize = 5 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        return ['success' => false, 'message' => 'La taille du fichier dépasse la limite autorisée de 5 Mo.'];
    }

    // Vérification de l'extension
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExts = ['png', 'jpg', 'jpeg', 'pdf', 'docx', 'doc'];
    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'message' => 'Extension de fichier non autorisée (uniquement png, jpg, jpeg, pdf, docx, doc).'];
    }

    // Chemin absolu vers le répertoire d'upload
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier unique et sécurisé
    $newFileName = uniqid('rec_' . $RéclamationId . '_', true) . '.' . $ext;
    $destPath = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmp, $destPath)) {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("INSERT INTO attachments (Réclamation_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)");
            $réelativePath = 'uploads/' . $newFileName;
            $stmt->execute([$RéclamationId, $fileName, $réelativePath, $fileType]);
            return ['success' => true, 'path' => $réelativePath, 'name' => $fileName];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement en base de données.'];
        }
    }

    return ['success' => false, 'message' => 'Erreur lors du déplacement du fichier sur le serveur.'];
}
