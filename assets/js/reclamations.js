/* assets/js/reclamations.js */

document.addEventListener('DOMContentLoaded', () => {
    const descriptionTextarea = document.querySelector('#description');
    const categorySelect = document.querySelector('#category_id');
    const suggestionBox = document.querySelector('#ai-suggestion-box');
    const suggestedCategoryName = document.querySelector('#suggested-category-name');
    const suggestedCategoryConfidence = document.querySelector('#suggested-category-confidence');
    const acceptSuggestionBtn = document.querySelector('#accept-suggestion-btn');
    
    // Pour stocker l'ID de la catégorie suggérée
    let suggestedCategoryId = null;

    if (descriptionTextarea && categorySelect && suggestionBox) {
        // Debounce timer
        let debounceTimer;

        descriptionTextarea.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const text = descriptionTextarea.value.trim();

            if (text.length < 10) {
                suggestionBox.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                // Obtenir l'URL de base dynamique
                const baseUrl = getBaseUrl();
                
                fetch(`${baseUrl}api/reclamations.php?action=suggest`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ description: text })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.category_id && data.category !== 'Autre') {
                        suggestedCategoryId = data.category_id;
                        suggestedCategoryName.textContent = data.category;
                        suggestedCategoryConfidence.textContent = Math.round(data.confidence * 100) + '%';
                        
                        suggestionBox.style.display = 'block';
                    } else {
                        suggestionBox.style.display = 'none';
                    }
                })
                .catch(err => {
                    console.error('Erreur de suggestion :', err);
                });
            }, 500);
        });

        if (acceptSuggestionBtn) {
            acceptSuggestionBtn.addEventListener('click', () => {
                if (suggestedCategoryId) {
                    categorySelect.value = suggestedCategoryId;
                    suggestionBox.style.display = 'none';
                    showToast('Catégorie mise à jour avec succès.', 'success');
                }
            });
        }
    }
});

/**
 * Récupère le chemin racine relatif à partir des scripts inclus
 * 
 * @return {string}
 */
function getBaseUrl() {
    // Parcourt les scripts chargés pour trouver le bon chemin vers la racine
    const script = document.querySelector('script[src*="reclamations.js"]');
    if (script) {
        const src = script.getAttribute('src');
        // Si le script est dans assets/js/reclamations.js, la racine est de 2 niveaux au-dessus
        return src.replace('assets/js/reclamations.js', '');
    }
    return '/reclamation-tt/';
}
