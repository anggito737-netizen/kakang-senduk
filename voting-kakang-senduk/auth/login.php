<?php
require_once __DIR__ . '/../config/app.php';

if (current_user()) {
    redirect(current_user()['role'] === 'admin' ? '../admin/index.php' : '../index.php');
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $mysqli->prepare('SELECT id, nama, email, password, role FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $user['id'], 'nama' => $user['nama'], 'role' => $user['role']];
        redirect($user['role'] === 'admin' ? '../admin/index.php' : '../index.php');
    }
    $error = 'Email atau password salah.';
}
render_header('Masuk');
?>
<section class="auth-wrap">
  <div class="card shadow-sm w-100" style="max-width: 460px">
    <div class="card-body p-4 p-md-5">
      <div class="eyebrow mb-2">Selamat datang kembali</div>
      <h1 class="h3 section-title mb-4">Masuk ke akun</h1>
      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" autocomplete="email" required></div>
        <div class="mb-4"><label class="form-label" for="password">Password</label><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="btn btn-success w-100" style="background:var(--forest)">Masuk</button>
      </form>
      <p class="text-center mt-4 mb-0">Belum punya akun? <a href="register.php">Daftar sebagai pemilih</a></p>
    </div>
  </div>
</section>
<?php render_footer(); ?>
