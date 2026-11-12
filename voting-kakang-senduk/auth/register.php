<?php
require_once __DIR__ . '/../config/app.php';

if (current_user()) {
    redirect('../index.php');
}

$errors = [];
$nama = '';
$email = '';
$noHp = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $noHp = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || strlen($nama) > 120) $errors[] = 'Nama wajib diisi (maksimal 120 karakter).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $errors[] = 'Format email tidak valid.';
    if (!preg_match('/^[0-9+]{9,20}$/', $noHp)) $errors[] = 'Nomor HP harus berisi 9-20 digit atau tanda +.';
    if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';

    if (!$errors) {
        $check = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) $errors[] = 'Email sudah terdaftar.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (nama, email, no_hp, password, role) VALUES (?, ?, ?, ?, 'pemilih')");
        $stmt->bind_param('ssss', $nama, $email, $noHp, $hash);
        $stmt->execute();
        set_flash('Registrasi berhasil. Silakan masuk untuk mulai memilih.');
        redirect('login.php');
    }
}
render_header('Daftar');
?>
<section class="auth-wrap">
  <div class="card shadow-sm w-100" style="max-width: 500px">
    <div class="card-body p-4 p-md-5">
      <div class="eyebrow mb-2">Partisipasi masyarakat</div>
      <h1 class="h3 section-title mb-4">Buat akun pemilih</h1>
      <?php foreach ($errors as $error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endforeach; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label" for="nama">Nama lengkap</label><input class="form-control" id="nama" name="nama" maxlength="120" value="<?= e($nama) ?>" autocomplete="name" required></div>
        <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" maxlength="190" value="<?= e($email) ?>" autocomplete="email" required></div>
        <div class="mb-3"><label class="form-label" for="no_hp">Nomor HP</label><input class="form-control" id="no_hp" name="no_hp" value="<?= e($noHp) ?>" inputmode="tel" required></div>
        <div class="mb-4"><label class="form-label" for="password">Password</label><input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><div class="form-text">Minimal 8 karakter.</div></div>
        <button class="btn btn-success w-100" style="background:var(--forest)">Daftar</button>
      </form>
      <p class="text-center mt-4 mb-0">Sudah punya akun? <a href="login.php">Masuk</a></p>
    </div>
  </div>
</section>
<?php render_footer(); ?>
