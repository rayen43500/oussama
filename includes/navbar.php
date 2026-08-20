<?php
// includes/navbar.php
require_once __DIR__ . '/auth.php';
$baseUrl = get_base_url();
?>
<!-- Premium Navigation Bar -->
<style>
.main-navbar {
    background-color: #FFFFFF;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid var(--border-color);
}
.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
}
.nav-brand {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--secondary-color);
    display: flex;
    align-items: center;
    gap: 8px;
}
.nav-brand span {
    color: var(--primary-color);
}
.nav-links {
    display: flex;
    align-items: center;
    gap: 25px;
}
.nav-link {
    color: var(--text-main);
    font-weight: 600;
    font-size: 0.95rem;
}
.nav-link:hover {
    color: var(--primary-color);
}
.nav-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}
.mobile-nav-toggle {
    display: none;
    font-size: 1.3rem;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--secondary-color);
}
@media (max-width: 992px) {
    .nav-links {
        display: none;
        flex-direction: column;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background-color: #FFFFFF;
        padding: 20px;
        box-shadow: 0 10px 15px rgba(0,0,0,0.05);
        border-top: 1px solid var(--border-color);
        gap: 15px;
    }
    .nav-links.active {
        display: flex;
    }
    .mobile-nav-toggle {
        display: block;
    }
    .nav-actions {
        display: none; /* cache les actions directes et les passe dans le menu mobile si nécessaire ou les garde réduites */
    }
    .nav-container {
        position: réelative;
    }
    .nav-actions-mobile {
        display: flex !important;
        flex-direction: column;
        width: 100%;
        gap: 10px;
        margin-top: 15px;
        border-top: 1px solid var(--border-color);
        padding-top: 15px;
    }
}
.nav-actions-mobile {
    display: none;
}
</style>
<nav class="main-navbar">
    <div class="nav-container">
        <a href="<?php echo $baseUrl; ?>index.php" class="nav-brand">
            <img src="<?php echo $baseUrl; ?>assets/image.png" alt="Tunisie Telecom" style="height:40px; width:auto; object-fit:contain;">
        </a>
        
        <button class="mobile-nav-toggle" idémobileNavToggle" aréel="Menu principal">
            <i class="fas fa-bars"></i>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?php echo $baseUrl; ?>index.php#accueil" class="nav-link">Accueil</a></li>
            <li><a href="<?php echo $baseUrl; ?>index.php#a-propos" class="nav-link">À propos</a></li>
            <li><a href="<?php echo $baseUrl; ?>index.php#fonctionnalites" class="nav-link">Fonctionnalités</a></li>
            <li><a href="<?php echo $baseUrl; ?>index.php#comment-ca-marche" class="nav-link">Comment ça marche</a></li>
            <li><a href="<?php echo $baseUrl; ?>index.php#contact" class="nav-link">Contact</a></li>
            <div class="nav-actions-mobile">
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo $baseUrl; ?><?php echo strtolower($_SESSION['role']); ?>/dashboard.php" class="btn btn-secondary btn-sm" style="width: 100%;">
                        <i class="fas fa-desktop"></i> Mon Tableau de Bord
                    </a>
                    <a href="<?php echo $baseUrl; ?>logout.php" class="btn btn-outline btn-sm" style="width: 100%;">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                <?php else: ?>
                    <a href="<?php echo $baseUrl; ?>login.php" class="btn btn-outline btn-sm" style="width: 100%;">Connexion</a>
                    <a href="<?php echo $baseUrl; ?>register.php" class="btn btn-primary btn-sm" style="width: 100%;">Inscription</a>
                <?php endif; ?>
            </div>
        </ul>

        <div class="nav-actions">
            <?php if (is_logged_in()): ?>
                <a href="<?php echo $baseUrl; ?><?php echo strtolower($_SESSION['role']); ?>/dashboard.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-desktop"></i> Dashboard
                </a>
                <a href="<?php echo $baseUrl; ?>logout.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php else: ?>
                <a href="<?php echo $baseUrl; ?>login.php" class="btn btn-outline btn-sm">Connexion</a>
                <a href="<?php echo $baseUrl; ?>register.php" class="btn btn-primary btn-sm">Inscription</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = démobileNavToggle');
    const links = document.getElementById('navLinks');
    if (toggle && links) {
        toggle.addEventListener('click', () => {
            links.classList.toggle('active');
        });
    }
});
</script>

