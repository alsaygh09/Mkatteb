<?php

require_once __DIR__ . '/db.php';

function getFeaturedBooks(int $limit = 6): array
{
    $limit = max(1, min($limit, 12));
    $pdo = getDatabaseConnection();

    $statement = $pdo->prepare(
        'SELECT id, title, author, description, price, stock, cover_image, created_at
         FROM books
         WHERE stock > 0
         ORDER BY created_at DESC, id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}
