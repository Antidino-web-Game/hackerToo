<?php
/**
 * HACKER TOO - Thread Template
 * Template pour afficher un thread et ses posts
 */

function displayThread($pdo, $thread_id) {
    // Récupérer les informations du thread
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name, c.id as category_id
        FROM forum_threads t
        JOIN forum_categories c ON t.category_id = c.id
        WHERE t.id = ?
    ");
    $stmt->execute([$thread_id]);
    $thread = $stmt->fetch();
    
    if (!$thread) {
        echo "<p style='color: #ff3333;'>Thread introuvable.</p>";
        return;
    }
    
    // Récupérer tous les posts du thread
    $stmt = $pdo->prepare("
        SELECT * FROM forum_posts
        WHERE thread_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$thread_id]);
    $posts = $stmt->fetchAll();
    
    ?>
    
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="forum.php">&gt; Accueil</a> &gt; 
        <a href="?action=view_category&category=<?= $thread['category_id'] ?>"><?= htmlspecialchars($thread['category_name']) ?></a> &gt;
        <?= htmlspecialchars($thread['title']) ?>
    </div>
    
    <!-- Thread header -->
    <div style="background: #0f1518; border: 2px solid #00ff41; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #00ffff; margin-bottom: 10px;">
            <?php if ($thread['is_pinned']): ?>
                <span style="color: #ffff00;">📌</span>
            <?php endif; ?>
            <?php if ($thread['is_locked']): ?>
                <span style="color: #ff3333;">🔒</span>
            <?php endif; ?>
            <?= htmlspecialchars($thread['title']) ?>
        </h2>
        <div style="color: #7aff95;">
            Par <strong><?= htmlspecialchars($thread['author']) ?></strong> • 
            <?= formatDate($thread['created_at']) ?> • 
            👁 <?= $thread['views'] ?> vues • 
            💬 <?= count($posts) ?> posts
        </div>
        <?php if ($thread['is_locked']): ?>
            <div style="color: #ff3333; margin-top: 10px;">
                ⚠ Ce thread est verrouillé, vous ne pouvez plus répondre.
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Liste des posts -->
    <div style="margin-bottom: 30px;">
        <?php foreach ($posts as $index => $post): ?>
            <div class="post" id="post-<?= $post['id'] ?>">
                <div class="post-header">
                    <div class="post-author">
                        <span style="color: #00ffff;">@<?= htmlspecialchars($post['author']) ?></span>
                        <?php if ($index === 0): ?>
                            <span class="badge">OP</span>
                        <?php endif; ?>
                    </div>
                    <div class="post-date">
                        #<?= $index + 1 ?> • <?= formatDate($post['created_at']) ?>
                    </div>
                </div>
                <div class="post-content">
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Formulaire de réponse -->
    <?php if (!$thread['is_locked']): ?>
        <div style="background: #0f1518; border: 2px solid #00ff41; padding: 20px;">
            <h3 style="color: #00ffff; margin-bottom: 15px;">💬 Répondre à ce thread</h3>
            <form method="POST" action="?action=add_post">
                <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                <div class="form-group">
                    <label>Nom d'utilisateur :</label>
                    <input type="text" name="author" required placeholder="Votre pseudo">
                </div>
                <div class="form-group">
                    <label>Message :</label>
                    <textarea name="content" required placeholder="Votre réponse..."></textarea>
                </div>
                <button type="submit" class="btn">📤 Envoyer</button>
                <a href="?action=view_category&category=<?= $thread['category_id'] ?>" class="btn">← Retour</a>
            </form>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 20px; color: #7aff95;">
            <a href="?action=view_category&category=<?= $thread['category_id'] ?>" class="btn">← Retour à la catégorie</a>
        </div>
    <?php endif; ?>
    
    <?php
}

/**
 * Fonction alternative pour afficher un thread en format compact
 */
