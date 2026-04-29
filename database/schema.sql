CREATE DATABASE IF NOT EXISTS mkatteb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mkatteb;

CREATE TABLE IF NOT EXISTS books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 3) NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    cover_image VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO books (title, author, description, price, stock, cover_image, created_at)
SELECT
    'The Midnight Library',
    'Matt Haig',
    'A thoughtful novel about choices, regret, and the many directions one life can take.',
    8.500,
    18,
    'https://placehold.co/320x480/2d4059/ffffff?text=The+Midnight+Library',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM books WHERE title = 'The Midnight Library' AND author = 'Matt Haig'
);

INSERT INTO books (title, author, description, price, stock, cover_image, created_at)
SELECT
    'Atomic Habits',
    'James Clear',
    'A practical guide to building better habits through small, consistent improvements.',
    7.750,
    25,
    'https://placehold.co/320x480/4f6f52/ffffff?text=Atomic+Habits',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM books WHERE title = 'Atomic Habits' AND author = 'James Clear'
);

INSERT INTO books (title, author, description, price, stock, cover_image, created_at)
SELECT
    'The Alchemist',
    'Paulo Coelho',
    'A modern classic about following dreams, reading signs, and finding purpose.',
    5.900,
    14,
    'https://placehold.co/320x480/b15d4a/ffffff?text=The+Alchemist',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM books WHERE title = 'The Alchemist' AND author = 'Paulo Coelho'
);

INSERT INTO books (title, author, description, price, stock, cover_image, created_at)
SELECT
    'Deep Work',
    'Cal Newport',
    'A focused productivity book about creating the conditions for meaningful, high-value work.',
    6.800,
    11,
    'https://placehold.co/320x480/3d5a80/ffffff?text=Deep+Work',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM books WHERE title = 'Deep Work' AND author = 'Cal Newport'
);

INSERT INTO books (title, author, description, price, stock, cover_image, created_at)
SELECT
    'Ikigai',
    'Hector Garcia and Francesc Miralles',
    'An accessible look at longevity, purpose, and simple habits inspired by Japanese life.',
    6.250,
    20,
    'https://placehold.co/320x480/6b705c/ffffff?text=Ikigai',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM books WHERE title = 'Ikigai' AND author = 'Hector Garcia and Francesc Miralles'
);

INSERT INTO books (title, author, description, price, stock, cover_image, created_at)
SELECT
    'Dune',
    'Frank Herbert',
    'A sweeping science fiction epic of politics, ecology, power, and destiny on Arrakis.',
    9.400,
    9,
    'https://placehold.co/320x480/9c6644/ffffff?text=Dune',
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM books WHERE title = 'Dune' AND author = 'Frank Herbert'
);
