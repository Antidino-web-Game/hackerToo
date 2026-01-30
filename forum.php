<?php
/**
 * HACKER TOO - Simple Forum System
 * Version simplifiée d'un système de forum
 */

session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'hackertoo_forum');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion à la base de données
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonction pour créer les tables si elles n'existent pas
function initDatabase($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS forum_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS forum_threads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(100) NOT NULL,
        views INT DEFAULT 0,
        is_pinned BOOLEAN DEFAULT FALSE,
        is_locked BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS forum_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thread_id INT NOT NULL,
        author VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE
    );
    ";
    
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // Tables existent déjà ou autre erreur
    }
}

// Initialiser la base de données
initDatabase($pdo);

// Fonction pour sécuriser les entrées
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Fonction pour formater la date
function formatDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) return "À l'instant";
    if ($diff < 3600) return floor($diff / 60) . " min";
    if ($diff < 86400) return floor($diff / 3600) . "h";
    if ($diff < 604800) return floor($diff / 86400) . "j";
    
    return date('d/m/Y H:i', $timestamp);
}

// Gestion des actions
$action = $_GET['action'] ?? 'list';
$category_id = $_GET['category'] ?? null;
$thread_id = $_GET['thread'] ?? null;

// Action : Créer une catégorie
if ($action === 'create_category' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    $stmt = $pdo->prepare("INSERT INTO forum_categories (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    
    header("Location: forum.php");
    exit;
}

// Action : Créer un thread
if ($action === 'create_thread' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)$_POST['category_id'];
    $title = sanitize($_POST['title']);
    $author = sanitize($_POST['author']);
    $content = sanitize($_POST['content']);
    
    $pdo->beginTransaction();
    try {
        // Créer le thread
        $stmt = $pdo->prepare("INSERT INTO forum_threads (category_id, title, author) VALUES (?, ?, ?)");
        $stmt->execute([$category_id, $title, $author]);
        $thread_id = $pdo->lastInsertId();
        
        // Créer le premier post
        $stmt = $pdo->prepare("INSERT INTO forum_posts (thread_id, author, content) VALUES (?, ?, ?)");
        $stmt->execute([$thread_id, $author, $content]);
        
        $pdo->commit();
        header("Location: forum.php?action=view_thread&thread=" . $thread_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur : " . $e->getMessage());
    }
}

// Action : Ajouter un post
if ($action === 'add_post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $thread_id = (int)$_POST['thread_id'];
    $author = sanitize($_POST['author']);
    $content = sanitize($_POST['content']);
    
    // Vérifier si le thread est verrouillé
    $stmt = $pdo->prepare("SELECT is_locked FROM forum_threads WHERE id = ?");
    $stmt->execute([$thread_id]);
    $thread = $stmt->fetch();
    
    if (!$thread['is_locked']) {
        $stmt = $pdo->prepare("INSERT INTO forum_posts (thread_id, author, content) VALUES (?, ?, ?)");
        $stmt->execute([$thread_id, $author, $content]);
        
        // Mettre à jour la date du thread
        $stmt = $pdo->prepare("UPDATE forum_threads SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$thread_id]);
    }
    
    header("Location: forum.php?action=view_thread&thread=" . $thread_id);
    exit;
}

// Action : Incrémenter les vues
if ($action === 'view_thread' && $thread_id) {
    $stmt = $pdo->prepare("UPDATE forum_threads SET views = views + 1 WHERE id = ?");
    $stmt->execute([$thread_id]);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HACKER TOO - Forum</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #0a0e0f;
            color: #00ff41;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            background: #0f1518;
            border: 2px solid #00ff41;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        h1 {
            font-size: 2.5rem;
            color: #00ff41;
            text-shadow: 0 0 10px #00ff41;
        }

        .breadcrumb {
            margin-bottom: 20px;
            color: #7aff95;
        }

        .breadcrumb a {
            color: #00ffff;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .category, .thread {
            background: #0f1518;
            border: 1px solid #00ff41;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .category:hover, .thread:hover {
            border-color: #00ffff;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
        }

        .category-header, .thread-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-title, .thread-title {
            font-size: 1.3rem;
            color: #00ffff;
        }

        .category-desc {
            color: #7aff95;
            margin-top: 10px;
        }

        .thread-meta {
            color: #7aff95;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .post {
            background: #050707;
            border-left: 3px solid #00ff41;
            padding: 15px;
            margin: 15px 0;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #00ffff;
        }

        .post-author {
            font-weight: bold;
        }

        .post-date {
            color: #7aff95;
            font-size: 0.9rem;
        }

        .post-content {
            color: #7aff95;
            line-height: 1.6;
        }

        .btn {
            background: #0f1518;
            color: #00ff41;
            border: 2px solid #00ff41;
            padding: 10px 20px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #00ff41;
            color: #0a0e0f;
            box-shadow: 0 0 15px rgba(0, 255, 65, 0.5);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #00ffff;
        }

        input[type="text"], textarea, select {
            width: 100%;
            background: #050707;
            border: 1px solid #00ff41;
            color: #00ff41;
            padding: 10px;
            font-family: 'Courier New', monospace;
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        .stats {
            display: flex;
            gap: 20px;
            color: #7aff95;
            font-size: 0.9rem;
        }

        .badge {
            background: #00ff41;
            color: #0a0e0f;
            padding: 3px 8px;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .locked { color: #ff3333; }
        .pinned { color: #ffff00; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>⚡ HACKER TOO FORUM ⚡</h1>
        </header>

        <?php if ($action === 'list'): ?>
            <!-- Liste des catégories -->
            <div class="breadcrumb">&gt; Accueil du forum</div>
            
            <a href="?action=new_category" class="btn">+ Nouvelle catégorie</a>
            
            <?php
            $stmt = $pdo->query("
                SELECT c.*, COUNT(t.id) as thread_count
                FROM forum_categories c
                LEFT JOIN forum_threads t ON c.id = t.category_id
                GROUP BY c.id
                ORDER BY c.id DESC
            ");
            $categories = $stmt->fetchAll();
            
            foreach ($categories as $cat):
            ?>
                <div class="category">
                    <div class="category-header">
                        <a href="?action=view_category&category=<?= $cat['id'] ?>" style="text-decoration: none;">
                            <div class="category-title"><?= htmlspecialchars($cat['name']) ?></div>
                        </a>
                        <div class="stats">
                            <span><?= $cat['thread_count'] ?> threads</span>
                        </div>
                    </div>
                    <div class="category-desc"><?= htmlspecialchars($cat['description']) ?></div>
                </div>
            <?php endforeach; ?>

        <?php elseif ($action === 'new_category'): ?>
            <!-- Créer une catégorie -->
            <div class="breadcrumb">
                <a href="forum.php">&gt; Accueil</a> &gt; Nouvelle catégorie
            </div>
            
            <form method="POST" action="?action=create_category">
                <div class="form-group">
                    <label>Nom de la catégorie :</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Description :</label>
                    <textarea name="description"></textarea>
                </div>
                <button type="submit" class="btn">Créer</button>
                <a href="forum.php" class="btn">Annuler</a>
            </form>

        <?php elseif ($action === 'view_category' && $category_id): ?>
            <!-- Liste des threads d'une catégorie -->
            <?php
            $stmt = $pdo->prepare("SELECT * FROM forum_categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch();
            ?>
            
            <div class="breadcrumb">
                <a href="forum.php">&gt; Accueil</a> &gt; <?= htmlspecialchars($category['name']) ?>
            </div>
            
            <a href="?action=new_thread&category=<?= $category_id ?>" class="btn">+ Nouveau thread</a>
            
            <?php
            $stmt = $pdo->prepare("
                SELECT t.*, COUNT(p.id) as post_count
                FROM forum_threads t
                LEFT JOIN forum_posts p ON t.id = p.thread_id
                WHERE t.category_id = ?
                GROUP BY t.id
                ORDER BY t.is_pinned DESC, t.updated_at DESC
            ");
            $stmt->execute([$category_id]);
            $threads = $stmt->fetchAll();
            
            if (empty($threads)) {
                echo "<p style='color: #7aff95; margin: 20px 0;'>Aucun thread dans cette catégorie.</p>";
            }
            
            foreach ($threads as $thread):
            ?>
                <div class="thread">
                    <div class="thread-header">
                        <a href="?action=view_thread&thread=<?= $thread['id'] ?>" style="text-decoration: none;">
                            <div class="thread-title">
                                <?php if ($thread['is_pinned']): ?>
                                    <span class="pinned">📌</span>
                                <?php endif; ?>
                                <?php if ($thread['is_locked']): ?>
                                    <span class="locked">🔒</span>
                                <?php endif; ?>
                                <?= htmlspecialchars($thread['title']) ?>
                            </div>
                        </a>
                        <div class="stats">
                            <span>👁 <?= $thread['views'] ?></span>
                            <span>💬 <?= $thread['post_count'] ?></span>
                        </div>
                    </div>
                    <div class="thread-meta">
                        Par <?= htmlspecialchars($thread['author']) ?> • <?= formatDate($thread['created_at']) ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php elseif ($action === 'new_thread' && $category_id): ?>
            <!-- Créer un thread -->
            <?php
            $stmt = $pdo->prepare("SELECT * FROM forum_categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch();
            ?>
            
            <div class="breadcrumb">
                <a href="forum.php">&gt; Accueil</a> &gt; 
                <a href="?action=view_category&category=<?= $category_id ?>"><?= htmlspecialchars($category['name']) ?></a> &gt;
                Nouveau thread
            </div>
            
            <form method="POST" action="?action=create_thread">
                <input type="hidden" name="category_id" value="<?= $category_id ?>">
                <div class="form-group">
                    <label>Titre :</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Auteur :</label>
                    <input type="text" name="author" required>
                </div>
                <div class="form-group">
                    <label>Message :</label>
                    <textarea name="content" required></textarea>
                </div>
                <button type="submit" class="btn">Créer</button>
                <a href="?action=view_category&category=<?= $category_id ?>" class="btn">Annuler</a>
            </form>

        <?php elseif ($action === 'view_thread' && $thread_id): ?>
            <!-- Voir un thread et ses posts -->
            <?php
            require_once 'thread_template.php';
            displayThread($pdo, $thread_id);
            ?>

        <?php endif; ?>
    </div>
</body>
</html>