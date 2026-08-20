/* assets/js/auth.js */

document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.querySelector('#registerForm');
    const loginForm = document.querySelector('#loginForm');

    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            const firstName = document.querySelector('#first_name').value.trim();
            const lastName = document.querySelector('#last_name').value.trim();
            const email = document.querySelector('#email').value.trim();
            const phone = document.querySelector('#phone').value.trim();
            const password = document.querySelector('#password').value;
            const confirmPassword = document.querySelector('#confirm_password').value;

            let errors = [];

            if (!firstName || !lastName) {
                errors.push('Veuillez saisir votre nom et prénom.');
            }

            if (!email) {
                errors.push('Veuillez saisir votre adresse email.');
            } else if (!validateEmail(email)) {
                errors.push('Format d\'adresse email invalide.');
            }

            if (!phone) {
                errors.push('Veuillez saisir votre numéro de téléphone.');
            } else if (!validateTunisianPhone(phone)) {
                errors.push('Le numéro de téléphone doit contenir 8 chiffres.');
            }

            if (password.length < 6) {
                errors.push('Le mot de passe doit contenir au moins 6 caractères.');
            }

            if (password !== confirmPassword) {
                errors.push('Les mots de passe ne correspondent pas.');
            }

            if (errors.length > 0) {
                e.preventDefault();
                // Afficher le premier message d'erreur
                showToast(errors[0], 'error');
            }
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            const email = document.querySelector('#email').value.trim();
            const password = document.querySelector('#password').value;

            if (!email || !password) {
                e.preventDefault();
                showToast('Veuillez remplir tous les champs obligatoires.', 'warning');
            }
        });
    }
});

/**
 * Valide un format d'adresse email standard
 * 
 * @param {string} email 
 * @return {boolean}
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Valide un numéro de téléphone tunisien (8 chiffres)
 * 
 * @param {string} phone 
 * @return {boolean}
 */
function validateTunisianPhone(phone) {
    const re = /^[0-9]{8}$/;
    return re.test(phone);
}
