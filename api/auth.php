<?php

declare(strict_types=1);

session_start();

require_once 'db_erp_user.php';

$userTable = 'erp_master_users';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

$baseRedirect = '../index.php';
$successTarget = 'feedback.php';

if (!empty($_POST['redirect_url'])) {
    $cleanUrl = basename((string) $_POST['redirect_url']);
    $allowed = ['feedback.php', 'tuning.php', 'index.php', 'report.php', 'summary_report.php'];
    if (in_array($cleanUrl, $allowed, true)) {
        $successTarget = $cleanUrl;
    }
}

$fail = function (string $msg) use ($baseRedirect, $successTarget): void {
    $query = http_build_query([
        'login_error' => $msg,
        'redirect' => $successTarget,
        'trigger_login' => 'true'
    ]);
    header("Location: {$baseRedirect}?{$query}");
    exit;
};

if ($username === '' || $password === '') {
    $fail('Username dan password wajib diisi.');
}

try {
    $stmt = $conn->prepare("SELECT user_id, username, full_name, password FROM {$userTable} WHERE username = :username LIMIT 1");
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $fail('Username tidak ditemukan.');
    }

    if (md5($password) !== $user['password']) {
        $fail('Password salah.');
    }

    session_regenerate_id(true);
    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];

    header("Location: ../{$successTarget}");
    exit;

} catch (PDOException $e) {
    error_log('Login Error: ' . $e->getMessage());
    $fail('Terjadi kesalahan sistem.');
}