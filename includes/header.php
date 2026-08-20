<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
$baseUrl = get_base_url();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Plateforme intelligente de gestion des Réclamations clients de Tunisie Telecom. Déposez et suivez vos Réclamations.">
    <title><?php echo isset($pageTitle) ? $pageTitle . " - Tunisie Telecom" : "Tunisie Telecom - Gestion des Réclamations"; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS de base -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
    
    <!-- CSS optionnels conditionnels -->
    <?php if (isset($useAuthCSS) && $useAuthCSS): ?>
        <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/auth.css">
    <?php endif; ?>
    
    <?php if (isset($useDashboardCSS) && $useDashboardCSS): ?>
        <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/dashboard.css">
    <?php endif; ?>
    
    <!-- CSS Responsive -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/responsive.css">
    
    <!-- Chart.js via CDN si nécessaire -->
    <?php if (isset($useCharts) && $useCharts): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</head>
<body>
