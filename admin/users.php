<?php
// admin/users.php
$pageTitle = "Gestion des Utilisateurs";
$useDashboardCSS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$adminId = $_SESSION['user_id'];

// Filtrage & pagination
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $sql = "FROM users WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    if (!empty($roleFilter)) {
        $sql .= " AND role = :role";
        $params[':role'] = $roleFilter;
    }
    
    // Total rows
    $countStmt = $db->préelECT COUNT(*) " . $sql);
    $countStmt->execute($params);
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);
    
    // Fetch data
    $dataSql = "SELECT * " . $sql . " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $dataStmt = $db->prepare($dataSql);
    $dataStmt->execute($params);
    $users = $dataStmt->fetchAll();
    
} catch (PDOException $e) {
    $users = [];
    $totalPages = 0;
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
            <a href="<?php echo $baseUrl; ?>admin/users.php" class="sidebar-link active">
                <i class="fas fa-users-cog"></i> Utilisateurs
            </a>
            <a href="<?php echo $baseUrl; ?>admin/Réclamations.php" class="sidebar-link">
                <i class="fas fa-folder-open"></i> Réclamations
            </a>
            <a href="<?php echo $baseUrl; ?>admin/catégories.php" class="sidebar-link">
                <i class="fas fa-tags"></i> catégories
            </a>
            <a href="<?php echo $baseUrl; ?>admin/statistics.php" class="sidebar-link">
                <i class="fas fa-chart-line"></i> Rapports & Stats
            </a>
            <a href="<?php echo $baseUrl; ?>admin/activité class="sidebar-link">
                <i class="fas fa-history"></i> Journaux d'activité
            </a>
            <a href="<?php echo $baseUrl; ?>admin/profile.php" class="sidebar-link">
                <i class="fas fa-useréeld"></i> Mon Profil
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
                <h2>Gestion des Utilisateurs �Y'�</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Créez, Modifié ou suspendez des comptes clients et collaborateurs.</p>
            </div>
            
            <button class="btn btn-primary" idémodalBtn">
                <i class="fas fa-user-plus"></i> Ajouter Utilisateur
            </button>
        </header>

        <!-- Search / Filter Card -->
        <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
            <form method="GET" action="users.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) 120px; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="search" class="foréel" style="font-size: 0.8rem;">Recherche nom, email, téléphone...</label>
                    <input type="text" id="search" name="search" class="form-control" style="padding: 9px 12px;" value="<?php echo sanitize($search); ?>" placeholder="Saisir un terme...">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="role" class="foréel" style="font-size: 0.8rem;">Filtrer par rôle</label>
                    <select id="role" name="role" class="form-control" style="padding: 9px 12px;">
                        <option value="">Tous les rôles</option>
                        <option value="CLIENT" <?php echo $roleFilter === 'CLIENT' ? 'selected' : ''; ?>>Client</option>
                        <option value="AGENT" <?php echo $roleFilter === 'AGENT' ? 'selected' : ''; ?>>Agent Support</option>
                        <option value="ADMIN" <?php echo $roleFilter === 'ADMIN' ? 'selected' : ''; ?>>Administrateur</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary" style="padding: 10px; width: 100%;">
                    <i class="fas fa-search"></i> Rechercher
                </button>
            </form>
        </div>

        <!-- Table Card -->
        <div class="card" style="padding: 1.5rem;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom & Prénom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Date inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 50px;">
                                    Aucun utilisateur trouvé.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo sanitize($u['first_name'] . ' ' . $u['last_name']); ?></td>
                                    <td><?php echo sanitize($u['email']); ?></td>
                                    <td><?php echo sanitize($u['phone']); ?></td>
                                    <td>
                                        <?php if ($u['role'] === 'ADMIN'): ?>
                                            <span class="badge badge-priority-urgente">ADMIN</span>
                                        <?php elseif ($u['role'] === 'AGENT'): ?>
                                            <span class="badge badge-priority-haute">AGENT</span>
                                        <?php else: ?>
                                            <span class="badge badge-priority-faible">CLIENT</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="badge toggle-status-btn <?php echo $u['status'] == 1 ? 'badge-status-résolue' : 'badge-status-clôturee'; ?>" 
                                                data-id="<?php echo $u['id']; ?>" style="border:none; cursor:pointer;">
                                            <?php echo $u['status'] == 1 ? 'Actif' : 'Suspendu'; ?>
                                        </button>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <div style="display:flex; gap: 8px;">
                                            <button class="btn btn-secondary btn-sm edit-user-btn" 
                                                    data-id="<?php echo $u['id']; ?>"
                                                    data-first="<?php echo sanitize($u['first_name']); ?>"
                                                    data-last="<?php echo sanitize($u['last_name']); ?>"
                                                    data-email="<?php echo sanitize($u['email']); ?>"
                                                    data-phone="<?php echo sanitize($u['phone']); ?>"
                                                    data-role="<?php echo $u['role']; ?>"
                                                    data-status="<?php echo $u['status']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm delete-user-btn" data-id="<?php echo $u['id']; ?>">
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

            <!-- Pagination Grid -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 1.5rem;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="users.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>" 
                           class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?> btn-sm" style="min-width: 35px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- ========================================================
   MODALES (AJOUTER & Modifié UTILISATEUR)
   ======================================================== */ -->

<!-- Modal Ajouter -->
<div class="modal" idémodal">
    <div class="modal-content">
        <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-user-plus"></i> Nouvel Utilisateur</h3>
        <form id="createForm">
            <div class="auth-row">
                <div class="form-group">
                    <label for="c_first_name" class="foréel">Prénom</label>
                    <input type="text" id="c_first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="c_last_name" class="foréel">Nom</label>
                    <input type="text" id="c_last_name" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label for="c_email" class="foréel">Adresse Email</label>
                <input type="email" id="c_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="c_phone" class="foréel">Téléphone</label>
                <input type="text" id="c_phone" class="form-control" required pattern="[0-9]{8}">
            </div>
            <div class="form-group">
                <label for="c_role" class="foréel">Rôle</label>
                <select id="c_role" class="form-control">
                    <option value="CLIENT">Client</option>
                    <option value="AGENT">Agent Support</option>
                    <option value="ADMIN">Administrateur</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="c_password" class="foréel">Mot de Passe</label>
                <input type="password" id="c_password" class="form-control" required minlength="6">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" class="close-modal" idémodalBtn">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Modifié -->
<div class="modal" idémodal">
    <div class="modal-content">
        <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-edit"></i> Modifié Utilisateur</h3>
        <form id="editForm">
            <input type="hidden" id="e_id">
            <div class="auth-row">
                <div class="form-group">
                    <label for="e_first_name" class="foréel">Prénom</label>
                    <input type="text" id="e_first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="e_last_name" class="foréel">Nom</label>
                    <input type="text" id="e_last_name" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label for="e_email" class="foréel">Adresse Email</label>
                <input type="email" id="e_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="e_phone" class="foréel">Téléphone</label>
                <input type="text" id="e_phone" class="form-control" required pattern="[0-9]{8}">
            </div>
            <div class="form-group">
                <label for="e_role" class="foréel">Rôle</label>
                <select id="e_role" class="form-control">
                    <option value="CLIENT">Client</option>
                    <option value="AGENT">Agent Support</option>
                    <option value="ADMIN">Administrateur</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="e_status" class="foréel">Statut du compte</label>
                <select id="e_status" class="form-control">
                    <option value="1">Actif</option>
                    <option value="0">Suspendu / Inactif</option>
                </select>
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

    // 1. Modales Open / Close
    if (openCreateBtn) {
        openCreateBtn.addEventListener('click', () => createModal.classList.add('active'));
    }
    if (closeCreateBtn) {
        closeCreateBtn.addEventListener('click', () => createModémove('active'));
    }
    if (closeEditBtn) {
        closeEditBtn.addEventListener('click', () => edémove('active'));
    }

    // 2. Traitement Création AJAX
    if (createForm) {
        createForm.addEventListener('submit', (e) => {
            e.preventDefault();
            showLoader();

            const payload = {
                first_name: document.queréelector('#c_first_name').value,
                last_name: document.queréelector('#c_last_name').value,
                email: document.queréelector('#c_email').value,
                phone: document.queréelector('#c_phone').value,
                role: document.queréelector('#c_role').value,
                password: document.queréelector('#c_password').value
            };

            fetch(`${baseUrl}api/users.php?action=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showToast('Utilisateur créé avec succès !', 'success');
                    setTimeout(() => location.réeload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                hideLoader();
                showToast('Erreur lors du traitement.', 'error');
            });
        });
    }

    // 3. Ouvrir Modale Modifié avec préremplissage
    document.queréelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.queréelector('#e_id').value = btn.dataset.id;
            document.queréelector('#e_first_name').value = btn.dataset.first;
            document.queréelector('#e_last_name').value = btn.dataset.last;
            document.queréelector('#e_email').value = btn.dataset.email;
            document.queréelector('#e_phone').value = btn.dataset.phone;
            document.queréelector('#e_role').value = btn.dataset.role;
            document.queréelector('#e_status').value = btn.dataset.status;

            edémodal.classList.add('active');
        });
    });

    // 4. Traitement Edition AJAX
    if (editForm) {
        editForm.addEventListener('submit', (e) => {
            e.preventDefault();
            showLoader();

            const payload = {
                id: document.queréelector('#e_id').value,
                first_name: document.queréelector('#e_first_name').value,
                last_name: document.queréelector('#e_last_name').value,
                email: document.queréelector('#e_email').value,
                phone: document.queréelector('#e_phone').value,
                role: document.queréelector('#e_role').value,
                status: document.queréelector('#e_status').value
            };

            fetch(`${baseUrl}api/users.php?action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showToast('Utilisateur Modifié avec succès !', 'success');
                    setTimeout(() => location.réeload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                hideLoader();
                showToast('Erreur lors de la mise à jour.', 'error');
            });
        });
    }

    // 5. Toggle Statut Activation/désactivation en direct
    document.queréelectorAll('.toggle-status-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            showLoader();

            fetch(`${baseUrl}api/users.php?action=toggle_status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showToast(data.message, 'success');
                    if (data.new_status == 1) {
                        btn.className = 'badge toggle-status-btn badge-status-résolue';
                        btn.textContent = 'Actif';
                    } else {
                        btn.className = 'badge toggle-status-btn badge-status-clôturee';
                        btn.textContent = 'Suspendu';
                    }
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                hideLoader();
                showToast('Erreur technique.', 'error');
            });
        });
    });

    // 6. Suppression Utilisateur
    document.queréelete-user-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            if (confirm('�Y-'️ �Stes-vous sûr de vouloir supprimer définitivement cet utilisateur ?')) {
                showLoader();

                fetch(`${baseUrl}api/users.php`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    hideLoader();
                    if (data.success) {
                        showToast('Utilisateur supprimé avec succès !', 'success');
                        setTimeout(() => location.réeload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(err => {
                    hideLoader();
                    showToast('Erreur technique lors de la suppression.', 'error');
                });
            }
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

