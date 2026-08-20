<?php
// client/Réclamation-details.php
$pageTitle = "Détails de la Réclamation";
$useDashboardCSS = true;

require_once __DIR__ . '/../includes/auth.php';
require_role('CLIENT');
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    header("Location: " . get_base_url() . "client/Réclamations.php");
    exit;
}

try {
    // 1. récupérer la Réclamation et valider le propriétaire
    $stmt = $db->prepare("
        SELECT r.*, c.name as category_name, 
               a.first_name as agent_first, a.last_name as agent_last, a.email as agent_email
        FROM Réclamations r
        LEFT JOIN catégories c ON r.category_id = c.id
        LEFT JOIN users a ON r.agent_id = a.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$id, $userId]);
    $rec = $stmt->fetch();

    if (!$rec) {
        // Non trouvé ou n'appartient pas au client connecté
        header("Location: " . get_base_url() . "client/Réclamations.php");
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
            <a href="<?php echo $baseUrl; ?>client/dashboard.php" class="sidebar-link">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="<?php echo $baseUrl; ?>client/Réclamation-create.php" class="sidebar-link">
                <i class="fas fa-plus-circle"></i> Déposer Réclamation
            </a>
            <a href="<?php echo $baseUrl; ?>client/Réclamations.php" class="sidebar-link active">
                <i class="fas fa-list"></i> Mes Réclamations
            </a>
            <a href="<?php echo $baseUrl; ?>client/profile.php" class="sidebar-link">
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
                <h2>Réclamation #<?php echo $rec['id']; ?> �Y",</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Créée le <?php echo date('d/m/Y à H:i', strtotime($rec['created_at'])); ?></p>
            </div>
            
            <div style="display:flex; gap: 10px;">
                <a href="<?php echo $baseUrl; ?>client/Réclamations.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <?php if ($rec['status'] === 'résolue'): ?>
                    <button id="closeRéclamationBtn" class="btn btn-danger btn-sm">
                        <i class="fas fa-check-circle"></i> clôturer le dossier
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <!-- Main Details Grid -->
        <div class="dashboard-grid">
            <!-- Left: Info & Comments -->
            <div style="display:flex; flex-direction:column; gap:30px;">
                <!-- Details Card -->
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 1.5rem;">
                        <div>
                            <span class="badge badge-priority-<?php echo strtolower($rec['priority']); ?>" style="margin-bottom: 10px;">
                                Priorité <?php echo $rec['priority']; ?>
                            </span>
                            <span class="badge badge-status-<?php echo strtolower(str_replace(' ', '-', $rec['status'])); ?>">
                                <?php echo $rec['status']; ?>
                            </span>
                            <h3 style="margin-top: 10px; font-size: 1.4rem;"><?php echo sanitize($rec['subject']); ?></h3>
                        </div>
                    </div>

                    <div style="background-color: #F8FAFC; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                        <strong style="display:block; margin-bottom: 10px; font-size: 0.9rem; color: var(--secondary-color);">Description du problème :</strong>
                        <p style="white-space: pre-line; color: var(--text-main); font-size: 0.95rem;"><?php echo sanitize($rec['description']); ?></p>
                    </div>

                    <!-- Category & Agent -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <strong>catégorie :</strong>
                            <p style="color: var(--text-muted); margin-top: 5px;"><?php echo sanitize($rec['category_name'] ?? 'Non classifiée'); ?></p>
                        </div>
                        <div>
                            <strong>Agent assigné :</strong>
                            <p style="color: var(--text-muted); margin-top: 5px;">
                                <?php if ($rec['agent_first']): ?>
                                    <i class="fas fa-user-tie"></i> <?php echo sanitize($rec['agent_first'] . ' ' . $rec['agent_last']); ?><br>
                                    <span style="font-size: 0.8rem;"><?php echo sanitize($rec['agent_email']); ?></span>
                                <?php else: ?>
                                    <i class="fas fa-clock"></i> En attente d'assignation
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <?php if (!empty($attachments)): ?>
                        <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                            <strong>Pièces jointes :</strong>
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
                    <h3>Discussion avec le support</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Posez des questions ou apportez des précisions.</p>

                    <!-- Comments List -->
                    <div class="comments-list" id="commentsList">
                        <?php if (empty($comments)): ?>
                            <div id="no-comments" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                Aucun message dans la discussion.
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $com): ?>
                                <?php 
                                    $isMe = ($com['user_id'] == $userId);
                                    $senderName = $isMe ? "Vous" : sanitize($com['first_name'] . ' ' . $com['last_name']);
                                    $réel = $isMe ? "" : " (" . $com['role'] . ")";
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
                        <textarea id="commentText" class="form-control" rows="2" placeholder="Saisir votre message..." required></textarea>
                        <button type="submit" class="btn btn-primary" style="height: 45px;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Timeline / History -->
            <div class="card" style="height: fit-content;">
                <h3>Historique de traitement</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">Suivi des étapes d'avancement de votre dossier.</p>

                <div class="timeline">
                    <?php foreach ($history as $h): ?>
                        <div class="timeline-item <?php echo $h === end($history) ? 'active' : ''; ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></div>
                                <div class="timeline-title">
                                    <?php if ($h['old_status'] === null): ?>
                                        Dossier créé
                                    <?php else: ?>
                                        Statut Modifié : <?php echo $h['old_status']; ?> �?' <?php echo $h['new_status']; ?>
                                    <?php ?>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-desc">
                                    Par <?php echo sanitize($h['first_name'] . ' ' . $h['last_name']); ?> (<?php echo $h['role']; ?>)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
    const closeRéclamationBtn = document.querySelector('#closeRéclamationBtn');
    
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
                    // Ajouter la bulle à la liste
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
                    
                    // Vider l'input et scroll down
                    commentText.value = '';
                    commentsList.scrollTop = commentsList.scrollHeight;
                    showToast('Commentaire ajouté !', 'success');
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

    // 2. clôturer la Réclamation
    if (closeRéclamationBtn) {
        closeRéclamationBtn.addEventListener('click', () => {
            if (confirm('�Stes-vous sûr de vouloir clôturer définitivement cette Réclamation ?')) {
                showLoader();

                fetch(`${baseURéclamations.php?action=update_status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        Réclamation_id: recId,
                        status: 'clôturée'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    hideLoader();
                    if (data.success) {
                        showToast('Dossier clôturé avec succès !', 'success');
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
    }
    
    // Scroll automatique vers le bas de la discussion
    if (commentsList) {
        commentsList.scrollTop = commentsList.scrollHeight;
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

