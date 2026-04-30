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
            <p class="footer-tagline"><?= e(t('footer_tagline')) ?></p>
        </div>
        <nav class="footer-nav" aria-label="Footer navigation">
            <ul>
                <li><a href="<?= e(url('index.php')) ?>"><?= e(t('home')) ?></a></li>
                <li><a href="<?= e(url('books.php')) ?>"><?= e(t('browse_books')) ?></a></li>
                <li><a href="<?= e(url('books.php?type=official')) ?>"><?= e(t('official_store')) ?></a></li>
                <li><a href="<?= e(url('books.php?type=used')) ?>"><?= e(t('used_books')) ?></a></li>
            </ul>
        </nav>
        <p class="footer-copy">&copy; <?= date('Y') ?> <?= e($siteName) ?>. <?= e(t('all_rights_reserved')) ?></p>
    </div>
</footer>

</body>
</html>
