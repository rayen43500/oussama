<?php
// client/dashboard.php
$pageTitle = "Mon Espace Client";
$useDashboardCSS = true;
$useCharts = true;
$useDashboardJS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('CLIENT');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// récupérer les compteurs de KPI
try {
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN status = 'Ouverte' THEN 1 ELSE 0 END) as open_count,
            SUM(CASE WHEN status = 'En cours' THEN 1 ELSE 0 END) as progress_count,
            SUM(CASE WHEN status = 'résolue' THEN 1 ELSE 0 END) as resolved_count,
            COUNT(*) as total_count
        FROM Réclamations 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $kpis = $stmt->fetch();
    
    $total = $kpis['total_count'] ?? 0;
    $open = $kpis['open_count'] ?? 0;
    $progress = $kpis['progress_count'] ?? 0;
    $resolved = $kpis['resolved_count'] ?? 0;

    // récupérer les 5 dernières Réclamations
    $stmt = $db->prepare("
        SELECT r.*, c.name as category_name 
        FROM Réclamations r 
        LEFT JOIN catégories c ON r.category_id = c.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $Réclamations = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erreur de récupération des données.");
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
            <a href="<?php echo $baseUrl; ?>client/dashboard.php" class="sidebar-link active">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="<?php echo $baseUrl; ?>client/reclamation-create.php" class="sidebar-link">
                <i class="fas fa-plus-circle"></i> Déposer Réclamation
            </a>
            <a href="<?php echo $baseUrl; ?>client/reclamations.php" class="sidebar-link">
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
                <h2>Bienvenue, <?php echo sanitize($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?> 👋</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Suivez et déposez vos réclamations Tunisie Telecom.</p>
            </div>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                </div>
                <div style="font-size: 0.9rem; font-weight: 600;">
                    <?php echo sanitize($_SESSION['email']); ?><br>
                    <span class="badge badge-priority-faible" style="font-size: 0.7rem;"><?php echo $_SESSION['role']; ?></span>
                </div>
            </div>
        </header>

        <!-- KPI Grid -->
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-info">
                    <h3>Total Soumises</h3>
                    <div class="kpi-value"><?php echo $total; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-folder-open"></i></div>
            </div>
            <div class="kpi-card kpi-open">
                <div class="kpi-info">
                    <h3>Ouvertes</h3>
                    <div class="kpi-value"><?php echo $open; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-envelope-open-text"></i></div>
            </div>
            <div class="kpi-card kpi-progress">
                <div class="kpi-info">
                    <h3>En cours</h3>
                    <div class="kpi-value"><?php echo $progress; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-spinner"></i></div>
            </div>
            <div class="kpi-card kpi-resolved">
                <div class="kpi-info">
                    <h3>résolues</h3>
                    <div class="kpi-value"><?php echo $resolved; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-check-double"></i></div>
            </div>
        </section>

        <!-- Dashboard Grid (Table + Chart) -->
        <div class="dashboard-grid">
            <!-- Left Column: Table -->
            <div class="card" style="padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3>Réclamations Récentes</h3>
                    <a href="<?php echo $baseUrl; ?>client/Réclamations.php" class="btn btn-secondary btn-sm">Voir tout</a>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sujet</th>
                                <th>catégorie</th>
                                <th>Priorité</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($Réclamations)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                        <i class="far fa-folder-open" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        Aucune Réclamation déposée pour le moment.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($Réclamations as $rec): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo sanitize($rec['subject']); ?></td>
                                        <td><?php echo sanitize($rec['category_name'] ?? 'Non catégorisée'); ?></td>
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
                                                <i class="fas fa-eye"></i> Suivre
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Column: Chart -->
            <div class="card" style="display: flex; flex-direction: column;">
                <h3>Répartition par Statut</h3>
                <div class="chart-container" style="flex-grow: 1; min-height: 250px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

