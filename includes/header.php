<?php

$pageTitle = $pageTitle ?? 'Mkatteb';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Main navigation">
            <a class="brand" href="index.php">Mkatteb</a>
            <a href="index.php">Home</a>
        </nav>
    </header>

    <main class="page">
