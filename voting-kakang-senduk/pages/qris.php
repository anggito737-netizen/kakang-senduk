<?php
require_once __DIR__ . '/../config/app.php';
require_role('pemilih');
$paymentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $mysqli->prepare('SELECT p.*, k.nama AS nama_kontestan, k.nomor_peserta, pv.nama_periode FROM pembayaran_vote p JOIN kontestan k ON k.id = p.kontestan_id JOIN periode_voting pv ON pv.id = p.periode_id WHERE p.id = ? AND p.user_id = ?');
$userId = current_user()['id'];
$stmt->bind_param('ii', $paymentId, $userId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
if (!$payment) {
    http_response_code(404);
    render_header('Pembayaran tidak ditemukan');
    echo '<div class="container py-5"><div class="alert alert-warning">Data pembayaran tidak ditemukan.</div></div>';
    render_footer();
    exit;
}
render_header('Instruksi QRIS');
?>
<section class="container py-5" style="max-width:760px">
  <div class="eyebrow">Dukungan ekstra</div><h1 class="section-title">Instruksi pembayaran</h1>
  <div class="alert alert-info mt-3">Integrasi QRIS belum tersedia. Ini adalah simulasi: jangan melakukan pembayaran nyata. Suara baru masuk klasemen setelah admin memverifikasi pembayaran.</div>
  <div class="card card-body mt-4">
    <div class="row g-4 align-items-center">
      <div class="col-md-5 text-center">
        <div class="mx-auto d-grid align-items-center p-3 border rounded-4" style="width:220px;height:220px;background:repeating-linear-gradient(45deg,#f5f2ea,#f5f2ea 8px,#fff 8px,#fff 16px)">
          <div class="bg-white rounded p-3"><strong class="d-block text-success">SIMULASI QRIS</strong><span class="small text-secondary">Belum aktif</span></div>
        </div>
        <div class="small text-secondary mt-2">Kode QR pembayaran akan tersedia setelah integrasi resmi.</div>
      </div>
      <div class="col-md-7">
        <div class="text-secondary small">ID transaksi #<?= (int) $payment['id'] ?></div>
        <h2 class="h4 mt-1"><?= e($payment['nama_kontestan']) ?></h2>
        <dl class="row mt-3 mb-2">
          <dt class="col-6">Jumlah suara</dt><dd class="col-6"><?= number_format((int) $payment['jumlah_suara']) ?></dd>
          <dt class="col-6">Biaya simulasi</dt><dd class="col-6 fw-bold"><?= format_rupiah($payment['total_harga']) ?></dd>
          <dt class="col-6">Status</dt><dd class="col-6"><span class="badge text-bg-warning"><?= e($payment['status']) ?></span></dd>
        </dl>
        <?php if ($payment['pesan_dukungan']): ?><p class="small text-secondary">Pesan: “<?= e($payment['pesan_dukungan']) ?>”</p><?php endif; ?>
        <a href="voting.php" class="btn btn-outline-success">Kembali ke voting</a>
      </div>
    </div>
  </div>
</section>
<?php render_footer(); ?>
