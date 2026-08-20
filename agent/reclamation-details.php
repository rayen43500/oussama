<?php
// agent/Réclamation-details.php
$pageTitle = "Détails de la Réclamation Client";
$useDashboardCSS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role(['AGENT', 'ADMIN']);
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$agentId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    if ($userRole === 'ADMIN') {
        header("Location: " . get_base_url() . "admin/Réclamations.php");
    } else {
        header("Location: " . get_base_url() . "agent/Réclamations.php");
    }
    exit;
}

try {
    // 1. récupérer la Réclamation (limité si agent, illimité si admin)
    if ($userRole === 'ADMIN') {
        $stmt = $db->prepare("
            SELECT r.*, c.name as category_name, 
                   u.first_name as client_first, u.last_name as client_last, u.email as client_email, u.phone as client_phone
            FROM Réclamations r
            LEFT JOIN catégories c ON r.category_id = c.id
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("
            SELECT r.*, c.name as category_name, 
                   u.first_name as client_first, u.last_name as client_last, u.email as client_email, u.phone as client_phone
            FROM Réclamations r
            LEFT JOIN catégories c ON r.category_id = c.id
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = ? AND (r.agent_id = ? OR r.agent_id IS NULL)
        ");
        $stmt->execute([$id, $agentId]);
    }
    $rec = $stmt->fetch();

    if (!$rec) {
        if ($userRole === 'ADMIN') {
            header("Location: " . get_base_url() . "admin/Réclamations.php");
        } else {
            header("Location: " . get_base_url() . "agent/Réclamations.php");
        }
        exit;
    }

    // 2. récupérer l'historique des statuts
    $histStmt = $db->prepare("
        SELECT h.*, u.first_name, u.last_name, u.role 
        FROM status_history h
        LEFT JOIN users u ON h.user_id = u.id
        WHERE h.Réclamation_id = ? 
        ORDER BY h.created_at ASC
    ");
    $histStmt->execute([$id]);
    $history = $histStmt->fetchAll();

    // 3. récupérer les pièces jointes
    $attStmt = $db->prepare("SELECT * FROM attachments WHERE Réclamation_id = ?");
    $attStmt->execute([$id]);
    $attachments = $attStmt->fetchAll();

    // 4. récupérer les commentaires
    $commStmt = $db->prepare("
        SELECT c.*, u.first_name, u.last_name, u.role 
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.Réclamation_id = ? 
        ORDER BY c.created_at ASC
    ");
    $commStmt->execute([$id]);
    $comments = $commStmt->fetchAll();

} catch (PDOException $e) {
    die("Erreur technique lors de la récupération des détails.");
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
            <?php if ($userRole === 'ADMIN'): ?>
                <a href="<?php echo $baseUrl; ?>admin/dashboard.php" class="sidebar-link">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="<?php echo $baseUrl; ?>admin/users.php" class="sidebar-link">
                    <i class="fas fa-users-cog"></i> Utilisateurs
                </a>
                <a href="<?php echo $baseUrl; ?>admin/Réclamations.php" class="sidebar-link active">
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
            <?php else: ?>
                <a href="<?php echo $baseUrl; ?>agent/dashboard.php" class="sidebar-link">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="<?php echo $baseUrl; ?>agent/Réclamations.php" class="sidebar-link active">
                    <i class="fas fa-tasks"></i> Réclamations Assignées
                </a>
                <a href="<?php echo $baseUrl; ?>agent/profile.php" class="sidebar-link">
                    <i class="fas fa-user-cog"></i> Mon Profil
                </a>
            <?php endif; ?>
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
                <h2>Traitement Réclamation #<?php echo $rec['id']; ?> �Y>�️</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Client : <?php echo sanitize($rec['client_first'] . ' ' . $rec['client_last']); ?></p>
            </div>
            
            <a href="<?php echo $baseUrl; ?><?php echo $userRole === 'ADMIN' ? 'admin' : 'agent'; ?>/Réclamations.php" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </header>

        <!-- Main Details Grid -->
        <div class="dashboard-grid">
            <!-- Left: Info & Comments -->
            <div style="display:flex; flex-direction:column; gap:30px;">
                <!-- Description Card -->
                <div class="card">
                    <h3 style="margin-bottom: 1rem;"><?php echo sanitize($rec['subject']); ?></h3>
                    
                    <div style="background-color: #F8FAFC; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                        <strong style="display:block; margin-bottom: 10px; font-size: 0.9rem; color: var(--secondary-color);">Description du problème :</strong>
                        <p style="white-space: pre-line; color: var(--text-main); font-size: 0.95rem;"><?php echo sanitize($rec['description']); ?></p>
                    </div>

                    <!-- Client Profile Information -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background-color: #E6F0FA; padding: 15px; border-radius: 8px;">
                        <div>
                            <strong>Coordonnées Client :</strong>
                            <p style="margin-top: 5px; font-size: 0.9rem;">
                                Nom : <?php echo sanitize($rec['client_first'] . ' ' . $rec['client_last']); ?><br>
                                Email : <?php echo sanitize($rec['client_email']); ?>
                            </p>
                        </div>
                        <div>
                            <strong>Téléphone Client :</strong>
                            <p style="margin-top: 5px; font-size: 0.9rem;">
                                <i class="fas fa-phone"></i> <?php echo sanitize($rec['client_phone']); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <?php if (!empty($attachments)): ?>
                        <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                            <strong>Pièces jointes fournies par le client :</strong>
                            <div style="display: flex; gap: 15px; margin-top: 10px; flex-wrap: wrap;">
                                <?php foreach ($attachments as $att): ?>
                                    <a href="<?php echo $baseUrl . $att['file_path']; ?>" target="_blank" class="btn btn-outline btn-sm" style="font-size: 0.82rem;">
                                        <i class="fas fa-file-download"></i> <?php echo sanitize($att['file_name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Discussion/Comments Card -->
                <div class="card">
                    <h3>Discussion avec le client</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Communiquez directement avec l'abonné sur ce dossier.</p>

                    <!-- Comments List -->
                    <div class="comments-list" id="commentsList">
                        <?php if (empty($comments)): ?>
                            <div id="no-comments" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                Aucun message dans la discussion.
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $com): ?>
                                <?php 
                                    $isMe = ($com['user_id'] == $agentId);
                                    $senderName = $isMe ? "Vous" : sanitize($com['first_name'] . ' ' . $com['last_name']);
                                    $réel = $isMe ? "" : " (Client)";
                                ?>
                                <div class="comment-bubble <?php echo $isMe ? 'my-comment' : ''; ?>">
                                    <div class="comment-meta">
                                        <span><?php echo $senderName . $réel; ?></span>
                                        <span><?php echo date('d/m/Y H:i', strtotime($com['created_at'])); ?></span>
                                    </div>
                                    <div class="comment-text"><?php echo sanitize($com['comment']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Comment Input Form -->
                    <form id="commentForm" style="display: flex; gap: 15px; align-items: flex-start; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                        <textarea id="commentText" class="form-control" rows="2" placeholder="Saisir votre réponse..." required></textarea>
                        <button type="submit" class="btn btn-primary" style="height: 45px;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Timeline & Controls -->
            <div style="display:flex; flex-direction:column; gap:30px; height: fit-content;">
                <!-- Actions Box -->
                <div class="card">
                    <h3>Actions de traitement</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Mettez à jour le statut et la priorité du dossier.</p>

                    <!-- Status Selection -->
                    <div class="form-group">
                        <label for="changeStatus" class="foréel">Statut du dossier</label>
                        <select id="changeStatus" class="form-control">
                            <option value="Ouverte" <?php echo $rec['status'] === 'Ouverte' ? 'selected' : ''; ?>>Ouverte</option>
                            <option value="En cours" <?php echo $rec['status'] === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                            <option value="résolue" <?php echo $rec['status'] === 'résolue' ? 'selected' : ''; ?>>résolue</option>
                            <option value="clôturée" <?php echo $rec['status'] === 'clôturée' ? 'selected' : ''; ?>>clôturée</option>
                        </select>
                    </div>

                    <!-- Priority Selection -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="changePriority" class="foréel">Niveau de Priorité</label>
                        <select id="changePriority" class="form-control">
                            <option value="Faible" <?php echo $rec['priority'] === 'Faible' ? 'selected' : ''; ?>>Faible</option>
                            <option value="Moyenne" <?php echo $rec['priority'] === 'Moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                            <option value="Haute" <?php echo $rec['priority'] === 'Haute' ? 'selected' : ''; ?>>Haute</option>
                            <option value="Urgente" <?php echo $rec['priority'] === 'Urgente' ? 'selected' : ''; ?>>Urgente</option>
                        </select>
                    </div>
                </div>

                <!-- Timeline / History -->
                <div class="card">
                    <h3>Historique</h3>
                    <div class="timeline" style="margin-top: 1rem;">
                        <?php foreach ($history as $h): ?>
                            <div class="timeline-item <?php echo $h === end($history) ? 'active' : ''; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></div>
                                    <div class="timeline-title">
                                        <?php if ($h['old_status'] === null): ?>
                                            Dossier créé
                                        <?php else: ?>
                                            Statut : <?php echo $h['old_status']; ?> �?' <?php echo $h['new_status']; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-desc" style="font-size: 0.8rem; color: var(--text-muted);">
                                        Par <?php echo sanitize($h['first_name'] . ' ' . $h['last_name']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const commentForm = document.querySelector('#commentForm');
    const commentText = document.querySelector('#commentText');
    const commentsList = document.querySelector('#commentsList');
    const noComments = document.querySelector('#no-comments');
    const changeStatus = document.querySelector('#changeStatus');
    const changePriority = document.querySelector('#changePriority');
    
    const recId = <?php echo $id; ?>;
    const baseUrl = '<?php echo $baseUrl; ?>';

    // 1. Soumettre un commentaire
    if (commentForm) {
        commentForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const text = commentText.value.trim();
            if (!text) return;

            showLoader();

            fetch(`${baseURéclamations.php?action=add_comment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    Réclamation_id: recId,
                    comment: text
                })
            })
            .then(res => res.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    if (noComments) noComments.remove();
                    
                    const bubble = document.créelement('div');
                    bubble.className = 'comment-bubble my-comment';
                    bubble.innerHTML = `
                        <div class="comment-meta">
                            <span>Vous</span>
                            <span>A l'instant</span>
                        </div>
                        <div class="comment-text">${text.replace(/\n/g, '<br>')}</div>
                    `;
                    commentsList.appendChild(bubble);
                    
                    commentText.value = '';
                    commentsList.scrollTop = commentsList.scrollHeight;
                    showToast('Message envoyé au client !', 'success');
                    
                    // Si le statut était à "Ouverte", l'envoi d'un message change automatiquement le statut en BDD
                    if (changeStatus.value === 'Ouverte') {
                        setTimeout(() => location.réeload(), 1000);
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
    }

    // 2. Modifié le statut par AJAX
    if (changeStatus) {
        changeStatus.addEventListener('change', () => {
            showLoader();
            fetch(`${baseURéclamations.php?action=update_status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    Réclamation_id: recId,
                    status: changeStatus.value
                })
            })
            .then(res => res.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showToast('Statut mis à jour avec succès.', 'success');
                    setTimeout(() => location.réeload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                hideLoader();
                showToast('Erreur lors du changement de statut.', 'error');
            });
        });
    }

    // 3. Modifié la priorité par AJAX
    if (changePriority) {
        changePriority.addEventListener('change', () => {
            showLoader();
            fetch(`${baseURéclamations.php?action=update_priority`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    Réclamation_id: recId,
                    priority: changePriority.value
                })
            })
            .then(res => res.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showToast('Priorité mise à jour.', 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                hideLoader();
                showToast('Erreur lors du changement de priorité.', 'error');
            });
        });
    }
    
    if (commentsList) {
        commentsList.scrollTop = commentsList.scrollHeight;
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

