<?php
require_once __DIR__ . '/koneksi.php';

$appRoot = realpath(dirname(__DIR__));
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$appBasePath = '';
$basePathResolved = false;
if ($appRoot && $documentRoot) {
    $normalizedAppRoot = rtrim(str_replace('\\', '/', $appRoot), '/');
    $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
    if (strcasecmp($normalizedAppRoot, $normalizedDocumentRoot) === 0) {
        $basePathResolved = true;
    } elseif (stripos($normalizedAppRoot, $normalizedDocumentRoot . '/') === 0) {
        $appBasePath = substr($normalizedAppRoot, strlen($normalizedDocumentRoot));
        $basePathResolved = true;
    }
}
if (!$basePathResolved) {
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    foreach (['/auth/', '/pages/', '/admin/', '/proses/'] as $directory) {
        $position = strpos($scriptPath, $directory);
        if ($position !== false) {
            $appBasePath = substr($scriptPath, 0, $position);
            $basePathResolved = true;
            break;
        }
    }
    if (!$basePathResolved && $scriptPath !== '') {
        $appBasePath = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');
    }
}

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name('voting_kakang_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function app_url($path = '')
{
    global $appBasePath;
    return rtrim($appBasePath, '/') . '/' . ltrim($path, '/');
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf()
{
    $provided = $_POST['csrf_token'] ?? '';
    if (!is_string($provided) || !hash_equals(csrf_token(), $provided)) {
        http_response_code(419);
        exit('Permintaan tidak valid atau sesi telah kedaluwarsa. Silakan muat ulang halaman.');
    }
}

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_login()
{
    if (!current_user()) {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Silakan masuk terlebih dahulu.'];
        redirect(app_url('auth/login.php'));
    }
}

function require_role($role)
{
    require_login();
    if (current_user()['role'] !== $role) {
        http_response_code(403);
        exit('Anda tidak memiliki akses ke halaman ini.');
    }
}

function flash_message()
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function set_flash($message, $type = 'success')
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function active_period($mysqli)
{
    $result = $mysqli->query(
        "SELECT * FROM periode_voting
         WHERE status = 'Aktif' AND tanggal_mulai <= NOW() AND tanggal_selesai >= NOW()
         ORDER BY id DESC LIMIT 1"
    );
    return $result->fetch_assoc() ?: null;
}

function format_rupiah($amount)
{
    return 'Rp ' . number_format((int) $amount, 0, ',', '.');
}

function render_header($title, $active = '')
{
    $user = current_user();
    $flash = flash_message();
    ?>
    <!doctype html>
    <html lang="id">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="theme-color" content="#183c35">
      <title><?= e($title) ?> | Kakang Senduk Ponorogo</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
    </head>
    <body>
    <nav class="navbar navbar-expand-lg navbar-dark site-nav sticky-top">
      <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e(app_url('index.php')) ?>"><span class="brand-mark">KS</span> Kakang Senduk</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Buka navigasi"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
            <li class="nav-item"><a class="nav-link <?= $active === 'home' ? 'active' : '' ?>" href="<?= e(app_url('index.php')) ?>">Beranda</a></li>
            <li class="nav-item"><a class="nav-link <?= $active === 'kontestan' ? 'active' : '' ?>" href="<?= e(app_url('pages/kontestan.php')) ?>">Kontestan</a></li>
            <li class="nav-item"><a class="nav-link <?= $active === 'klasemen' ? 'active' : '' ?>" href="<?= e(app_url('pages/klasemen.php')) ?>">Klasemen</a></li>
            <?php if ($user && $user['role'] === 'pemilih'): ?>
              <li class="nav-item"><a class="nav-link <?= $active === 'voting' ? 'active' : '' ?>" href="<?= e(app_url('pages/voting.php')) ?>">Voting</a></li>
            <?php endif; ?>
            <?php if ($user && $user['role'] === 'admin'): ?>
              <li class="nav-item"><a class="nav-link" href="<?= e(app_url('admin/index.php')) ?>">Panel Admin</a></li>
            <?php endif; ?>
            <?php if ($user): ?>
              <li class="nav-item ms-lg-2"><span class="nav-link small text-white-50"><?= e($user['nama']) ?></span></li>
              <li class="nav-item"><a class="btn btn-sm btn-outline-light ms-lg-2" href="<?= e(app_url('auth/logout.php')) ?>">Keluar</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="<?= e(app_url('auth/login.php')) ?>">Masuk</a></li>
              <li class="nav-item"><a class="btn btn-sm btn-gold ms-lg-2" href="<?= e(app_url('auth/register.php')) ?>">Daftar</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
    <main>
    <?php if ($flash): ?>
      <div class="container pt-3"><div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div></div>
    <?php endif; ?>
    <?php
}

function render_footer()
{
    ?>
    </main>
    <footer class="site-footer py-4 mt-5">
      <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
        <span>© <?= date('Y') ?> Pemilihan Kakang Senduk Ponorogo</span>
        <span>Merayakan budaya, talenta, dan pariwisata Ponorogo.</span>
      </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
    </body>
    </html>
    <?php
}
