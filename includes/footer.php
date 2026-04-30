<?php
/**
 * includes/footer.php
 */
$siteName = $siteName ?? 'Mkatteb';
?>
</main><!-- /main-content -->

<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <span class="logo-icon">📚</span>
            <span class="logo-text"><?= e($siteName) ?></span>
            <p class="footer-tagline">Your local bookstore &amp; used-book marketplace.</p>
        </div>
        <nav class="footer-nav" aria-label="Footer navigation">
            <ul>
                <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                <li><a href="<?= e(url('books.php')) ?>">Browse Books</a></li>
                <li><a href="<?= e(url('books.php?type=official')) ?>">Official Store</a></li>
                <li><a href="<?= e(url('books.php?type=used')) ?>">Used Books</a></li>
            </ul>
        </nav>
        <p class="footer-copy">&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
