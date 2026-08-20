<?php
// api/dashboard.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté.']);
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$action = $_GET['action'] ?? '';

// Mappage des couleurs pour les graphiques de statuts
$statusColors = [
    'Ouverte' => '#3B82F6',   // Blue
    'En cours' => '#F59E0B',  // Orange
    'résolue' => '#10B981',   // Green
    'clôturée' => '#6B7280'   // Grey
];

// Mappage des couleurs pour les priorités
$priorityColors = [
    'Faible' => '#10B981',
    'Moyenne' => '#3B82F6',
    'Haute' => '#F59E0B',
    'Urgente' => '#EF4444'
];

switch ($action) {
    case 'status_stats':
        // Graphique de statut pour Client ou Agent connecté
        try {
            if ($userRole === 'CLIENT') {
                $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM Réclamations WHERE user_id = ? GROUP BY status");
                $stmt->execute([$userId]);
            } elseif ($userRole === 'AGENT') {
                $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM Réclamations WHERE agent_id = ? GROUP BY status");
                $stmt->execute([$userId]);
            } else {
                // Admin global
                $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM Réclamations GROUP BY status");
                $stmt->execute();
            }

            $raw = $stmt->fetchAll();
            $labels = [];
            $values = [];
            $colors = [];

            // Assurer que tous les statuts sont présents même à 0 pour la cohérence visuelle
            $statuses = ['Ouverte', 'En cours', 'résolue', 'clôturée'];
            $counts = array_fill_keys($statuses, 0);
            
            foreach ($raw as $row) {
                if (array_key_exists($row['status'], $counts)) {
                    $counts[$row['status']] = intval($row['count']);
                }
            }

            foreach ($counts as $status => $count) {
                $labels[] = $status;
                $values[] = $count;
                $colors[] = $statusColors[$status];
            }

            echo json_encode([
                'success' => true,
                'labels' => $labels,
                'values' => $values,
                'colors' => $colors
            ]);

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur SQL.']);
        }
        break;

    case 'admin_stats':
        if ($userRole !== 'ADMIN') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }

        try {
            // 1. Réclamation par statut
            $stmt = $db->query("SELECT status, COUNT(*) as count FROM Réclamations GROUP BY status");
            $rawStatus = $stmt->fetchAll();
            $statusCounts = array_fill_keys(['Ouverte', 'En cours', 'résolue', 'clôturée'], 0);
            foreach ($rawStatus as $row) {
                $statusCounts[$row['status']] = intval($row['count']);
            }
            $statusData = [
                'labels' => array_keys($statusCounts),
                'values' => array_values($statusCounts),
                'colors' => array_values($statusColors)
            ];

            // 2. Réclamations par catégorie (jointure pour avoir le nom de la catégorie)
            $stmt = $db->query("
                SELECT c.name, COUNT(r.id) as count 
                FROM catégories c 
                LEFT JOIN Réclamations r ON c.id = r.category_id 
                GROUP BY c.id
            ");
            $rawCat = $stmt->fetchAll();
            $catLabels = [];
            $catValues = [];
            foreach ($rawCat as $row) {
                $catLabels[] = $row['name'];
                $catValues[] = intval($row['count']);
            }
            $categoryData = [
                'labels' => $catLabels,
                'values' => $catValues
            ];

            // 3. Réclamations par priorité
            $stmt = $db->query("SELECT priority, COUNT(*) as count FROM Réclamations GROUP BY priority");
            $rawPriority = $stmt->fetchAll();
            $priorityCounts = array_fill_keys(['Faible', 'Moyenne', 'Haute', 'Urgente'], 0);
            foreach ($rawPriority as $row) {
                $priorityCounts[$row['priority']] = intval($row['count']);
            }
            $priorityData = [
                'labels' => array_keys($priorityCounts),
                'values' => array_values($priorityCounts),
                'colors' => array_values($priorityColors)
            ];

            // 4. Évolution des Réclamations par mois et Prévision
            // Regrouper par mois
            $stmt = $db->query("
                SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                FROM Réclamations 
                GROUP BY month 
                ORDER BY month ASC 
                LIMIT 6
            ");
            $rawEvolution = $stmt->fetchAll();
            $evoLabels = [];
            $evoValues = [];

            foreach ($rawEvolution as $row) {
                $time = strtotime($row['month'] . "-01");
                $evoLabels[] = get_french_month_name(date('n', $time)) . " " . date('Y', $time);
                $evoValues[] = intval($row['count']);
            }

            // Calcul prédictif
            $predictedValue = get_linear_prediction($evoValues);
            
            // Calculer le label du mois prochain
            if (!empty($rawEvolution)) {
                $lastMonthStr = $rawEvolution[count($rawEvolution) - 1]['month'];
                $nextMonthTime = strtotime($lastMonthStr . "-01 +1 month");
                $préel = get_french_month_name(date('n', $nextMonthTime)) . " " . date('Y', $nextMonthTime);
            } else {
                $nextMonthTime = strtotime("+1 month");
                $préel = get_french_month_name(date('n', $nextMonthTime)) . " " . date('Y', $nextMonthTime);
            }

            $evolutionData = [
                'labels' => $evoLabels,
                'values' => $evoValues,
                'préel' => $préel,
                'predicted_value' => $predictedValue
            ];

            echo json_encode([
                'success' => true,
                'data' => [
                    'status' => $statusData,
                    'category' => $categoryData,
                    'priority' => $priorityData,
                    'evolution' => $evolutionData
                ]
            ]);

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur SQL lors du calcul des stats admin : ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action non supportée.']);
        break;
}

/**
 * Traduit un numéro de mois en mois français
 */
function get_french_month_name($num) {
    $months = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    ];
    return $months[$num] ?? '';
}

/**
 * Prédit le point suivant de la série temporéelle par régression linéaire (Moindres Carrés)
 * y = mx + b
 * 
 * @param array $values
 * @return int
 */
function get_linear_prediction($values) {
    $n = count($values);
    if ($n < 2) {
        return $n === 1 ? $values[0] : 0;
    }
    
    $sumX = 0;
    $sumY = 0;
    $sumXY = 0;
    $sumXX = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $x = $i + 1;
        $y = $values[$i];
        $sumX += $x;
        $sumY += $y;
        $sumXY += $x * $y;
        $sumXX += $x * $x;
    }
    
    $denominator = ($n * $sumXX - $sumX * $sumX);
    if ($denominator == 0) return round($sumY / $n);

    $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
    $intercept = ($sumY - $slope * $sumX) / $n;
    
    // Prédire le point suivant x = n + 1
    $predicted = $slope * ($n + 1) + $intercept;
    return max(0, intval(round($predicted)));
}
