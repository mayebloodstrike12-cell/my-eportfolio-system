<?php
$page_title = "About Me - ANTHONY MAYER P. GUANZON";
require_once __DIR__ . '/includes/header.php';

// Fetch the main post
$post = get_post(1);
?>

<main class="site-content">
    <?php if ($post): ?>
        <article class="post-article">
            <header class="post-header">
                <h1 class="post-title">
                    <a href="index.php"><?php echo htmlspecialchars($post['title'] ?: 'About Me'); ?></a>
                </h1>
                <div class="post-meta">
                    <?php if (!empty($post['date'])): ?>
                        <span><?php echo htmlspecialchars($post['date']); ?></span>
                        <span class="meta-sep">/</span>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($post['author']); ?></span>
                </div>
            </header>

            <div class="post-body">
                <?php echo $post['content']; ?>
            </div>
        </article>
    <?php else: ?>
        <p>Post not found.</p>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
