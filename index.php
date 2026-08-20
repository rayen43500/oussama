<?php
// index.php
$pageTitle = "Accueil";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
$baseUrl = get_base_url();
?>

<!-- Style spécifique pour la page d'accueil -->
<style>
/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, rgba(0, 102, 204, 0.9) 0%, rgba(0, 51, 102, 0.95) 100%), 
                url('https://images.unsplash.com/photo-1544197150-b99a580bb7a8?q=80&w=1920&auto=format&fit=crop');
    background-size: cover;
    background-position: center;
    color: #FFFFFF;
    padding: 100px 20px;
    text-align: center;
    min-height: 80vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}
.hero-content {
    max-width: 800px;
    margin: 0 auto;
}
.hero-title {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 20px;
    line-height: 1.2;
    color: #FFFFFF;
}
.hero-subtitle {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 40px;
    font-weight: 400;
    line-height: 1.6;
}
.hero-actions {
    display: flex;
    gap: 20px;
    justify-content: center;
}

/* Feature Section */
.section-title {
    text-align: center;
    margin: 80px 0 50px 0;
}
.section-title h2 {
    font-size: 2.2rem;
    position: relative;
    padding-bottom: 15px;
    margin-bottom: 10px;
}
.section-title h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 4px;
    background-color: var(--primary-color);
    border-radius: 2px;
}
.section-title p {
    color: var(--text-muted);
    font-size: 1.1rem;
}

.features-grid {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
    padding: 0 20px;
}
.feature-card {
    background-color: var(--card-bg);
    padding: 40px 30px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    transition: var(--transition);
    text-align: center;
}
.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(0, 102, 204, 0.2);
}
.feature-icon {
    width: 70px;
    height: 70px;
    background-color: var(--primary-light);
    color: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 25px auto;
    transition: var(--transition);
}
.feature-card:hover .feature-icon {
    background-color: var(--primary-color);
    color: #FFFFFF;
}
.feature-card h3 {
    margin-bottom: 15px;
    font-size: 1.25rem;
}
.feature-card p {
    color: var(--text-muted);
    font-size: 0.95rem;
}

/* Process Section */
.process-section {
    background-color: #F8FAFC;
    padding: 80px 20px;
    margin-top: 80px;
}
.process-grid {
    max-width: 1000px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
    position: relative;
}
.process-step {
    text-align: center;
    position: relative;
    z-index: 2;
}
.process-num {
    width: 60px;
    height: 60px;
    background-color: var(--primary-color);
    color: #FFFFFF;
    font-size: 1.5rem;
    font-weight: 800;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
    box-shadow: 0 4px 10px rgba(0, 102, 204, 0.3);
}
.process-step h3 {
    margin-bottom: 10px;
    font-size: 1.15rem;
}
.process-step p {
    color: var(--text-muted);
    font-size: 0.92rem;
}

/* Contact Section */
.contact-section {
    max-width: 800px;
    margin: 80px auto;
    padding: 0 20px;
    text-align: center;
}
.contact-info {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    gap: 30px;
    margin-top: 40px;
}
.contact-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.contact-item i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

/* Footer TT */
.footer-tt {
    background-color: var(--secondary-color);
    color: #FFFFFF;
    padding: 50px 20px;
    text-align: center;
    border-top: 5px solid var(--accent-color);
}
.footer-links {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin: 20px 0;
}
.footer-links a {
    color: rgba(255, 255, 255, 0.7);
}
.footer-links a:hover {
    color: #FFFFFF;
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2.2rem;
    }
    .hero-actions {
        flex-direction: column;
        width: 100%;
        max-width: 300px;
    }
    .process-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}
</style>

<!-- Hero Section -->
<header id="accueil" class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">Votre réclamation, notre priorité</h1>
        <p class="hero-subtitle">Déposez, suivez et gérez vos réclamations facilement depuis une plateforme centralisée et intelligente.</p>
        <div class="hero-actions">
            <?php if (is_logged_in() && $_SESSION['role'] === 'CLIENT'): ?>
                <a href="<?php echo $baseUrl; ?>client/reclamation-create.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Déposer une réclamation
                </a>
                <a href="<?php echo $baseUrl; ?>client/dashboard.php" class="btn btn-outline" style="color:white; border-color:white;">
                    <i class="fas fa-desktop"></i> Mon Espace
                </a>
            <?php elseif (is_logged_in()): ?>
                <a href="<?php echo $baseUrl; ?><?php echo strtolower($_SESSION['role']); ?>/dashboard.php" class="btn btn-primary">
                    <i class="fas fa-desktop"></i> Accéder au Tableau de Bord
                </a>
            <?php else: ?>
                <a href="<?php echo $baseUrl; ?>login.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Déposer une réclamation
                </a>
                <a href="<?php echo $baseUrl; ?>login.php" class="btn btn-outline" style="color:white; border-color:white;">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- À Propos -->
