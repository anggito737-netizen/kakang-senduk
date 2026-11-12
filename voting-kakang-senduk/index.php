<?php
require_once __DIR__ . '/config/app.php';

$period = active_period($mysqli);
$contestantCount = (int) $mysqli->query('SELECT COUNT(*) AS total FROM kontestan')->fetch_assoc()['total'];
$periodVotes = 0;
if ($period) {
    $stmt = $mysqli->prepare('SELECT COALESCE(SUM(jumlah_suara), 0) AS total FROM voting WHERE periode_id = ?');
    $stmt->bind_param('i', $period['id']);
    $stmt->execute();
    $periodVotes = (int) $stmt->get_result()->fetch_assoc()['total'];
}
$featured = $mysqli->query('SELECT id, nomor_peserta, nama, kategori, foto, asal_instansi FROM kontestan ORDER BY id DESC LIMIT 3')->fetch_all(MYSQLI_ASSOC);

render_header('Beranda', 'home');
?>
<section class="hero py-5 py-lg-6">
  <div class="container py-4 py-lg-5 hero-content">
    <div class="row align-items-center g-4">
      <div class="col-lg-8">
        <div class="eyebrow mb-3">Pesona budaya Ponorogo</div>
        <h1 class="display-4 fw-bold mb-3">Pilih inspirasi,<br>rayakan kebanggaan.</h1>
        <p class="lead text-white-50 mb-4">Dukung Kakang dan Senduk pilihanmu dalam ajang pemilihan duta budaya dan pariwisata Ponorogo.</p>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-gold btn-lg" href="pages/kontestan.php">Jelajahi kontestan</a>
          <?php if (current_user() && current_user()['role'] === 'pemilih'): ?>
            <a class="btn btn-outline-light btn-lg" href="pages/voting.php">Mulai voting</a>
          <?php elseif (!current_user()): ?>
            <a class="btn btn-outline-light btn-lg" href="auth/register.php">Daftar pemilih</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card bg-white bg-opacity-10 text-white border border-light border-opacity-25 p-4">
          <div class="eyebrow">Periode pemilihan</div>
          <h2 class="h4 mt-2"><?= $period ? e($period['nama_periode']) : 'Segera hadir' ?></h2>
          <?php if ($period): ?>
            <p class="mb-1 text-white-50">Berakhir <?= e(date('d M Y, H:i', strtotime($period['tanggal_selesai']))) ?> WIB</p>
            <span class="badge text-bg-success align-self-start">Voting aktif</span>
          <?php else: ?>
            <p class="mb-0 text-white-50">Informasi periode voting akan diumumkan melalui halaman ini.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="container py-5">
  <div class="row g-3 mb-5">
    <div class="col-md-6"><div class="stat-card"><div class="eyebrow">Kontestan</div><div class="stat-value"><?= number_format($contestantCount) ?></div><div class="text-secondary">Talenta terbaik Ponorogo</div></div></div>
    <div class="col-md-6"><div class="stat-card"><div class="eyebrow">Suara periode ini</div><div class="stat-value"><?= number_format($periodVotes) ?></div><div class="text-secondary">Suara terverifikasi masuk</div></div></div>
  </div>
  <div class="d-flex justify-content-between align-items-end mb-3">
    <div><div class="eyebrow">Kenali para finalis</div><h2 class="section-title mb-0">Kontestan pilihan</h2></div>
    <a href="pages/kontestan.php" class="link-success">Lihat semua</a>
  </div>
  <div class="row g-4">
    <?php if (!$featured): ?><div class="col-12"><div class="alert alert-light border">Data kontestan belum tersedia.</div></div><?php endif; ?>
    <?php foreach ($featured as $person): ?>
      <div class="col-sm-6 col-lg-4">
        <div class="card contestant-card h-100 shadow-sm">
          <?php if ($person['foto']): ?><img class="contestant-photo" src="<?= e($person['foto']) ?>" alt="Foto <?= e($person['nama']) ?>" loading="lazy"><?php else: ?><div class="placeholder-photo"><?= e(mb_substr($person['nama'], 0, 1)) ?></div><?php endif; ?>
          <div class="card-body">
            <span class="badge <?= $person['kategori'] === 'Kakang' ? 'badge-kakang' : 'badge-senduk' ?>"><?= e($person['kategori']) ?> · <?= e($person['nomor_peserta']) ?></span>
            <h3 class="h5 mt-3 mb-1"><?= e($person['nama']) ?></h3><div class="text-secondary small"><?= e($person['asal_instansi']) ?></div>
            <a class="btn btn-outline-success btn-sm mt-3" href="pages/detail-kontestan.php?id=<?= (int) $person['id'] ?>">Lihat detail</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="text-center mt-5 p-4 p-md-5 rounded-4" style="background:var(--cream)">
    <div class="eyebrow">Dukunganmu berarti</div><h2 class="section-title mt-2">Satu suara untuk masa depan budaya</h2>
    <p class="text-secondary mb-4">Kenali gagasan para finalis dan ikut menentukan duta kebanggaan Ponorogo.</p>
    <a class="btn btn-gold px-4" href="pages/klasemen.php">Lihat klasemen</a>
  </div>
</section>
<?php render_footer(); ?>
