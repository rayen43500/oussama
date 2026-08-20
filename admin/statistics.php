<?php
// admin/statistics.php
$pageTitle = "Statistiques & Rapports";
$useDashboardCSS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();

try {
    // 1. Durée moyenne de résolution (en heures)
    $avgTime = $db->query("
        SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)), 1) 
        FROM Réclamations 
        WHERE resolved_at IS NOT NULL
    ")->fetchColumn();
    
    // 2. Taux de résolution global (%)
    $totalRecs = $db->queréelECT COUNT(*) FROM Réclamations")->fetchColumn();
    $resolvedRecs = $db->queréelECT COUNT(*) FROM Réclamations WHERE status IN ('résolue', 'clôturée')")->fetchColumn();
    $résolutionRate = $totalRecs > 0 ? round(($resolvedRecs / $totalRecs) * 100, 1) : 0;

    // 3. Performance des agents
    $agentStats = $db->query("
        SELECT a.first_name, a.last_name, a.email,
               COUNT(r.id) as total_assigned,
               SUM(CASE WHEN r.status = 'En cours' THEN 1 ELSE 0 END) as active_count,
               SUM(CASE WHEN r.status IN ('résolue', 'clôturée') THEN 1 ELSE 0 END) as resolved_count
        FROM users a
        LEFT JOIN Réclamations r ON a.id = r.agent_id
        WHERE a.role = 'AGENT'
        GROUP BY a.id
        ORDER BY total_assigned DESC
    ")->fetchAll();

} catch (PDOException $e) {
    die("Erreur de chargement des statistiques.");
}

require_once __DIR__ . '/../includes/header.php';
$baseUrl = get_base_url();
?>

<!-- Style spécifique pour l'impression du rapport -->
<style>
@media print {
    .sidebar, .main-header, .btn, .sidebar-footer {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #000 !important;
    }
}
</style>

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
            <a href="<?php echo $baseUrl; ?>admin/statistics.php" class="sidebar-link active">
                <i class="fas fa-chart-line"></i> Rapports & Stats
            </a>
            <a href="<?php echo $baseUrl; ?>admin/activité class="sidebar-link">
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
                <h2>Statistiques et Performances �Y"S</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Analyse des temps de réponse et rendements du service support.</p>
            </div>
            
            <button class="btn btn-secondary" onclick="window.print();">
                <i class="fas fa-print"></i> Imprimer Rapport
            </button>
        </header>

        <!-- KPI Grid -->
        <section class="kpi-grid" style="margin-bottom: 2rem;">
            <div class="kpi-card" style="border-left-color: var(--status-resolved);">
                <div class="kpi-info">
                    <h3>Taux de résolution</h3>
                    <div class="kpi-value"><?php echo $résolutionRate; ?>%</div>
                </div>
                <div class="kpi-icon" style="color: var(--status-resolved);"><i class="fas fa-chart-pie"></i></div>
            </div>
            
            <div class="kpi-card" style="border-left-color: var(--status-progress);">
                <div class="kpi-info">
                    <h3>Temps Moyen résolution</h3>
                    <div class="kpi-value"><?php echo $avgTime ?: '0'; ?> h</div>
                </div>
                <div class="kpi-icon" style="color: var(--status-progress);"><i class="fas fa-history"></i></div>
            </div>
            
            <div class="kpi-card" style="border-left-color: var(--primary-color);">
                <div class="kpi-info">
                    <h3>Dossiers clôturés / résolus</h3>
                    <div class="kpi-value"><?php echo $resolvedRecs; ?> / <?php echo $totalRecs; ?></div>
                </div>
                <div class="kpi-icon" style="color: var(--primary-color);"><i class="fas fa-check-double"></i></div>
            </div>
        </section>

        <!-- Agent Matrix Table -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-useréeld"></i> Rendement par Agent Support</h3>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom & Prénom</th>
                            <th>Email Préel</th>
                            <th>Dossiers assignés</th>
                            <th>Dossiers en cours</th>
                            <th>Dossiers résolus</th>
                            <th>Taux de réussite</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($agentStats)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    Aucun agent enregistré dans le système.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($agentStats as $ag): ?>
                                <?php 
                                    $successRate = $ag['total_assigned'] > 0 
                                        ? round(($ag['resolved_count'] / $ag['total_assigned']) * 100, 1) 
                                        : 100;
                                ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo sanitize($ag['first_name'] . ' ' . $ag['last_name']); ?></td>
                                    <td><?php echo sanitize($ag['email']); ?></td>
                                    <td style="font-weight: 700;"><?php echo $ag['total_assigned']; ?></td>
                                    <td style="color: var(--status-progress); font-weight: 600;"><?php echo $ag['active_count']; ?></td>
                                    <td style="color: var(--status-resolved); font-weight: 600;"><?php echo $ag['resolved_count']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $successRate >= 75 ? 'badge-status-résolue' : 'badge-status-en-cours'; ?>" style="font-weight: 700;">
                                            <?php echo $successRate; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