<section id="a-propos" class="contact-section" style="margin-bottom: 0;">
    <div class="section-title">
        <h2>À propos de la plateforme</h2>
        <p>Tunisie Telecom s'engage à vous fournir la meilleure expérience de service possible.</p>
    </div>
    <p style="font-size: 1.1rem; line-height: 1.8; color: var(--text-main);">
        Notre portail de gestion des réclamations clients a été développé pour simplifier l'interaction entre nos abonnés et nos équipes de support. Grâce à un routage intelligent par type d'incident et à un suivi en temps réel sous forme de timeline, nous résolvons vos problèmes réseau, internet et facturation plus rapidement et en toute transparence.
    </p>
</section>

<!-- Fonctionnalités -->
<section id="fonctionnalites">
    <div class="section-title">
        <h2>Nos fonctionnalités clés</h2>
        <p>Une technologie au service d'un support réactif.</p>
    </div>
    
    <div class="features-grid">
        <!-- 1 -->
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-bolt"></i></div>
            <h3>Dépôt rapide</h3>
            <p>Formulaire clair et simplifié pour soumettre vos requêtes techniques ou administratives en quelques clics.</p>
        </div>
        <!-- 2 -->
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-history"></i></div>
            <h3>Suivi en temps réel</h3>
            <p>Visualisez chaque étape de traitement de votre incident via une timeline claire et des notifications en direct.</p>
        </div>
        <!-- 3 -->
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-user-check"></i></div>
            <h3>Traitement efficace</h3>
            <p>Assignation automatique des tickets à nos experts techniques en agence et au support niveau 2.</p>
        </div>
        <!-- 4 -->
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-brain"></i></div>
            <h3>Classification intelligente</h3>
            <p>Un analyseur de contenu intelligent suggère automatiquement la catégorie de votre incident lors de la saisie.</p>
        </div>
        <!-- 5 -->
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
            <h3>Tableau de bord</h3>
            <p>Un historique complet et clair de vos dossiers pour un contrôle total sur vos abonnements.</p>
        </div>
        <!-- 6 -->
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
            <h3>Sécurité garantie</h3>
            <p>Protection stricte de vos informations personnelles et pièces jointes grâce à nos politiques de sécurité.</p>
        </div>
    </div>
</section>

<!-- Comment ça marche -->
<section id="comment-ca-marche" class="process-section">
    <div class="section-title">
        <h2>Comment ça marche ?</h2>
        <p>Un processus simple et transparent en 3 étapes.</p>
    </div>
    
    <div class="process-grid">
        <div class="process-step">
            <div class="process-num">1</div>
            <h3>Déposer votre réclamation</h3>
            <p>Remplissez la description du problème. Notre système pré-sélectionne la catégorie.</p>
        </div>
        <div class="process-step">
            <div class="process-num">2</div>
            <h3>Suivre le traitement</h3>
            <p>Un agent spécialisé prend en charge votre dossier. Vous pouvez dialoguer en direct avec lui par message.</p>
        </div>
        <div class="process-step">
            <div class="process-num">3</div>
            <h3>Recevoir la résolution</h3>
            <p>Dès que le dysfonctionnement est corrigé, validez la clôture définitive de votre dossier.</p>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="contact-section">
    <div class="section-title">
        <h2>Contactez-nous</h2>
        <p>Besoin d'une assistance immédiate ?</p>
    </div>
    <div class="contact-info">
        <div class="contact-item">
            <i class="fas fa-phone-alt"></i>
            <strong>Téléphone Support</strong>
            <span>1298 (Gratuit depuis un numéro TT)</span>
        </div>
        <div class="contact-item">
            <i class="fas fa-envelope"></i>
            <strong>Email Support</strong>
            <span>support@tunisietelecom.tn</span>
        </div>
        <div class="contact-item">
            <i class="fas fa-map-marker-alt"></i>
            <strong>Siège Social</strong>
            <span>Rue Asdrubal, 1002 Tunis, Tunisie</span>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer-tt">
    <div class="footer-brand">
        <div style="background: rgba(255, 255, 255, 0.95); display: inline-block; padding: 8px 16px; border-radius: 8px; margin-bottom: 10px;">
            <img src="<?php echo $baseUrl; ?>assets/image.png" alt="Tunisie Telecom" style="height:45px; width:auto; object-fit:contain; display:block;">
        </div>
        <p>La vie est émotions.</p>
    </div>
    <div class="footer-links">
        <a href="#accueil">Accueil</a>
        <a href="#a-propos">À propos</a>
        <a href="#fonctionnalites">Fonctionnalités</a>
        <a href="#contact">Contact</a>
    </div>
    <p style="font-size: 0.85rem; color: rgba(255,255,255,0.5); margin-top: 30px;">
        &copy; <?php echo date('Y'); ?> Tunisie Telecom. Tous droits réservés.
    </p>
</footer>

<?php
require_once __DIR__ . '/includes/footer.php';

