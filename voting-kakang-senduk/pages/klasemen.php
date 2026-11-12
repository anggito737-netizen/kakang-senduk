<?php
require_once __DIR__ . '/../config/app.php';
$period = active_period($mysqli);
if (!$period) {
    $period = $mysqli->query("SELECT * FROM periode_voting ORDER BY tanggal_mulai DESC, id DESC LIMIT 1")->fetch_assoc();
}
$results = ['Kakang' => [], 'Senduk' => []];
$totals = ['Kakang' => 0, 'Senduk' => 0];
if ($period) {
    $stmt = $mysqli->prepare('SELECT k.id, k.nama, k.nomor_peserta, k.kategori, k.foto, k.asal_instansi, COALESCE(SUM(v.jumlah_suara), 0) AS suara FROM kontestan k LEFT JOIN voting v ON v.kontestan_id = k.id AND v.periode_id = ? GROUP BY k.id ORDER BY k.kategori, suara DESC, k.nomor_peserta');
    $periodId = (int) $period['id'];
    $stmt->bind_param('i', $periodId);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $results[$row['kategori']][] = $row;
        $totals[$row['kategori']] += (int) $row['suara'];
    }
}
render_header('Klasemen', 'klasemen');
?>
<section class="container py-5">
  <div class="eyebrow">Hasil sementara</div><h1 class="section-title">Klasemen pemilihan</h1>
  <p class="text-secondary"><?= $period ? e($period['nama_periode']) : 'Belum ada periode pemilihan.' ?> · Suara berbayar hanya dihitung setelah diverifikasi admin.</p>
  <?php if (!$period): ?><div class="alert alert-info">Klasemen akan tampil setelah periode voting dibuat.</div><?php else: ?>
    <div class="row g-3 my-3"><div class="col-md-6"><div class="stat-card"><div class="eyebrow">Total suara Kakang</div><div class="stat-value"><?= number_format($totals['Kakang']) ?></div></div></div><div class="col-md-6"><div class="stat-card"><div class="eyebrow">Total suara Senduk</div><div class="stat-value"><?= number_format($totals['Senduk']) ?></div></div></div></div>
    <div class="row g-4">
      <?php foreach (['Kakang', 'Senduk'] as $category): ?>
        <div class="col-lg-6">
          <h2 class="h4 section-title mb-3">Kategori <?= e($category) ?></h2>
          <?php if (!$results[$category]): ?><div class="alert alert-light border">Belum ada kontestan.</div><?php endif; ?>
          <?php foreach (array_slice($results[$category], 0, 3) as $index => $person): ?>
            <div class="card podium-card card-body mb-3">
              <div class="d-flex align-items-center gap-3">
                <span class="rank-number"><?= $index + 1 ?></span>
                <?php if ($person['foto']): ?><img src="<?= e($person['foto']) ?>" alt="" class="rounded-circle" style="width:58px;height:58px;object-fit:cover"><?php endif; ?>
                <div class="flex-grow-1"><strong><?= e($person['nama']) ?></strong><div class="small text-secondary">No. <?= e($person['nomor_peserta']) ?> · <?= e($person['asal_instansi']) ?></div></div>
                <div class="text-end"><strong><?= number_format((int) $person['suara']) ?></strong><div class="small text-secondary">suara · <?= $totals[$category] ? number_format(((int) $person['suara'] / $totals[$category]) * 100, 1, ',', '.') : '0,0' ?>%</div></div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (count($results[$category]) > 3): ?>
            <div class="card card-body"><h3 class="h6">Peringkat selanjutnya</h3>
              <?php foreach (array_slice($results[$category], 3) as $index => $person): ?><div class="d-flex justify-content-between border-top py-2"><span><?= $index + 4 ?>. <?= e($person['nama']) ?> <small class="text-secondary">(<?= e($person['nomor_peserta']) ?>)</small></span><strong><?= number_format((int) $person['suara']) ?></strong></div><?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php render_footer(); ?>
