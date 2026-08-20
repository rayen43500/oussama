<?php
// admin/activité
$pageTitle = "Journaux d'activité
$useDashboardCSS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();

// Filtres et pagination
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

try {
    $sql = "
        FROM activité l
        LEFT JOIN users u ON l.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (l.action LIKE :search OR l.description LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR l.ip_address LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Total rows
    $countStmt = $db->prepare("SELECT COUNT(*) " . $sql);
    $countStmt->execute($params);
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);
    
    // Fetch logs
    $dataSql = "
        SELECT l.*, u.first_name, u.last_name, u.email 
        $sql 
        ORDER BY l.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    $dataStmt = $db->prepare($dataSql);
    $dataStmt->execute($params);
    $logs = $dataStmt->fetchAll();

} catch (PDOException $e) {
    $logs = [];
    $totalPages = 0;
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
            <a href="<?php echo $baseUrl; ?>admin/dashboard.php" class="sidebar-link">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="<?php echo $baseUrl; ?>admin/users.php" class="sidebar-link">
                <i class="fas fa-users-cog"></i> Utilisateurs
            </a>
            <a href="<?php echo $baseUrl; ?>admin/Réclamations.php" class="sidebar-link">
                <i class="fas fa-folder-open"></i> Réclamations
            </a>
            <a href="<?php echo $baseUrl; ?>admin/catégories.php" class="sidebar-link">
                <i class="fas fa-tags"></i> catégories
            </a>
            <a href="<?php echo $baseUrl; ?>admin/statistics.php" class="sidebar-link">
                <i class="fas fa-chart-line"></i> Rapports & Stats
            </a>
            <a href="<?php echo $baseUrl; ?>admin/activité class="sidebar-link active">
                <i class="fas fa-history"></i> Journaux d'activité
            </a>
            <a href="<?php echo $baseUrl; ?>admin/profile.php" class="sidebar-link">
                <i class="fas fa-useréeld"></i> Mon Profil
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
                <h2>Journaux d'activité Système �Y"o</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Historique d'audit de Sécuritééé et traçabilité des opérations de la plateforme.</p>
            </div>
        </header>

        <!-- Search Card -->
        <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
            <form method="GET" action="activité style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0; flex-grow: 1;">
                    <label for="search" class="foréel" style="font-size: 0.8rem;">Recherche (Action, utilisateur, description, adresse IP...)</label>
                    <input type="text" id="search" name="search" class="form-control" style="padding: 9px 12px;" value="<?php echo sanitize($search); ?>" placeholder="Saisir un mot-clé...">
                </div>
                <button type="submit" class="btn btn-secondary" style="padding: 10px 25px;">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <!-- Table Card -->
        <div class="card" style="padding: 1.5rem;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date & Heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Adresse IP</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                    Aucun journal d'activité enregistré pour le moment.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="font-weight: 600; font-size: 0.85rem; color: var(--text-muted);">
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <strong><?php echo sanitize($log['first_name'] . ' ' . $log['last_name']); ?></strong><br>
                                            <span style="font-size: 0.78rem; color: var(--text-muted);"><?php echo sanitize($log['email']); ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-style: italic;">Système / Invité</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="font-weight:700; background-color: var(--primary-light); color: var(--primary-color);">
                                            <?php echo sanitize($log['action']); ?>
                                        </span>
                                    </td>
                                    <td style="font-family: monospace; font-size: 0.85rem;"><?php echo sanitize($log['ip_address']); ?></td>
                                    <td style="font-size: 0.88rem; max-width: 350px;"><?php echo sanitize($log['description']); ?></td>
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
                        <a href="activité<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                           class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?> btn-sm" style="min-width: 35px;">
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

