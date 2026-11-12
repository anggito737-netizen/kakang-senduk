<?php
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$errors = [];
$editPerson = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = $_POST['aksi'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    if ($action === 'hapus') {
        $stmt = $mysqli->prepare('DELETE FROM kontestan WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        set_flash('Kontestan berhasil dihapus.');
        redirect('kontestan.php');
    }

    $number = trim($_POST['nomor_peserta'] ?? '');
    $name = trim($_POST['nama'] ?? '');
    $category = $_POST['kategori'] ?? '';
    $photo = trim($_POST['foto'] ?? '');
    $institution = trim($_POST['asal_instansi'] ?? '');
    $vision = trim($_POST['visi'] ?? '');
    $mission = trim($_POST['misi'] ?? '');
    $description = trim($_POST['deskripsi'] ?? '');
    if ($number === '' || strlen($number) > 30) $errors[] = 'Nomor peserta wajib diisi (maksimal 30 karakter).';
    if ($name === '' || strlen($name) > 120) $errors[] = 'Nama wajib diisi (maksimal 120 karakter).';
    if (!in_array($category, ['Kakang', 'Senduk'], true)) $errors[] = 'Kategori tidak valid.';
    if ($photo !== '' && (!filter_var($photo, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($photo, PHP_URL_SCHEME)), ['http', 'https'], true))) $errors[] = 'Foto harus berupa URL HTTP/HTTPS yang valid.';
    if ($institution === '' || strlen($institution) > 160) $errors[] = 'Asal instansi wajib diisi (maksimal 160 karakter).';
    if ($vision === '' || $mission === '' || $description === '') $errors[] = 'Visi, misi, dan deskripsi wajib diisi.';

    if (!$errors) {
        try {
            if ($action === 'simpan' && $id > 0) {
                $stmt = $mysqli->prepare('UPDATE kontestan SET nomor_peserta=?, nama=?, kategori=?, foto=?, asal_instansi=?, visi=?, misi=?, deskripsi=? WHERE id=?');
                $stmt->bind_param('ssssssssi', $number, $name, $category, $photo, $institution, $vision, $mission, $description, $id);
                $stmt->execute();
                set_flash('Data kontestan berhasil diperbarui.');
            } else {
                $stmt = $mysqli->prepare('INSERT INTO kontestan (nomor_peserta,nama,kategori,foto,asal_instansi,visi,misi,deskripsi) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->bind_param('ssssssss', $number, $name, $category, $photo, $institution, $vision, $mission, $description);
                $stmt->execute();
                set_flash('Kontestan berhasil ditambahkan.');
            }
            redirect('kontestan.php');
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1062) $errors[] = 'Nomor peserta sudah digunakan.';
            else throw $e;
        }
    }
}

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $mysqli->prepare('SELECT * FROM kontestan WHERE id = ?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editPerson = $stmt->get_result()->fetch_assoc();
}
$people = $mysqli->query('SELECT id, nomor_peserta, nama, kategori, asal_instansi FROM kontestan ORDER BY kategori, nomor_peserta')->fetch_all(MYSQLI_ASSOC);
render_header('Kelola Kontestan');
?>
<section class="container py-5">
  <div class="d-flex justify-content-between align-items-end mb-4"><div><div class="eyebrow">Administrasi</div><h1 class="section-title mb-0">Kelola kontestan</h1></div><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div>
  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card card-body">
        <h2 class="h5 section-title"><?= $editPerson ? 'Edit kontestan' : 'Tambah kontestan' ?></h2>
        <?php foreach ($errors as $error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endforeach; ?>
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= (int) ($editPerson['id'] ?? ($_POST['id'] ?? 0)) ?>">
          <div class="mb-3"><label class="form-label">Nomor peserta</label><input class="form-control" name="nomor_peserta" maxlength="30" value="<?= e($editPerson['nomor_peserta'] ?? ($_POST['nomor_peserta'] ?? '')) ?>" required></div>
          <div class="mb-3"><label class="form-label">Nama</label><input class="form-control" name="nama" maxlength="120" value="<?= e($editPerson['nama'] ?? ($_POST['nama'] ?? '')) ?>" required></div>
          <div class="mb-3"><label class="form-label">Kategori</label><select class="form-select" name="kategori" required><option value="">Pilih kategori</option><?php foreach (['Kakang','Senduk'] as $category): ?><option value="<?= $category ?>" <?= ($editPerson['kategori'] ?? ($_POST['kategori'] ?? '')) === $category ? 'selected' : '' ?>><?= $category ?></option><?php endforeach; ?></select></div>
          <div class="mb-3"><label class="form-label">URL foto</label><input class="form-control" type="url" name="foto" value="<?= e($editPerson['foto'] ?? ($_POST['foto'] ?? '')) ?>" placeholder="https://..."></div>
          <div class="mb-3"><label class="form-label">Asal instansi</label><input class="form-control" name="asal_instansi" maxlength="160" value="<?= e($editPerson['asal_instansi'] ?? ($_POST['asal_instansi'] ?? '')) ?>" required></div>
          <div class="mb-3"><label class="form-label">Visi</label><textarea class="form-control" name="visi" rows="2" required><?= e($editPerson['visi'] ?? ($_POST['visi'] ?? '')) ?></textarea></div>
          <div class="mb-3"><label class="form-label">Misi</label><textarea class="form-control" name="misi" rows="3" required><?= e($editPerson['misi'] ?? ($_POST['misi'] ?? '')) ?></textarea></div>
          <div class="mb-3"><label class="form-label">Deskripsi / profil</label><textarea class="form-control" name="deskripsi" rows="3" required><?= e($editPerson['deskripsi'] ?? ($_POST['deskripsi'] ?? '')) ?></textarea></div>
          <button class="btn btn-gold"><?= $editPerson ? 'Simpan perubahan' : 'Tambah kontestan' ?></button><?php if ($editPerson): ?> <a class="btn btn-light" href="kontestan.php">Batal</a><?php endif; ?>
        </form>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card card-body"><h2 class="h5 section-title">Daftar kontestan <span class="text-secondary">(<?= count($people) ?>)</span></h2>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nomor</th><th>Nama</th><th>Kategori</th><th>Instansi</th><th>Aksi</th></tr></thead><tbody>
          <?php foreach ($people as $person): ?><tr><td><?= e($person['nomor_peserta']) ?></td><td><?= e($person['nama']) ?></td><td><?= e($person['kategori']) ?></td><td><?= e($person['asal_instansi']) ?></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int) $person['id'] ?>">Edit</a>
            <form class="d-inline" method="post" data-confirm="Hapus kontestan ini? Suara dan pembayaran terkait juga akan terhapus."><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int) $person['id'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form>
          </td></tr><?php endforeach; ?>
          <?php if (!$people): ?><tr><td colspan="5" class="text-secondary">Belum ada data kontestan.</td></tr><?php endif; ?>
        </tbody></table></div>
      </div>
    </div>
  </div>
</section>
<?php render_footer(); ?>
