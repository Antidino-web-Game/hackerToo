-- HACKER TOO - Forum Database Setup
-- Exécutez ce fichier pour créer la base de données et les tables

CREATE DATABASE IF NOT EXISTS hackertoo_forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hackertoo_forum;

-- Table des catégories
CREATE TABLE IF NOT EXISTS forum_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des threads
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
    FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_updated (updated_at),
    INDEX idx_author (author)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des posts
CREATE TABLE IF NOT EXISTS forum_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    author VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE,
    INDEX idx_thread (thread_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de démonstration
INSERT INTO forum_categories (name, description) VALUES
('Général', 'Discussions générales sur l\'informatique'),
('Gaming', 'Tout sur les jeux vidéo et le gaming'),
('Hardware', 'Montage PC, périphériques et matériel'),
('Support technique', 'Besoin d\'aide ? Posez vos questions ici'),
('Programmation', 'Code, développement et scripts');

-- Exemple de thread
INSERT INTO forum_threads (category_id, title, author, is_pinned) VALUES
(1, 'Bienvenue sur Hacker Too Forum!', 'Admin', TRUE);

-- Exemple de post
INSERT INTO forum_posts (thread_id, author, content) VALUES
(1, 'Admin', 'Bienvenue sur le forum Hacker Too!\n\nIci vous pouvez discuter de tout ce qui concerne l\'informatique, le gaming, le hardware et bien plus.\n\nN\'hésitez pas à créer des threads et à participer aux discussions.\n\nBon forum à tous! 🚀');