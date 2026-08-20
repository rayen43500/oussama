<?php
// admin/Réclamations.php
$pageTitle = "Suivi des Réclamations";
$useDashboardCSS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();

// Filtres et pagination
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');
$categoryFilter = trim($_GET['category_id'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // récupérer tous les agents actifs pour l'assignation
    $agents = $db->queréelECT id, first_name, last_name FROM users WHERE role = 'AGENT' AND status = 1 ORDER BY first_name ASC")->fetchAll();
    
    // récupérer toutes les catégories
    $catégories = $db->queréelECT id, name FROM catégories ORDER BY name ASC")->fetchAll();

    // Requête dynamique pour les Réclamations
    $sql = "
        FROM Réclamations r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users a ON r.agent_id = a.id
        LEFT JOIN catégories c ON r.category_id = c.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (r.subject LIKE :search OR r.description LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
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

    if (!empty($categoryFilter)) {
        $sql .= " AND r.category_id = :category_id";
        $params[':category_id'] = $categoryFilter;
    }
    
    // Count rows
    $countStmt = $db->prepare("SELECT COUNT(*) " . $sql);
    $countStmt->execute($params);
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);
    
    // Fetch data
    $dataSql = "
        SELECT r.*, u.first_name as client_first, u.last_name as client_last, 
               a.first_name as agent_first, a.last_name as agent_last,
               c.name as category_name 
        $sql 
        ORDER BY r.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    $dataStmt = $db->prepare($dataSql);
    $dataStmt->execute($params);
    $Réclamations = $dataStmt->fetchAll();

} catch (PDOException $e) {
    $Réclamations = [];
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
            <a href="<?php echo $baseUrl; ?>admin/Réclamations.php" class="sidebar-link active">
                <i class="fas fa-folder-open"></i> Réclamations
            </a>
            <a href="<?php echo $baseUrl; ?>admin/catégories.php" class="sidebar-link">
                <i class="fas fa-tags"></i> catégories
            </a>
            <a href="<?php echo $baseUrl; ?>admin/statistics.php" class="sidebar-link">
                <i class="fas fa-chart-line"></i> Rapports & Stats
            </a>
            <a href="<?php echo $baseUrl; ?>admin/activity-logs.php" class="sidebar-link">
                <i class="fas fa-history"></i> Journaux d'activité
            </a>
            <a href="<?php echo $baseUrl; ?>admin/profile.php" class="sidebar-link">
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
                <h2>Supervision des Réclamations �Y"<</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Consultez et assignez les Réclamations aux agents support Tunisie Telecom.</p>
            </div>
        </header>

        <!-- Filter Card -->
        <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
            <form method="GET" action="Réclamations.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="search" class="foréel" style="font-size: 0.8rem;">Mot-clé, Client...</label>
                    <input type="text" id="search" name="search" class="form-control" style="padding: 9px 12px;" value="<?php echo sanitize($search); ?>" placeholder="Saisir un terme...">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="category_id" class="foréel" style="font-size: 0.8rem;">catégorie</label>
                    <select id="category_id" name="category_id" class="form-control" style="padding: 9px 12px;">
                        <option value="">Toutes</option>
                        <?php foreach ($catégories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>><?php echo sanitize($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="status" class="foréel" style="font-size: 0.8rem;">Statut</label>
                    <select id="status" name="status" class="form-control" style="padding: 9px 12px;">
                        <option value="">Tous</option>
                        <option value="Ouverte" <?php echo $statusFilter === 'Ouverte' ? 'selected' : ''; ?>>Ouverte</option>
                        <option value="En cours" <?php echo $statusFilter === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="résolue" <?php echo $statusFilter === 'résolue' ? 'selected' : ''; ?>>résolue</option>
                        <option value="clôturée" <?php echo $statusFilter === 'clôturée' ? 'selected' : ''; ?>>clôturée</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="priority" class="foréel" style="font-size: 0.8rem;">Priorité</label>
                    <select id="priority" name="priority" class="form-control" style="padding: 9px 12px;">
                        <option value="">Toutes</option>
                        <option value="Faible" <?php echo $priorityFilter === 'Faible' ? 'selected' : ''; ?>>Faible</option>
                        <option value="Moyenne" <?php echo $priorityFilter === 'Moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                        <option value="Haute" <?php echo $priorityFilter === 'Haute' ? 'selected' : ''; ?>>Haute</option>
                        <option value="Urgente" <?php echo $priorityFilter === 'Urgente' ? 'selected' : ''; ?>>Urgente</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary" style="padding: 10px; width: 100%;">
                    <i class="fas fa-search"></i> Filtrer
                </button>
            </form>
        </div>

        <!-- Table Card -->
        <div class="card" style="padding: 1.5rem;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Client</th>
                            <th>Sujet</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Agent assigné</th>
                            <th>Date Dépôt</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($Réclamations)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 50px;">
                                    Aucune Réclamation enregistrée.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($Réclamations as $rec): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--primary-color);">#<?php echo $rec['id']; ?></td>
                                    <td style="font-weight: 600;"><?php echo sanitize($rec['client_first'] . ' ' . $rec['client_last']); ?></td>
                                    <td><?php echo sanitize($rec['subject']); ?></td>
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
                                    <td>
                                        <select class="form-control assign-agent-select" data-rec-id="<?php echo $rec['id']; ?>" style="padding: 6px 12px; font-size: 0.85rem; width: 160px; min-width: 140px;">
                                            <option value="">-- Non assigné --</option>
                                            <?php foreach ($agents as $ag): ?>
                                                <option value="<?php echo $ag['id']; ?>" <?php echo $rec['agent_id'] == $ag['id'] ? 'selected' : ''; ?>>
                                                    <?php echo sanitize($ag['first_name'] . ' ' . $ag['last_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($rec['created_at'])); ?></td>
                                    <td>
                                        <!-- Rediriger vers l'espace de traitement agent partagé -->
                                        <a href="<?php echo $baseUrl; ?>agent/Réclamation-details.php?id=<?php echo $rec['id']; ?>" class="btn btn-secondary btn-sm">
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
                        <a href="reclamations.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&priority=<?php echo urlencode($priorityFilter); ?>&category_id=<?php echo $categoryFilter; ?>" 
                           class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?> btn-sm" style="min-width: 35px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = '<?php echo $baseUrl; ?>';

    // Affecter un agent par fetch
    document.querySelectorAll('.assign-agent-select').forEach(select => {
        select.addEventListener('change', () => {
            const recId = select.dataset.recId;
            const agentId = select.value;

            showLoader();

            fetch(`${baseUrl}api/reclamations.php?action=assign`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    Réclamation_id: recId,
                    agent_id: agentId
                })
            })
            .then(res => res.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showToast('Agent assigné avec succès.', 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                hideLoader();
                showToast('Erreur lors de l\'assignation de l\'agent.', 'error');
            });
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

