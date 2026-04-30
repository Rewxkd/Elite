<?php

if (!function_exists('elite_footer_url')) {
    function elite_footer_url($path) {
        if (function_exists('elite_url')) {
            return elite_url($path);
        }

        return htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
    }
}
?>
<footer class="site-footer">
    <div class="site-footer-inner">
        <div class="site-footer-brand">
            <strong>Elite</strong>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Ipsam earum, necessitatibus quaerat molestias cupiditate dolore recusandae exercitationem corrupti maxime nostrum!.</p>
        </div>
        <nav class="site-footer-links" aria-label="Footer links">
            <div>
                <h2>Elite</h2>
                <a href="<?php echo elite_footer_url('index.php'); ?>">Home</a>
                <a href="<?php echo elite_footer_url('pages/games.php'); ?>">Games</a>
                <a href="<?php echo elite_footer_url('pages/favorites.php'); ?>">Favourites</a>
                <a href="<?php echo elite_footer_url('pages/recent.php'); ?>">Recent</a>
            </div>
        </nav>
    </div>
    <div class="site-footer-bottom">Copyright &copy; 2026 Elite. All Rights Reserved.</div>
</footer>
