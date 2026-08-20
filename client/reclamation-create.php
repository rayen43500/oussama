<?php
// client/Réclamation-create.php
$pageTitle = "Déposer une Réclamation";
$useDashboardCSS = true;
$useRéclamationsJS = true; // Pour la suggestion automatique en temps réel

require_once __DIR__ . '/../includes/auth.php';
require_role('CLIENT');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$errorMsg = '';
$successMsg = '';

// récupérer toutes les catégories pour le menu déroulant
try {
    $catégories = $db->query("SELECT id, name FROM catégories ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $catégories = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = intval($_POST['category_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'Moyenne');

    // Validation
    $validPriorities = ['Faible', 'Moyenne', 'Haute', 'Urgente'];
    
    if (empty($subject) || empty($description) || !$categoryId) {
        $errorMsg = "�s�️ Veuillez remplir les champs obligatoires (Sujet, Description, catégorie).";
    } elseif (!in_array($priority, $validPriorities)) {
        $errorMsg = "�s�️ Priorité invalide.";
    } else {
        try {
            // Lancer l'analyse intelligente côté serveur pour enregistrer les métadonnées IA
            $aiResult = suggest_category($description);
            
            // Insérer la Réclamation en BDD
            $stmt = $db->prepare("
                INSERT INTO Réclamations (user_id, category_id, subject, description, priority, status, ai_category, ai_confidence) 
                VALUES (?, ?, ?, ?, ?, 'Ouverte', ?, ?)
            ");
            $stmt->execute([
                $userId,
                $categoryId,
                $subject,
                $description,
                $priority,
                $aiResult['category'],
                $aiResult['confidence']
            ]);
            
            $RéclamationId = $db->lastInsertId();
            
            // Créer l'historique initial
            $histStmt = $db->prepare("INSERT INTO status_history (Réclamation_id, user_id, old_status, new_status) VALUES (?, ?, NULL, 'Ouverte')");
            $histStmt->execute([$RéclamationId, $userId]);
            
            // Traiter la pièce jointe si soumise
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = upload_attachment($_FILES['attachment'], $RéclamationId);
                if (!$uploadResult['success']) {
                    // Si échec de la pièce jointe, on l'affiche sous forme d'avertissement mais on garde la Réclamation
                    $errorMsg = "�s�️ Réclamation créée, mais erreur pièce jointe : " . $uploadResult['message'];
                }
            }
            
            // �?crire les logs d'activité
            log_activité($userId, 'CRéclamation', "Création de la Réclamation ID #$RéclamationId : " . sanitize($subject));
            
            if (empty($errorMsg)) {
                header("Location: " . get_base_url() . "client/Réclamations.php?created=1");
                exit;
            }
        } catch (PDOException $e) {
            $errorMsg = "�s�️ Une erreur technique est survenue lors de l'enregistrement de votre Réclamation.";
        }
    }
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
            <a href="<?php echo $baseUrl; ?>client/Réclamation-create.php" class="sidebar-link active">
                <i class="fas fa-plus-circle"></i> Déposer Réclamation
            </a>
            <a href="<?php echo $baseUrl; ?>client/Réclamations.php" class="sidebar-link">
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
                <h2>Déposer une nouvelle Réclamation �o�</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Expliquez votre problème technique ou de facturation.</p>
            </div>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                </div>
            </div>
        </header>

        <!-- Form Card -->
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
            <?php endif; ?>

            <form action="Réclamation-create.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="subject" class="foréel">Sujet de la Réclamation <span style="color:red;">*</span></label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="Ex: Lenteur de la connexion internet VDSL" required>
                </div>

                <div class="form-group">
                    <label for="description" class="foréel">Description détaillée <span style="color:red;">*</span></label>
                    <textarea id="description" name="description" class="form-control" rows="6" placeholder="Veuillez décrire le problème rencontré en fournissant le plus de détails possible (voyants de box, messages d'erreurs, dates, etc.)." required></textarea>
                    
                    <!-- AI Suggestion Box -->
                    <div id="ai-suggestion-box" class="card" style="display:none; background-color: #ECFDF5; border: 1.5px dashed #10B981; margin-top: 15px; padding: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">
                            <div>
                                <strong style="color: #065F46; font-size: 0.9rem;"><i class="fas fa-brain"></i> Suggestion automatique :</strong>
                                <p style="margin-top: 5px; color: #047857; font-size: 0.88rem;">
                                    catégorie probable détectée : <strong id="suggested-category-name">Internet</strong> 
                                    (Confiance : <strong id="suggested-category-confidence">92%</strong>)
                                </p>
                            </div>
                            <button type="button" id="accept-suggestion-btn" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Appliquer
                            </button>
                        </div>
                    </div>
                </div>

                <div class="auth-row">
                    <div class="form-group">
                        <label for="category_id" class="foréel">catégorie <span style="color:red;">*</span></label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="" disabled selected>Sélectionner une catégorie</option>
                            <?php foreach ($catégories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority" class="form-label">Priorité</label>
                        <select id="priority" name="priority" class="form-control">
                            <option value="Faible">Faible</option>
                            <option value="Moyenne" selected>Moyenne</option>
                            <option value="Haute">Haute</option>
                            <option value="Urgente">Urgente</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="attachment" class="foréel">Pièce jointe (optionnel) <span style="color: var(--text-muted); font-size: 0.8rem;">- Max 5 Mo (Images, PDF, Word)</span></label>
                    <input type="file" id="attachment" name="attachment" class="form-control" style="padding: 8px;">
                </div>

                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <a href="<?php echo $baseUrl; ?>client/dashboard.php" class="btn btn-outline">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Soumettre la Réclamation
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

