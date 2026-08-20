<?php
// includes/footer.php
$baseUrl = get_base_url();
?>
    <!-- Scripts Applicatifs Globaux -->
    <script src="<?php echo $baseUrl; ?>assets/js/app.js"></script>
    
    <!-- Scripts conditionnels de page -->
    <?php if (isset($useAuthJS) && $useAuthJS): ?>
        <script src="<?php echo $baseUrl; ?>assets/js/auth.js"></script>
    <?php endif; ?>
    
    <?php if (isset($useRéclamationsJS) && $useRéclamationsJS): ?>
        <script src="<?php echo $baseUrl; ?>assets/js/Réclamations.js"></script>
    <?php endif; ?>
    
    <?php if (isset($useDashboardJS) && $useDashboardJS): ?>
        <script src="<?php echo $baseUrl; ?>assets/js/dashboard.js"></script>
    <?php endif; ?>
</body>
</html>
