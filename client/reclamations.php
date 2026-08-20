<?php
// client/Réclamations.php
$pageTitle = "Mes Réclamations";
$useDashboardCSS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('CLIENT');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// récupérer les paramètres de filtrage / pagination
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');
$sortBy = trim($_GET['sort_by'] ?? 'created_at');
$sortOrder = trim($_GET['sort_order'] ?? 'DESC');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Valider les colonnes de tri
$allowedSortCols = ['created_at', 'priority', 'status', 'subject'];
if (!in_array($sortBy, $allowedSortCols)) $sortBy = 'created_at';
if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') $sortOrder = 'DESC';

// Notification de succès de création
$successMsg = '';
if (isset($_GET['created']) && $_GET['created'] == 1) {
    $successMsg = "🎉 Votre Réclamation a été créée avec succès. Un agent va l'étudier rapidement.";
}

try {
    // Construire la requête SQL dynamique
    $sql = "
        FROM Réclamations r
        LEFT JOIN catégories c ON r.category_id = c.id
        WHERE r.user_id = :user_id
    ";
    
    $params = [':user_id' => $userId];
    
    if (!empty($search)) {
        $sql .= " AND (r.subject LIKE :search OR r.description LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    if (!empty($statusFilter)) {
        $sql .= " AND r.status = :status";
        $params[':status'] = $statusFilter;
    }
    
    if (!empty($priorityFilter)) {
        $sql .= " AND r.priority = :priority";
        $params[':priority'] = $priorityFilter;
    }
    
    // Obtenir le nombre total d'enregistrements pour la pagination
    $countSql = "SELECT COUNT(*) " . $sql;
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);
    
    // Requête finale avec tri et pagination
    $dataSql = "
        SELECT r.*, c.name as category_name 
        $sql 
        ORDER BY r.$sortBy $sortOrder 
        LIMIT $limit OFFSET $offset
    ";
    
    $dataStmt = $db->prepare($dataSql);
    $dataStmt->execute($params);
    $Réclamations = $dataStmt->fetchAll();

} catch (PDOException $e) {
    $Réclamations = [];
    $totalPages = 0;
    $totalRows = 0;
}

require_once __DIR__ . '/../includes/header.php';
$baseUrl = get_base_url();
?>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="<?php echo $baseUrl; ?>assets/image.png" alt="Tunisie Telecom" style="height:40px; width:auto; object-fit:contain;">
        </div>
        <nav class="sidebar-menu">
            <a href="<?php echo $baseUrl; ?>client/dashboard.php" class="sidebar-link">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="<?php echo $baseUrl; ?>client/Réclamation-create.php" class="sidebar-link">
                <i class="fas fa-plus-circle"></i> Déposer Réclamation
            </a>
            <a href="<?php echo $baseUrl; ?>client/Réclamations.php" class="sidebar-link active">
                <i class="fas fa-list"></i> Mes Réclamations
            </a>
            <a href="<?php echo $baseUrl; ?>client/profile.php" class="sidebar-link">
                <i class="fas fa-user-cog"></i> Mon Profil
            </a>
            <a href="<?php echo $baseUrl; ?>index.php" class="sidebar-link" style="margin-top: auto;">
                <i class="fas fa-home"></i> Retour Accueil
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="<?php echo $baseUrl; ?>logout.php" class="sidebar-link" style="color: #ef4444;">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="main-header">
            <div>
                <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                <h2>Mes Réclamations �Y"<</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Liste de l'ensemble de vos dossiers soumis.</p>
            </div>
            
            <a href="<?php echo $baseUrl; ?>client/Réclamation-create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Déposer un dossier
            </a>
        </header>

        <?php if ($successMsg): ?>
            <div class="alert alert-success" style="max-width: 100%; margin-bottom: 20px;">
                <?php echo $successMsg; ?>
            </div>
        <?php endif; ?>

        <!-- Filter Card -->
        <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
            <form method="GET" action="Réclamations.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 100px; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="search" class="foréel" style="font-size: 0.8rem;">Recherche par mot-clé</label>
                    <input type="text" id="search" name="search" class="form-control" style="padding: 9px 12px;" placeholder="Sujet, mot..." value="<?php echo sanitize($search); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="status" class="foréel" style="font-size: 0.8rem;">Filtrer par Statut</label>
                    <select id="status" name="status" class="form-control" style="padding: 9px 12px;">
                        <option value="">Tous les statuts</option>
                        <option value="Ouverte" <?php echo $statusFilter === 'Ouverte' ? 'selected' : ''; ?>>Ouverte</option>
                        <option value="En cours" <?php echo $statusFilter === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="résolue" <?php echo $statusFilter === 'résolue' ? 'selected' : ''; ?>>résolue</option>
                        <option value="clôturée" <?php echo $statusFilter === 'clôturée' ? 'selected' : ''; ?>>clôturée</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="priority" class="foréel" style="font-size: 0.8rem;">Filtrer par Priorité</label>
                    <select id="priority" name="priority" class="form-control" style="padding: 9px 12px;">
                        <option value="">Toutes priorités</option>
                        <option value="Faible" <?php echo $priorityFilter === 'Faible' ? 'selected' : ''; ?>>Faible</option>
                        <option value="Moyenne" <?php echo $priorityFilter === 'Moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                        <option value="Haute" <?php echo $priorityFilter === 'Haute' ? 'selected' : ''; ?>>Haute</option>
                        <option value="Urgente" <?php echo $priorityFilter === 'Urgente' ? 'selected' : ''; ?>>Urgente</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="sort_by" class="foréel" style="font-size: 0.8rem;">Trier par</label>
                    <select id="sort_by" name="sort_by" class="form-control" style="padding: 9px 12px;">
                        <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Date création</option>
                        <option value="subject" <?php echo $sortBy === 'subject' ? 'selected' : ''; ?>>Sujet</option>
                        <option value="priority" <?php echo $sortBy === 'priority' ? 'selected' : ''; ?>>Priorité</option>
                        <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>Statut</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary" style="padding: 10px; width: 100%;">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
            </form>
        </div>

        <!-- Table Card -->
        <div class="card" style="padding: 1.5rem;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° dossier</th>
                            <th>Sujet</th>
                            <th>catégorie</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Date Dépôt</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($Réclamations)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 50px;">
                                    <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 15px; display: block; opacity: 0.3;"></i>
                                    Aucune Réclamation trouvée avec ces critères.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($Réclamations as $rec): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--primary-color);">#<?php echo $rec['id']; ?></td>
                                    <td style="font-weight: 600;"><?php echo sanitize($rec['subject']); ?></td>
                                    <td><?php echo sanitize($rec['category_name'] ?? 'Non classifiée'); ?></td>
                                    <td>
                                        <span class="badge badge-priority-<?php echo strtolower($rec['priority']); ?>">
                                            <?php echo $rec['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-status-<?php echo strtolower(str_replace(' ', '-', $rec['status'])); ?>">
                                            <?php echo $rec['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($rec['created_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo $baseUrl; ?>client/Réclamation-details.php?id=<?php echo $rec['id']; ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-eye"></i> Gérer
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Grid -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 1.5rem;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a hRéclamations.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&priority=<?php echo urlencode($priorityFilter); ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?>" 
                           class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?> btn-sm" 
                           style="min-width: 35px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

