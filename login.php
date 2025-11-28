<?php
// login.php (Legacy Support)
// Halaman ini sekarang hanya me-redirect ke dashboard utama untuk login via modal.

$redirect_to = isset($_GET['redirect']) ? $_GET['redirect'] : 'feedback.php';
header("Location: index.php?trigger_login=true&redirect=" . urlencode($redirect_to));
exit;
?>