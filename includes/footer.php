</div> <!-- End Container -->
<footer class="text-center py-3 mt-auto">
    <p class="mb-0 text-light">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All Rights Reserved.</p>
</footer>

<script>
// Smart Navbar - Instant hide/show with mobile touch support
(function() {
    let lastScroll = 0;
    const navbar = document.querySelector('.navbar');
    const minScroll = 10;
    let ticking = false;

    function updateNavbar() {
        const currentScroll = window.pageYOffset;

        if (currentScroll < minScroll) {
            navbar.classList.remove('navbar-hidden');
            lastScroll = currentScroll;
            return;
        }

        if (currentScroll > lastScroll) {
            navbar.classList.add('navbar-hidden');
        } else if (currentScroll < lastScroll) {
            navbar.classList.remove('navbar-hidden');
        }

        lastScroll = currentScroll;
        ticking = false;
    }

    function requestTick() {
        if (!ticking) {
            window.requestAnimationFrame(updateNavbar);
            ticking = true;
        }
    }

    // Support both scroll and touch events for mobile
    window.addEventListener('scroll', requestTick, { passive: true });
    window.addEventListener('touchmove', requestTick, { passive: true });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
