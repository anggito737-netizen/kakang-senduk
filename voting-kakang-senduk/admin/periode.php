<?php
require_once __DIR__ . '/../config/app.php';
require_role('admin');
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = $_POST['aksi'] ?? '';
    if ($action === 'buat') {
        $name = trim($_POST['nama_periode'] ?? '');
        $startRaw = $_POST['tanggal_mulai'] ?? '';
        $endRaw = $_POST['tanggal_selesai'] ?? '';
        $start = DateTime::createFromFormat('Y-m-d\TH:i', $startRaw);
        $end = DateTime::createFromFormat('Y-m-d\TH:i', $endRaw);
        if ($name === '' || strlen($name) > 120) $errors[] = 'Nama periode wajib diisi (maksimal 120 karakter).';
        if (!$start || !$end || $start >= $end) $errors[] = 'Tanggal mulai dan selesai tidak valid; tanggal selesai harus setelah tanggal mulai.';
        if (!$errors) {
            $startValue = $start->format('Y-m-d H:i:s');
            $endValue = $end->format('Y-m-d H:i:s');
            $stmt = $mysqli->prepare("INSERT INTO periode_voting (nama_periode, tanggal_mulai, tanggal_selesai, status) VALUES (?, ?, ?, 'Nonaktif')");
            $stmt->bind_param('sss', $name, $startValue, $endValue);
            $stmt->execute();
            set_flash('Periode voting berhasil dibuat. Aktifkan saat jadwalnya siap.');
            redirect('periode.php');
        }
    } elseif ($action === 'aktifkan') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $stmt = $mysqli->prepare('SELECT id FROM periode_voting WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            set_flash('Periode tidak ditemukan.', 'danger');
            redirect('periode.php');
        }
        $mysqli->begin_transaction();
        try {
            $mysqli->query("UPDATE periode_voting SET status = 'Nonaktif'");
            $stmt = $mysqli->prepare("UPDATE periode_voting SET status = 'Aktif' WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $mysqli->commit();
            set_flash('Periode dipilih sebagai periode aktif. Voting hanya tersedia selama rentang tanggalnya.');
        } catch (Throwable $e) {
            $mysqli->rollback();
            throw $e;
        }
        redirect('periode.php');
    } elseif ($action === 'nonaktifkan') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $stmt = $mysqli->prepare("UPDATE periode_voting SET status = 'Nonaktif' WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        set_flash('Periode berhasil dinonaktifkan.');
        redirect('periode.php');
    }
}
$periods = $mysqli->query('SELECT * FROM periode_voting ORDER BY tanggal_mulai DESC')->fetch_all(MYSQLI_ASSOC);
render_header('Manajemen Periode');
?>
<section class="container py-5">
  <div class="d-flex justify-content-between align-items-end mb-4"><div><div class="eyebrow">Administrasi</div><h1 class="section-title mb-0">Manajemen periode</h1></div><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div>
  <div class="row g-4">
    <div class="col-lg-4"><div class="card card-body"><h2 class="h5 section-title">Buat periode baru</h2><?php foreach ($errors as $error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endforeach; ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="buat">
        <div class="mb-3"><label class="form-label">Nama periode</label><input class="form-control" name="nama_periode" maxlength="120" value="<?= e($_POST['nama_periode'] ?? '') ?>" required></div>
        <div class="mb-3"><label class="form-label">Tanggal mulai</label><input class="form-control" name="tanggal_mulai" type="datetime-local" value="<?= e($_POST['tanggal_mulai'] ?? '') ?>" required></div>
        <div class="mb-3"><label class="form-label">Tanggal selesai</label><input class="form-control" name="tanggal_selesai" type="datetime-local" value="<?= e($_POST['tanggal_selesai'] ?? '') ?>" required></div>
        <button class="btn btn-gold">Simpan periode</button>
      </form>
    </div></div>
    <div class="col-lg-8"><div class="card card-body"><h2 class="h5 section-title">Periode voting</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama</th><th>Jadwal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach ($periods as $period): ?><tr><td><?= e($period['nama_periode']) ?></td><td class="small"><?= e(date('d M Y H:i', strtotime($period['tanggal_mulai']))) ?><br>s/d <?= e(date('d M Y H:i', strtotime($period['tanggal_selesai']))) ?></td><td><span class="badge <?= $period['status'] === 'Aktif' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($period['status']) ?></span></td><td>
        <form method="post" class="d-inline" data-confirm="Mengaktifkan periode ini akan menonaktifkan periode lain."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $period['id'] ?>"><input type="hidden" name="aksi" value="<?= $period['status'] === 'Aktif' ? 'nonaktifkan' : 'aktifkan' ?>"><button class="btn btn-sm <?= $period['status'] === 'Aktif' ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= $period['status'] === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
      </td></tr><?php endforeach; ?>
      <?php if (!$periods): ?><tr><td colspan="4" class="text-secondary">Belum ada periode.</td></tr><?php endif; ?>
    </tbody></table></div></div></div>
  </div>
</section>
<?php render_footer(); ?>
