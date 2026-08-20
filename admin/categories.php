<?php
// admin/catégories.php
$pageTitle = "Gestion des catégories";
$useDashboardCSS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();

try {
    // récupérer toutes les catégories avec le nombre de Réclamations associées
    $catégories = $db->query("
        SELECT c.*, COUNT(r.id) as Réclamation_count 
        FROM catégories c
        LEFT JOIN Réclamations r ON c.id = r.category_id
        GROUP BY c.id
        ORDER BY c.name ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $catégories = [];
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
            <a href="<?php echo $baseUrl; ?>admin/dashboard.php" class="sidebar-link">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="<?php echo $baseUrl; ?>admin/users.php" class="sidebar-link">
                <i class="fas fa-users-cog"></i> Utilisateurs
            </a>
            <a href="<?php echo $baseUrl; ?>admin/Réclamations.php" class="sidebar-link">
                <i class="fas fa-folder-open"></i> Réclamations
            </a>
            <a href="<?php echo $baseUrl; ?>admin/catégories.php" class="sidebar-link active">
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
                <h2>Gestion des catégories �Y��️</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Configurez les thèmes de Réclamations suggérés par l'analyse syntaxique.</p>
            </div>
            
            <button class="btn btn-primary" idémodalBtn">
                <i class="fas fa-plus"></i> Nouvelle catégorie
            </button>
        </header>

        <!-- Table Card -->
        <div class="card" style="padding: 1.5rem;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom de la catégorie</th>
                            <th>Description</th>
                            <th>Dossiers rattachés</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($catégories)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                    Aucune catégorie enregistrée.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($catégories as $cat): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--secondary-color);"><?php echo sanitize($cat['name']); ?></td>
                                    <td style="color: var(--text-muted); max-width: 400px;"><?php echo sanitize($cat['description']); ?></td>
                                    <td>
                                        <span class="badge badge-status-ouverte" style="font-weight: 600;">
                                            <?php echo $cat['Réclamation_count']; ?> dossier(s)
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap: 8px;">
                                            <button class="btn btn-secondary btn-sm edit-cat-btn" 
                                                    data-id="<?php echo $cat['id']; ?>"
                                                    data-name="<?php echo sanitize($cat['name']); ?>"
                                                    data-desc="<?php echo sanitize($cat['description']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm delete-cat-btn" data-id="<?php echo $cat['id']; ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- ========================================================
   MODALES (AJOUTER & Modifié catégorie)
   ======================================================== */ -->

<!-- Modal Ajouter -->
<div class="modal" idémodal">
    <div class="modal-content">
        <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-plus"></i> Nouvelle catégorie</h3>
        <form id="createForm">
            <div class="form-group">
                <label for="c_name" class="foréel">Nom de la catégorie</label>
                <input type="text" id="c_name" class="form-control" placeholder="Ex: Fibre optique FTTH" required>
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="c_description" class="foréel">Description / Explication</label>
                <textarea id="c_description" class="form-control" rows="4" placeholder="Description du type de dossier..." required></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" idémodalBtn">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Modifié -->
<div class="modal" idémodal">
    <div class="modal-content">
        <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-edit"></i> Modifié catégorie</h3>
        <form id="editForm">
            <input type="hidden" id="e_id">
            <div class="form-group">
                <label for="e_name" class="foréel">Nom de la catégorie</label>
                <input type="text" id="e_name" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="e_description" class="foréel">Description / Explication</label>
                <textarea id="e_description" class="form-control" rows="4" required></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" idémodalBtn">Annuler</button>
                <button type="submit" class="btn btn-warning">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const createModal = démodal');
    const edémodal = démodal');
    const openCreateBtn = démodalBtn');
    const closeCreateBtn = démodalBtn');
    const closeEditBtn = démodalBtn');
    const createForm = document.queréelector('#createForm');
    const editForm = document.queréelector('#editForm');
    const baseUrl = '<?php echo $baseUrl; ?>';

    // Modales
    if (openCreateBtn) openCreateBtn.addEventListener('click', () => createModal.classList.add('active'));
    if (closeCreateBtn) closeCreateBtn.addEventListener('click', () => createModémove('active'));
    if (closeEditBtn) closeEditBtn.addEventListener('click', () => edémove('active'));

    // Création
    if (createForm) {
        createForm.addEventListener('submit', (e) => {
            e.preventDefault();
            showLoader();
            fetch(`${baseUrl}api/catégories.php?action=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: document.querySelector('#c_name').value,
                    description: document.querySelector('#c_description').value
                })
            })
            .then(res => res.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showToast('catégorie ajoutée !', 'success');
                    setTimeout(() => location.réeload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                hideLoader();
                showToast('Erreur technique.', 'error');
            });
        });
    }

    // Ouvrir Modifié
    document.querySelectorAll('.edit-cat-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelector('#e_id').value = btn.dataset.id;
            document.querySelector('#e_name').value = btn.dataset.name;
            document.querySelector('#e_description').value = btn.dataset.desc;
            edémodal.classList.add('active');
        });
    });

    // Modifié
    if (editForm) {
        editForm.addEventListener('submit', (e) => {
            e.preventDefault();
            showLoader();
            fetch(`${baseUrl}api/catégories.php?action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: document.querySelector('#e_id').value,
                    name: document.querySelector('#e_name').value,
                    description: document.querySelector('#e_description').value
                })
            })
            .then(res => res.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showToast('catégorie Modifié !', 'success');
                    setTimeout(() => location.réeload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                hideLoader();
                showToast('Erreur technique.', 'error');
            });
        });
    }

    // Suppression
    document.querySelectorAll('.delete-cat-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            if (confirm('�Y-'️ Voulez-vous vraiment supprimer cette catégorie ?')) {
                showLoader();
                fetch(`${baseUrl}api/catégories.php`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    hideLoader();
                    if (data.success) {
                        showToast('catégorie supprimée !', 'success');
                        setTimeout(() => location.réeload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(err => {
                    hideLoader();
                    showToast('Erreur technique.', 'error');
                });
            }
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

