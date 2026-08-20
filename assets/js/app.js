/* assets/js/app.js */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialiser le conteneur de toast s'il n'existe pas
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }

    // 2. Gestion du menu mobile (Hamburger / Sidebar)
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });

        // Fermer la sidebar en cliquant à l'extérieur
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== menuToggle) {
                sidebar.classList.remove('active');
            }
        });
    }

    // 3. Auto-fermeture des alertes PHP statiques au bout de 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

/**
 * Affiche une notification Toast premium à l'écran
 * 
 * @param {string} message Le texte à afficher
 * @param {string} type 'success' | 'error' | 'warning' | 'info'
 */
function showToast(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Déterminer l'icône selon le type
    let iconClass = 'fa-info-circle';
    if (type === 'success') iconClass = 'fa-check-circle';
    else if (type === 'error') iconClass = 'fa-exclamation-circle';
    else if (type === 'warning') iconClass = 'fa-exclamation-triangle';

    toast.innerHTML = `
        <i class="fas ${iconClass}"></i>
        <div style="flex-grow: 1;">${message}</div>
    `;

    container.appendChild(toast);

    // Faire disparaître après 4 secondes
    setTimeout(() => {
        toast.classList.add('toast-out');
        toast.addEventListener('animationend', () => {
            toast.remove();
        });
    }, 4000);
}

/**
 * Affiche l'overlay de chargement global
 */
function showLoader() {
    let loader = document.querySelector('.loader-container');
    if (!loader) {
        loader = document.createElement('div');
        loader.className = 'loader-container';
        loader.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(loader);
    }
    loader.classList.add('active');
}

/**
 * Masque l'overlay de chargement global
 */
function hideLoader() {
    const loader = document.querySelector('.loader-container');
    if (loader) {
        loader.classList.remove('active');
    }
}
