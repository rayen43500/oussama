<?php
// login.php
$pageTitle = "Connexion";
$useAuthCSS = true;
$useAuthJS = true;

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Rediriger si déjà connecté
if (is_logged_in()) {
    redirect_to_dashboard($_SESSION['role']);
}

$errorMsg = '';
$successMsg = '';

// Si redirection suite à un compte désactivé
if (isset($_GET['error']) && $_GET['error'] === 'account_disabled') {
    $errorMsg = "⚠️ Votre compte est désactivé. Veuillez contacter l'administrateur.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] == 0) {
                    $errorMsg = "⚠️ Votre compte est temporairement suspendu.";
                } else {
                    // Configurer la session utilisateur
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['status'] = $user['status'];

                    // Journaliser l'activité de connexion
                    log_activity($user['id'], 'CONNEXION', 'Connexion réussie de l\'utilisateur.');

                    // Redirection
                    redirect_to_dashboard($user['role']);
                }
            } else {
                $errorMsg = "⚠️ Adresse email ou mot de passe incorrect.";
                // Éventuellement journaliser une tentative échouée pour la sécurité
                log_activity(null, 'TENTATIVE_CONNEXION_ECHOUEE', "Tentative d'accès échouée pour l'email: " . sanitize($email));
            }
        } catch (PDOException $e) {
            $errorMsg = "⚠️ Une erreur technique est survenue. Veuillez réessayer plus tard.";
        }
    } else {
        $errorMsg = "⚠️ Veuillez saisir des identifiants valides.";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-container">
        <div class="auth-header">
            <div class="auth-logo"><img src="<?php echo $baseUrl; ?>assets/image.png" alt="Tunisie Telecom" style="height:70px; width:auto; object-fit:contain;"></div>
            <p class="auth-subtitle">Gestion des réclamations clients</p>
        </div>
        
        <div class="auth-body">
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger">
                    <?php echo $errorMsg; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($successMsg): ?>
                <div class="alert alert-success">
                    <?php echo $successMsg; ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="login.php">
                <div class="form-group">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="nom@exemple.com" required autocomplete="email">
                </div>
                
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="password" class="form-label">Mot de Passe</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Se Connecter
                </button>
            </form>
        </div>
        
        <div class="auth-footer">
            Nouveau sur la plateforme ? <a href="<?php echo $baseUrl; ?>register.php">Créer un compte client</a>
            <div style="margin-top: 15px; font-size: 0.8rem; background: #f8fafc; padding: 10px; border-radius: 8px;">
                <strong>Identifiants de démo :</strong><br>
                Client: <code>client1@gmail.com</code> / <code>Client123!</code><br>
                Agent: <code>agent1@tunisietelecom.tn</code> / <code>Agent123!</code><br>
                Admin: <code>admin@tunisietelecom.tn</code> / <code>Admin123!</code>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

