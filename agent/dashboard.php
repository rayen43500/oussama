<?php
// agent/dashboard.php
$pageTitle = "Espace Agent Support";
$useDashboardCSS = true;
$useCharts = true;
$useDashboardJS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('AGENT');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$agentId = $_SESSION['user_id'];

try {
    // récupérer les compteurs de KPI de l'agent
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN priority = 'Urgente' AND status != 'clôturée' THEN 1 ELSE 0 END) as urgent_count,
            SUM(CASE WHEN status = 'En cours' THEN 1 ELSE 0 END) as progress_count,
            SUM(CASE WHEN status = 'résolue' THEN 1 ELSE 0 END) as resolved_count,
            COUNT(*) as total_count
        FROM Réclamations 
        WHERE agent_id = ?
    ");
    $stmt->execute([$agentId]);
    $kpis = $stmt->fetch();
    
    $total = $kpis['total_count'] ?? 0;
    $urgent = $kpis['urgent_count'] ?? 0;
    $progress = $kpis['progress_count'] ?? 0;
    $resolved = $kpis['resolved_count'] ?? 0;

    // récupérer les 5 dernières Réclamations assignées à cet agent
    $stmt = $db->prepare("
        SELECT r.*, u.first_name as client_first, u.last_name as client_last, c.name as category_name 
        FROM Réclamations r 
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN catégories c ON r.category_id = c.id 
        WHERE r.agent_id = ? 
        ORDER BY r.updated_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$agentId]);
    $assignedRéclamations = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erreur de récupération des statistiques.");
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
            <a href="<?php echo $baseUrl; ?>agent/dashboard.php" class="sidebar-link active">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="<?php echo $baseUrl; ?>agent/Réclamations.php" class="sidebar-link">
                <i class="fas fa-tasks"></i> Réclamations Assignées
            </a>
            <a href="<?php echo $baseUrl; ?>agent/profile.php" class="sidebar-link">
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
                <h2>Espace Support, Agent <?php echo sanitize($_SESSION['first_name']); ?> 🎧</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Gérez et résolvez efficacement les réclamations de vos clients.</p>
            </div>
            
            <div class="user-profile">
                <div class="user-avatar" style="background-color: var(--status-progress-light); color: var(--status-progress);">
                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                </div>
                <div style="font-size: 0.9rem; font-weight: 600;">
                    <?php echo sanitize($_SESSION['email']); ?><br>
                    <span class="badge badge-status-en-cours" style="font-size: 0.7rem;"><?php echo $_SESSION['role']; ?></span>
                </div>
            </div>
        </header>

        <!-- KPI Grid -->
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-info">
                    <h3>Total Assignées</h3>
                    <div class="kpi-value"><?php echo $total; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-clipboard-list"></i></div>
            </div>
            <div class="kpi-card kpi-urgent">
                <div class="kpi-info">
                    <h3>Urgentes Actives</h3>
                    <div class="kpi-value"><?php echo $urgent; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
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
                    <h3>activité Récentes</h3>
                    <a href="<?php echo $baseUrl; ?>agent/Réclamations.php" class="btn btn-secondary btn-sm">Gérer tout</a>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Sujet</th>
                                <th>Priorité</th>
                                <th>Statut</th>
                                <th>Dernière modif.</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignedRéclamations)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                        <i class="fas fa-smile" style="font-size: 2.5rem; margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                                        Aucune Réclamation ne vous est assignée pour l'instant !
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignedRéclamations as $rec): ?>
                                    <tr>
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
                                        <td><?php echo date('d/m/Y H:i', strtotime($rec['updated_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo $baseUrl; ?>agent/Réclamation-details.php?id=<?php echo $rec['id']; ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-edit"></i> Traiter
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
                <h3>Vos Traitements par Statut</h3>
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

