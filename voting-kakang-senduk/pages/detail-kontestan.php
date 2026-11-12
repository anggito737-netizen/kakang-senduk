<?php
require_once __DIR__ . '/../config/app.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $mysqli->prepare('SELECT * FROM kontestan WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$person = $stmt->get_result()->fetch_assoc();
if (!$person) {
    http_response_code(404);
    render_header('Kontestan tidak ditemukan', 'kontestan');
    echo '<div class="container py-5"><div class="alert alert-warning">Kontestan tidak ditemukan.</div><a href="kontestan.php">Kembali ke daftar</a></div>';
    render_footer();
    exit;
}
render_header('Detail ' . $person['nama'], 'kontestan');
?>
<section class="container py-5">
  <a class="text-success text-decoration-none" href="kontestan.php">← Kembali ke kontestan</a>
  <div class="row g-4 g-lg-5 mt-2 align-items-start">
    <div class="col-lg-5">
      <?php if ($person['foto']): ?><img class="w-100 rounded-4 shadow-sm" style="max-height:620px;object-fit:cover" src="<?= e($person['foto']) ?>" alt="Foto <?= e($person['nama']) ?>"><?php else: ?><div class="placeholder-photo rounded-4" style="height:420px"><?= e(mb_substr($person['nama'], 0, 1)) ?></div><?php endif; ?>
    </div>
    <div class="col-lg-7">
      <span class="badge <?= $person['kategori'] === 'Kakang' ? 'badge-kakang' : 'badge-senduk' ?>"><?= e($person['kategori']) ?> · No. <?= e($person['nomor_peserta']) ?></span>
      <h1 class="display-5 section-title mt-3"><?= e($person['nama']) ?></h1>
      <p class="text-secondary"><?= e($person['asal_instansi']) ?></p>
      <div class="card card-body mb-3"><h2 class="h5 section-title">Profil</h2><p class="mb-0"><?= nl2br(e($person['deskripsi'])) ?></p></div>
      <div class="card card-body mb-3"><h2 class="h5 section-title">Visi</h2><p class="mb-0"><?= nl2br(e($person['visi'])) ?></p></div>
      <div class="card card-body"><h2 class="h5 section-title">Misi</h2><p class="mb-0"><?= nl2br(e($person['misi'])) ?></p></div>
      <?php if (current_user() && current_user()['role'] === 'pemilih'): ?><a href="voting.php?kontestan_id=<?= (int) $person['id'] ?>" class="btn btn-gold mt-4">Dukung kontestan ini</a><?php endif; ?>
    </div>
  </div>
</section>
<?php render_footer(); ?>