function displayThreadCompact($pdo, $thread_id) {
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name, COUNT(p.id) as post_count
        FROM forum_threads t
        JOIN forum_categories c ON t.category_id = c.id
        LEFT JOIN forum_posts p ON t.id = p.thread_id
        WHERE t.id = ?
        GROUP BY t.id
    ");
    $stmt->execute([$thread_id]);
    $thread = $stmt->fetch();
    
    if (!$thread) return;
    
    ?>
    <div class="thread-compact" style="background: #0f1518; border-left: 4px solid #00ff41; padding: 15px; margin: 10px 0;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <a href="?action=view_thread&thread=<?= $thread['id'] ?>" style="color: #00ffff; text-decoration: none; font-weight: bold;">
                    <?= htmlspecialchars($thread['title']) ?>
                </a>
                <div style="color: #7aff95; font-size: 0.9rem; margin-top: 5px;">
                    <?= htmlspecialchars($thread['category_name']) ?> • Par <?= htmlspecialchars($thread['author']) ?>
                </div>
            </div>
            <div style="text-align: right; color: #7aff95; font-size: 0.9rem;">
                <div>💬 <?= $thread['post_count'] ?> posts</div>
                <div>👁 <?= $thread['views'] ?> vues</div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Fonction pour afficher les derniers threads (widget)
 */
function displayRecentThreads($pdo, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name
        FROM forum_threads t
        JOIN forum_categories c ON t.category_id = c.id
        ORDER BY t.updated_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $threads = $stmt->fetchAll();
    
    ?>
    <div style="background: #0f1518; border: 2px solid #00ff41; padding: 20px; margin: 20px 0;">
        <h3 style="color: #00ffff; margin-bottom: 15px;">🔥 Dernières discussions</h3>
        <?php foreach ($threads as $thread): ?>
            <div style="border-bottom: 1px solid #00ff41; padding: 10px 0;">
                <a href="?action=view_thread&thread=<?= $thread['id'] ?>" style="color: #00ff41; text-decoration: none;">
                    <?= htmlspecialchars($thread['title']) ?>
                </a>
                <div style="color: #7aff95; font-size: 0.85rem; margin-top: 5px;">
                    <?= htmlspecialchars($thread['category_name']) ?> • <?= formatDate($thread['updated_at']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Fonction pour afficher les statistiques du forum
 */
function displayForumStats($pdo) {
    // Total des threads
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM forum_threads");
    $thread_count = $stmt->fetch()['count'];
    
    // Total des posts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM forum_posts");
    $post_count = $stmt->fetch()['count'];
    
    // Total des catégories
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM forum_categories");
    $category_count = $stmt->fetch()['count'];
    
    ?>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
        <div style="background: #0f1518; border: 2px solid #00ff41; padding: 20px; text-align: center;">
            <div style="color: #00ffff; font-size: 2rem; font-weight: bold;"><?= $category_count ?></div>
            <div style="color: #7aff95;">Catégories</div>
        </div>
        <div style="background: #0f1518; border: 2px solid #00ff41; padding: 20px; text-align: center;">
            <div style="color: #00ffff; font-size: 2rem; font-weight: bold;"><?= $thread_count ?></div>
            <div style="color: #7aff95;">Threads</div>
        </div>
        <div style="background: #0f1518; border: 2px solid #00ff41; padding: 20px; text-align: center;">
            <div style="color: #00ffff; font-size: 2rem; font-weight: bold;"><?= $post_count ?></div>
            <div style="color: #7aff95;">Messages</div>
        </div>
    </div>
    <?php
}

/**
 * Fonction pour rechercher dans le forum
 */
function searchForum($pdo, $query) {
    $search_term = "%$query%";
    
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name, COUNT(p.id) as post_count
        FROM forum_threads t
        JOIN forum_categories c ON t.category_id = c.id
        LEFT JOIN forum_posts p ON t.id = p.thread_id
        WHERE t.title LIKE ? OR t.author LIKE ?
        GROUP BY t.id
        ORDER BY t.updated_at DESC
    ");
    $stmt->execute([$search_term, $search_term]);
    
    return $stmt->fetchAll();
}
?>