<?php
require_once __DIR__ . '/../config/app.php';
require_role('admin');

$stats = [];
$stats['kontestan'] = (int) $mysqli->query('SELECT COUNT(*) AS n FROM kontestan')->fetch_assoc()['n'];
$stats['pemilih'] = (int) $mysqli->query("SELECT COUNT(*) AS n FROM users WHERE role = 'pemilih'")->fetch_assoc()['n'];
$period = active_period($mysqli);
$stats['kakang'] = 0;
$stats['senduk'] = 0;
$stats['pending'] = (int) $mysqli->query("SELECT COUNT(*) AS n FROM pembayaran_vote WHERE status = 'Menunggu'")->fetch_assoc()['n'];
if ($period) {
    $stmt = $mysqli->prepare('SELECT kategori, COALESCE(SUM(jumlah_suara), 0) AS total FROM voting WHERE periode_id = ? GROUP BY kategori');
    $periodId = (int) $period['id'];
    $stmt->bind_param('i', $periodId);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $stats[strtolower($row['kategori'])] = (int) $row['total'];
}
render_header('Dashboard Admin');
?>
<section class="container py-5">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div><div class="eyebrow">Ruang pengelola</div><h1 class="section-title mb-1">Dashboard admin</h1><p class="text-secondary mb-0"><?= $period ? 'Periode aktif: ' . e($period['nama_periode']) : 'Belum ada periode voting yang aktif.' ?></p></div>
    <a class="btn btn-outline-success" href="../pages/klasemen.php">Lihat klasemen publik</a>
  </div>
  <div class="row g-3">
    <?php foreach ([
      ['Total kontestan', $stats['kontestan']],
      ['Total pemilih', $stats['pemilih']],
      ['Suara Kakang', $stats['kakang']],
      ['Suara Senduk', $stats['senduk']],
      ['Pembayaran menunggu', $stats['pending']],
    ] as $stat): ?>
      <div class="col-sm-6 col-lg"><div class="stat-card"><div class="text-secondary small"><?= e($stat[0]) ?></div><div class="stat-value"><?= number_format($stat[1]) ?></div></div></div>
    <?php endforeach; ?>
  </div>
  <div class="row g-4 mt-2">
    <div class="col-lg-7"><div class="card card-body"><h2 class="h5 section-title">Ringkasan suara periode aktif</h2><canvas id="voteChart" height="130" aria-label="Grafik suara Kakang dan Senduk"></canvas></div></div>
    <div class="col-lg-5"><div class="card card-body h-100"><h2 class="h5 section-title">Akses cepat</h2>
      <a class="btn btn-outline-success text-start mb-2" href="kontestan.php">Kelola kontestan</a>
      <a class="btn btn-outline-success text-start mb-2" href="periode.php">Kelola periode voting</a>
      <a class="btn btn-outline-success text-start" href="hasil.php">Hasil & verifikasi pembayaran</a>
      <div class="form-text mt-3">Grafik dan klasemen menggunakan suara yang tercatat/disetujui untuk periode berjalan.</div>
    </div></div>
  </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('voteChart'), {
  type: 'bar',
  data: { labels: ['Kakang', 'Senduk'], datasets: [{ label: 'Suara', data: [<?= (int) $stats['kakang'] ?>, <?= (int) $stats['senduk'] ?>], backgroundColor: ['#527a61', '#d7ad58'], borderRadius: 8 }] },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
<?php render_footer(); ?>
