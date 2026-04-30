-- ============================================================
-- Mkatteb - Online Bookstore & Used-Books Marketplace
-- Database Schema
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop database if exists and recreate
CREATE DATABASE IF NOT EXISTS mkatteb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mkatteb;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120)    NOT NULL,
    email       VARCHAR(180)    NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,
    role        ENUM('user','admin') NOT NULL DEFAULT 'user',
    avatar      VARCHAR(255)    NULL,
    phone       VARCHAR(30)     NULL,
    address     TEXT            NULL,
    is_blocked  TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120)    NOT NULL UNIQUE,
    slug        VARCHAR(140)    NOT NULL UNIQUE,
    description TEXT            NULL,
    icon        VARCHAR(60)     NULL COMMENT 'Emoji or icon class',
    sort_order  INT             NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: books
-- ============================================================
CREATE TABLE IF NOT EXISTS books (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id       INT UNSIGNED    NOT NULL COMMENT 'User who listed the book',
    category_id     INT UNSIGNED    NULL,
    title           VARCHAR(255)    NOT NULL,
    author          VARCHAR(255)    NOT NULL,
    description     TEXT            NULL,
    price           DECIMAL(10,3)   NOT NULL,
    stock           INT UNSIGNED    NOT NULL DEFAULT 1,
    condition_type  ENUM('new','like_new','used','damaged') NOT NULL DEFAULT 'new',
    book_type       ENUM('official','used') NOT NULL DEFAULT 'used'
                    COMMENT 'official = admin listed, used = user listed',
    cover_image     VARCHAR(255)    NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    views           INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_seller   FOREIGN KEY (seller_id)   REFERENCES users(id)       ON DELETE CASCADE,
    CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: book_images
-- ============================================================
CREATE TABLE IF NOT EXISTS book_images (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id     INT UNSIGNED    NOT NULL,
    image_path  VARCHAR(255)    NOT NULL,
    is_primary  TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_book_images_book (book_id),
    CONSTRAINT fk_book_images_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: cart_items  (session-isolated via user_id)
-- ============================================================
CREATE TABLE IF NOT EXISTS cart_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    book_id     INT UNSIGNED    NOT NULL,
    quantity    INT UNSIGNED    NOT NULL DEFAULT 1,
    added_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY uq_cart_user_book (user_id, book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: orders
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_id        INT UNSIGNED    NOT NULL,
    customer_name   VARCHAR(120)    NOT NULL,
    phone           VARCHAR(30)     NOT NULL,
    delivery_address TEXT           NOT NULL,
    notes           TEXT            NULL,
    payment_method  VARCHAR(40)     NOT NULL DEFAULT 'cash_on_delivery',
    total_price     DECIMAL(10,3)   NOT NULL,
    total_amount    DECIMAL(10,3)   NOT NULL,
    status          ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED    NOT NULL,
    book_id     INT UNSIGNED    NOT NULL,
    seller_id   INT UNSIGNED    NOT NULL,
    quantity    INT UNSIGNED    NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,3)   NOT NULL COMMENT 'Price at time of purchase',
    CONSTRAINT fk_oi_order  FOREIGN KEY (order_id)  REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_book   FOREIGN KEY (book_id)   REFERENCES books(id)  ON DELETE CASCADE,
    CONSTRAINT fk_oi_seller FOREIGN KEY (seller_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: reviews
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id     INT UNSIGNED    NOT NULL,
    user_id     INT UNSIGNED    NOT NULL,
    order_id    INT UNSIGNED    NOT NULL COMMENT 'Must have purchased to review',
    rating      TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    body        TEXT            NULL,
    is_visible  TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rev_book  FOREIGN KEY (book_id)  REFERENCES books(id)   ON DELETE CASCADE,
    CONSTRAINT fk_rev_user  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_rev_order FOREIGN KEY (order_id) REFERENCES orders(id)  ON DELETE CASCADE,
    UNIQUE KEY uq_review_user_book (user_id, book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: reports
-- ============================================================
CREATE TABLE IF NOT EXISTS reports (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id     INT UNSIGNED    NOT NULL,
    book_id         INT UNSIGNED    NULL,
    reported_user_id INT UNSIGNED   NULL,
    reason          VARCHAR(255)    NOT NULL,
    details         TEXT            NULL,
    status          ENUM('open','reviewed','resolved') NOT NULL DEFAULT 'open',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rep_reporter FOREIGN KEY (reporter_id)      REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rep_book     FOREIGN KEY (book_id)          REFERENCES books(id) ON DELETE SET NULL,
    CONSTRAINT fk_rep_reported FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    type        VARCHAR(60)     NOT NULL COMMENT 'order_status, book_sold, account_blocked, etc.',
    message     TEXT            NOT NULL,
    is_read     TINYINT(1)      NOT NULL DEFAULT 0,
    link        VARCHAR(255)    NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED: Default Categories
-- ============================================================
INSERT INTO categories (name, slug, description, icon, sort_order) VALUES
('Arabic Books',      'arabic-books',      'Books written in Arabic language',       '📚', 1),
('Novels',            'novels',            'Fiction and literary novels',             '📖', 2),
('Textbooks',         'textbooks',         'Academic and school textbooks',           '🎓', 3),
('Programming',       'programming',       'Software, coding, and tech books',        '💻', 4),
('Business',          'business',          'Business, management and finance books',  '💼', 5),
('Children',          'children',          'Books for children and young readers',    '🧸', 6),
('Science',           'science',           'Science, math, and engineering books',    '🔬', 7),
('Self-Help',         'self-help',         'Personal development and motivation',     '🌱', 8),
('History',           'history',           'History, politics, and biographies',      '🏛️', 9),
('Religion',          'religion',          'Islamic studies and religious books',     '🕌', 10);

-- ============================================================
-- SEED: Admin / Owner Account
-- Password: books
-- Hash generated with password_hash('books', PASSWORD_DEFAULT)
-- Run this in PHP to verify: password_verify('books', '$2y$10$...')
-- ============================================================
INSERT INTO users (name, email, password, role, is_blocked) VALUES (
    'Owner',
    'owner@book.io',
    '$2y$10$FNJsZrdKI2u0l9OcfllIJ.QO/B7CbgRmi19EPf36FbahIFz5FSv1y',
    'admin',
    0
) ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = 'admin',
    is_blocked = 0;

-- ============================================================
-- SEED: Sample Official Books (added by admin seller_id=1)
-- ============================================================
INSERT INTO books (seller_id, category_id, title, author, description, price, stock, condition_type, book_type) VALUES
(1, 4, 'Clean Code', 'Robert C. Martin',
 'A handbook of agile software craftsmanship. Every developer should read this.',
 9.900, 20, 'new', 'official'),
(1, 4, 'The Pragmatic Programmer', 'David Thomas & Andrew Hunt',
 'Your journey to mastery. Practical advice for programmers.',
 11.500, 15, 'new', 'official'),
(1, 2, 'The Alchemist', 'Paulo Coelho',
 'A magical story about following your dream.',
 4.500, 30, 'new', 'official'),
(1, 5, 'Zero to One', 'Peter Thiel',
 'Notes on startups, or how to build the future.',
 7.750, 10, 'new', 'official'),
(1, 8, 'Atomic Habits', 'James Clear',
 'An easy and proven way to build good habits and break bad ones.',
 8.250, 25, 'new', 'official'),
(1, 1, 'ألف ليلة وليلة', 'مجهول',
 'مجموعة من القصص الشعبية العربية الشهيرة.',
 5.500, 12, 'new', 'official');
