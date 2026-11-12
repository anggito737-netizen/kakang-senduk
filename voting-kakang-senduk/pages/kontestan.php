<?php
require_once __DIR__ . '/../config/app.php';

$kategori = $_GET['kategori'] ?? '';
$search = trim($_GET['q'] ?? '');
$allowedCategories = ['', 'Kakang', 'Senduk'];
if (!in_array($kategori, $allowedCategories, true)) $kategori = '';

$sql = 'SELECT id, nomor_peserta, nama, kategori, foto, asal_instansi FROM kontestan WHERE 1=1';
$types = '';
$values = [];
if ($kategori !== '') {
    $sql .= ' AND kategori = ?';
    $types .= 's';
    $values[] = $kategori;
}
if ($search !== '') {
    $sql .= ' AND (nama LIKE ? OR nomor_peserta LIKE ? OR asal_instansi LIKE ?)';
    $types .= 'sss';
    $term = '%' . $search . '%';
    array_push($values, $term, $term, $term);
}
$sql .= ' ORDER BY kategori, nomor_peserta';
$stmt = $mysqli->prepare($sql);
if ($types !== '') {
    $bindings = [$types];
    foreach ($values as $index => $value) $bindings[] = &$values[$index];
    call_user_func_array([$stmt, 'bind_param'], $bindings);
}
$stmt->execute();
$people = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

render_header('Daftar Kontestan', 'kontestan');
?>
<section class="container py-5">
  <div class="eyebrow">Kenali para finalis</div><h1 class="section-title mb-2">Daftar kontestan</h1>
  <p class="text-secondary mb-4">Temukan sosok inspiratif yang akan mewakili budaya dan pariwisata Ponorogo.</p>
  <form class="card card-body mb-4" method="get">
    <div class="row g-2 align-items-end">
      <div class="col-md-7"><label class="form-label" for="q">Cari kontestan</label><input id="q" class="form-control" name="q" value="<?= e($search) ?>" placeholder="Nama, nomor, atau instansi"></div>
      <div class="col-md-3"><label class="form-label" for="kategori">Kategori</label><select class="form-select" name="kategori" id="kategori"><option value="">Semua kategori</option><option <?= $kategori === 'Kakang' ? 'selected' : '' ?>>Kakang</option><option <?= $kategori === 'Senduk' ? 'selected' : '' ?>>Senduk</option></select></div>
      <div class="col-md-2 d-grid"><button class="btn btn-success" style="background:var(--forest)">Cari</button></div>
    </div>
  </form>
  <div class="row g-4">
    <?php if (!$people): ?><div class="col-12"><div class="alert alert-light border">Kontestan tidak ditemukan.</div></div><?php endif; ?>
    <?php foreach ($people as $person): ?>
      <div class="col-sm-6 col-lg-4">
        <article class="card contestant-card h-100 shadow-sm">
          <?php if ($person['foto']): ?><img class="contestant-photo" src="<?= e($person['foto']) ?>" alt="Foto <?= e($person['nama']) ?>" loading="lazy"><?php else: ?><div class="placeholder-photo"><?= e(mb_substr($person['nama'], 0, 1)) ?></div><?php endif; ?>
          <div class="card-body d-flex flex-column">
            <div><span class="badge <?= $person['kategori'] === 'Kakang' ? 'badge-kakang' : 'badge-senduk' ?>"><?= e($person['kategori']) ?></span><span class="text-secondary small ms-2">No. <?= e($person['nomor_peserta']) ?></span></div>
            <h2 class="h5 mt-3 mb-1"><?= e($person['nama']) ?></h2><p class="text-secondary small"><?= e($person['asal_instansi']) ?></p>
            <a class="btn btn-outline-success btn-sm mt-auto align-self-start" href="detail-kontestan.php?id=<?= (int) $person['id'] ?>">Lihat detail</a>
          </div>
        </article>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php render_footer(); ?>
