<?php
// admin/dashboard.php
$pageTitle = "Tableau de Bord Administrateur";
$useDashboardCSS = true;
$useCharts = true;
$useDashboardJS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();

try {
    $uCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $cCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'CLIENT'")->fetchColumn();
    $aCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'AGENT'")->fetchColumn();

    // KPIs Réclamations
    $rCount = $db->query("SELECT COUNT(*) FROM Réclamations")->fetchColumn();
    $openCount = $db->query("SELECT COUNT(*) FROM Réclamations WHERE status = 'Ouverte'")->fetchColumn();
    $progressCount = $db->query("SELECT COUNT(*) FROM Réclamations WHERE status = 'En cours'")->fetchColumn();
    $resolvedCount = $db->query("SELECT COUNT(*) FROM Réclamations WHERE status = 'résolue'")->fetchColumn();
    $urgentCount = $db->query("SELECT COUNT(*) FROM Réclamations WHERE priority = 'Urgente' AND status != 'clôturée'")->fetchColumn();

    // Calcul de la prévision de Réclamations pour le mois prochain (côté serveur pour l'affichage statique)
    $evoStmt = $db->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM Réclamations 
        GROUP BY month 
        ORDER BY month ASC 
        LIMIT 6
    ");
    $evoValues = [];
    $evoMonths = [];
    foreach ($evoStmt->fetchAll() as $row) {
        $evoMonths[] = $row['month'];
        $evoValues[] = intval($row['count']);
    }

    $predictedValue = 0;
    if (count($evoValues) >= 2) {
        $n = count($evoValues);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumXX = 0;
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $evoValues[$i];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }
        $denominator = ($n * $sumXX - $sumX * $sumX);
        $slope = $denominator != 0 ? ($n * $sumXY - $sumX * $sumY) / $denominator : 0;
        $intercept = ($sumY - $slope * $sumX) / $n;
        $predictedValue = max(0, intval(round($slope * ($n + 1) + $intercept)));
    } else {
        $predictedValue = !empty($evoValues) ? $evoValues[0] : 0;
    }

} catch (PDOException $e) {
    die("Erreur technique de chargement du dashboard administrateur.");
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
            <a href="<?php echo $baseUrl; ?>admin/dashboard.php" class="sidebar-link active">
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
                <h2>Supervision Globale Tunisie Telecom 🏢</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Espace d'administration et d'analyse prédictive.</p>
            </div>
            
            <div class="user-profile">
                <div class="user-avatar" style="background-color: var(--priority-urgent); color: white;">
                    AD
                </div>
                <div style="font-size: 0.9rem; font-weight: 600;">
                    Administrateur<br>
                    <span class="badge badge-priority-urgente" style="font-size: 0.7rem; padding: 2px 6px;">ADMIN</span>
                </div>
            </div>
        </header>

        <!-- KPI Grid 1: Utilisateurs -->
        <h3 style="margin-bottom: 1rem; font-size: 1.1rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Indicateurs Utilisateurs</h3>
        <section class="kpi-grid" style="margin-bottom: 2rem;">
            <div class="kpi-card" style="border-left-color: #003366;">
                <div class="kpi-info">
                    <h3>Total Comptes</h3>
                    <div class="kpi-value"><?php echo $uCount; ?></div>
                </div>
                <div class="kpi-icon" style="color: #003366;"><i class="fas fa-users"></i></div>
            </div>
            <div class="kpi-card" style="border-left-color: var(--primary-color);">
                <div class="kpi-info">
                    <h3>Clients</h3>
                    <div class="kpi-value"><?php echo $cCount; ?></div>
                </div>
                <div class="kpi-icon" style="color: var(--primary-color);"><i class="fas fa-user-friends"></i></div>
            </div>
            <div class="kpi-card" style="border-left-color: var(--status-progress);">
                <div class="kpi-info">
                    <h3>Agents Support</h3>
                    <div class="kpi-value"><?php echo $aCount; ?></div>
                </div>
                <div class="kpi-icon" style="color: var(--status-progress);"><i class="fas fa-user-shield"></i></div>
            </div>
        </section>

        <!-- KPI Grid 2: Réclamations -->
        <h3 style="margin-bottom: 1rem; font-size: 1.1rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Statuts Réclamations</h3>
        <section class="kpi-grid" style="margin-bottom: 2.5rem;">
            <div class="kpi-card">
                <div class="kpi-info">
                    <h3>Total Soumises</h3>
                    <div class="kpi-value"><?php echo $rCount; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-folder"></i></div>
            </div>
            <div class="kpi-card kpi-open">
                <div class="kpi-info">
                    <h3>Ouvertes</h3>
                    <div class="kpi-value"><?php echo $openCount; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-envelope-open-text"></i></div>
            </div>
            <div class="kpi-card kpi-progress">
                <div class="kpi-info">
                    <h3>En cours</h3>
                    <div class="kpi-value"><?php echo $progressCount; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-spinner"></i></div>
            </div>
            <div class="kpi-card kpi-resolved">
                <div class="kpi-info">
                    <h3>résolues</h3>
                    <div class="kpi-value"><?php echo $resolvedCount; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="kpi-card kpi-urgent">
                <div class="kpi-info">
                    <h3>Urgentes Actives</h3>
                    <div class="kpi-value"><?php echo $urgentCount; ?></div>
                </div>
                <div class="kpi-icon"><i class="fas fa-fire"></i></div>
            </div>
        </section>

        <!-- Charts Grid 1: Statut & Priorité -->
        <div class="dashboard-grid">
            <div class="card" style="display: flex; flex-direction: column;">
                <h3>Répartition par Statut</h3>
                <div class="chart-container" style="flex-grow: 1; min-height: 280px;">
                    <canvas id="adminStatusChart"></canvas>
                </div>
            </div>
            <div class="card" style="display: flex; flex-direction: column;">
                <h3>Niveaux de Priorité</h3>
                <div class="chart-container" style="flex-grow: 1; min-height: 280px;">
                    <canvas id="adminPriorityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Grid 2: catégorie & �?volution Temporéelle -->
        <div class="dashboard-grid">
            <div class="card">
                <h3>Nombre de Dossiers par catégorie</h3>
                <div class="chart-container" style="min-height: 300px;">
                    <canvas id="adminCategoryChart"></canvas>
                </div>
            </div>
            
            <!-- Evolution & Predictive Box -->
            <div class="card" style="display: flex; flex-direction: column;">
                <h3>�?volution & Estimation Prédictive</h3>
                <div class="chart-container" style="flex-grow: 1; min-height: 250px;">
                    <canvas id="adminEvolutionChart"></canvas>
                </div>
                
                <div class="prediction-box">
                    <h4><i class="fas fa-brain"></i> Estimation du mois prochain</h4>
                    <p>Calculé sur la tendance historique linéaire des Réclamations reçues (Moindres Carrés).</p>
                    <span class="highlight">~ <?php echo $predictedValue; ?> Réclamations prévues</span>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

