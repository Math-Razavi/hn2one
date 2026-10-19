    </main>

    <footer class="text-center text-xs text-gray-400 py-4">
        Copyright <?php echo date('Y'); ?> | <?php echo htmlspecialchars(hn_load('settings.json')['school_short_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
    </footer>
</div>

<div id="toast-container" class="fixed bottom-6 right-6 z-[90] space-y-2"></div>

<script src="_panel.js"></script>
<script>
    initPanelSidebar();
</script>
</body>
</html>
