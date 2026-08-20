<?php
// register.php
$pageTitle = "Inscription Client";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation serveur
    if (empty($firstName) || empty($lastName) || !$email || empty($phone) || empty($password)) {
        $errorMsg = "⚠️ Veuillez remplir tous les champs correctement.";
    } elseif (strlen($password) < 6) {
        $errorMsg = "⚠️ Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $confirmPassword) {
        $errorMsg = "⚠️ Les mots de passe ne correspondent pas.";
    } elseif (!preg_match('/^[0-9]{8}$/', $phone)) {
        $errorMsg = "⚠️ Le numéro de téléphone doit être composé de 8 chiffres.";
    } else {
        try {
            $db = getDBConnection();
            
            // Vérifier si l'email existe déjà
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errorMsg = "⚠️ Cette adresse email est déjà enregistrée.";
            } else {
                // Hacher le mot de passe de façon sécurisée
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insérer le nouvel utilisateur (Rôle = CLIENT)
                $insertStmt = $db->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'CLIENT', 1)");
                $insertStmt->execute([$firstName, $lastName, $email, $phone, $hashedPassword]);
                
                $userId = $db->lastInsertId();
                
                // Journaliser l'activité d'inscription
                log_activity($userId, 'INSCRIPTION', "Inscription réussie du client $firstName $lastName.");
                
                // Démarrer la session automatique
                $_SESSION['user_id'] = $userId;
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'CLIENT';
                $_SESSION['status'] = 1;
                
                // Rediriger vers le dashboard client
                header("Location: " . get_base_url() . "client/dashboard.php?welcome=1");
                exit;
            }
        } catch (PDOException $e) {
            $errorMsg = "⚠️ Erreur technique lors de l'enregistrement. Veuillez réessayer.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-container">
        <div class="auth-header">
            <div class="auth-logo"><img src="<?php echo $baseUrl; ?>assets/image.png" alt="Tunisie Telecom" style="height:70px; width:auto; object-fit:contain;"></div>
            <p class="auth-subtitle">Créer un espace réclamations client</p>
        </div>
        
        <div class="auth-body">
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger">
                    <?php echo $errorMsg; ?>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="register.php">
                <div class="auth-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">Prénom</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" placeholder="Ahmed" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name" class="form-label">Nom</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Ben Ali" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Adresse Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="ahmed.benali@exemple.tn" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Numéro de Téléphone</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="98123456" required pattern="[0-9]{8}">
                </div>
                
                <div class="auth-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Mot de Passe</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 car." required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmation</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Saisir à nouveau" required autocomplete="new-password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    <i class="fas fa-user-plus"></i> Créer mon Compte
                </button>
            </form>
        </div>
        
        <div class="auth-footer">
            Déjà inscrit ? <a href="<?php echo $baseUrl; ?>login.php">Se connecter</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

