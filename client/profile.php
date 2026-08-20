<?php
// client/profile.php
$pageTitle = "Mon Profil Client";
$useDashboardCSS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('CLIENT');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$errorMsg = '';
$successMsg = '';

// Récupérer les données actuelles de l'utilisateur
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Erreur de base de données.");
}

// Traitement de la mise à jour des infos personnelles
if (isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

    if (empty($firstName) || empty($lastName) || empty($phone) || !$email) {
        $errorMsg = "⚠️ Veuillez remplir tous les champs correctement.";
    } elseif (!preg_match('/^[0-9]{8}$/', $phone)) {
        $errorMsg = "⚠️ Le numéro de téléphone doit contenir 8 chiffres.";
    } else {
        try {
            // Vérifier email unique
            $emailStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $emailStmt->execute([$email, $userId]);
            if ($emailStmt->fetch()) {
                $errorMsg = "⚠️ Cette adresse email est déjà prise par un autre compte.";
            } else {
                $update = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, email = ? WHERE id = ?");
                $update->execute([$firstName, $lastName, $phone, $email, $userId]);

                // Mettre à jour les variables de session
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['email'] = $email;

                // Actualiser l'affichage
                $user['first_name'] = $firstName;
                $user['last_name'] = $lastName;
                $user['phone'] = $phone;
                $user['email'] = $email;

                log_activity($userId, 'MISE_A_JOUR_PROFIL', "Mise à jour des informations personnelles du profil.");
                $successMsg = "✅ Vos informations ont été mises à jour avec succès.";
            }
        } catch (PDOException $e) {
            $errorMsg = "⚠️ Une erreur technique est survenue lors de la mise à jour.";
        }
    }
}

// Traitement de la mise à jour du mot de passe
if (isset($_POST['update_password'])) {
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($oldPass) || empty($newPass) || empty($confirmPass)) {
        $errorMsg = "⚠️ Veuillez remplir tous les champs de mot de passe.";
    } elseif (strlen($newPass) < 6) {
        $errorMsg = "⚠️ Le nouveau mot de passe doit contenir au moins 6 caractères.";
    } elseif ($newPass !== $confirmPass) {
        $errorMsg = "⚠️ Le nouveau mot de passe et sa confirmation ne correspondent pas.";
    } else {
        try {
            // Vérifier l'ancien mot de passe
            $passStmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $passStmt->execute([$userId]);
            $currentPass = $passStmt->fetchColumn();

            if (password_verify($oldPass, $currentPass)) {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$newHash, $userId]);

                log_activity($userId, 'MODIFICATION_MOT_DE_PASSE', "Modification sécurisée du mot de passe.");
                $successMsg = "✅ Votre mot de passe a été modifié avec succès.";
            } else {
                $errorMsg = "⚠️ L'ancien mot de passe saisi est incorrect.";
            }
        } catch (PDOException $e) {
            $errorMsg = "⚠️ Erreur technique lors du changement de mot de passe.";
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
            <a href="<?php echo $baseUrl; ?>client/reclamation-create.php" class="sidebar-link">
                <i class="fas fa-plus-circle"></i> Déposer Réclamation
            </a>
            <a href="<?php echo $baseUrl; ?>client/reclamations.php" class="sidebar-link">
                <i class="fas fa-list"></i> Mes Réclamations
            </a>
            <a href="<?php echo $baseUrl; ?>client/profile.php" class="sidebar-link active">
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
                <h2>Mon Profil Client ⚙️</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Gérez vos informations de compte et de sécurité.</p>
            </div>
        </header>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;"><?php echo $errorMsg; ?></div>
        <?php endif; ?>
        
        <?php if ($successMsg): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;"><?php echo $successMsg; ?></div>
        <?php endif; ?>

        <!-- Two Columns Profile Forms -->
        <div class="dashboard-grid">
            <!-- Information Personnelle -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-user"></i> Informations Personnelles</h3>
                
                <form action="profile.php" method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="auth-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">Prénom</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo sanitize($user['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="form-label">Nom</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo sanitize($user['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Adresse Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo sanitize($user['email']); ?>" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo sanitize($user['phone']); ?>" required pattern="[0-9]{8}">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Enregistrer les Modifications
                    </button>
                </form>
            </div>

            <!-- Changement de mot de passe -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-lock"></i> Sécurité du Compte</h3>
                
                <form action="profile.php" method="POST">
                    <input type="hidden" name="update_password" value="1">
                    
                    <div class="form-group">
                        <label for="old_password" class="form-label">Mot de passe actuel</label>
                        <div style="position: relative;">
                            <input type="password" id="old_password" name="old_password" class="form-control" required autocomplete="current-password" style="padding-right: 45px;">
                            <button type="button" class="toggle-pass-btn" onclick="togglePass('old_password', this)" title="Voir le mot de passe" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.1rem; padding: 4px;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <div style="position: relative;">
                            <input type="password" id="new_password" name="new_password" class="form-control" required autocomplete="new-password" style="padding-right: 45px;">
                            <button type="button" class="toggle-pass-btn" onclick="togglePass('new_password', this)" title="Voir le mot de passe" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.1rem; padding: 4px;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                        <div style="position: relative;">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password" style="padding-right: 45px;">
                            <button type="button" class="toggle-pass-btn" onclick="togglePass('confirm_password', this)" title="Voir le mot de passe" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 1.1rem; padding: 4px;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning" style="width: 100%;">
                        <i class="fas fa-key"></i> Mettre à jour le Mot de passe
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        btn.setAttribute('title', 'Masquer le mot de passe');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        btn.setAttribute('title', 'Voir le mot de passe');
    }
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
