<?php
require_once __DIR__ . '/../config/app.php';
require_role('admin');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $paymentId = filter_input(INPUT_POST, 'pembayaran_id', FILTER_VALIDATE_INT);
    $decision = $_POST['keputusan'] ?? '';
    if (!$paymentId || !in_array($decision, ['setujui', 'tolak'], true)) {
        set_flash('Permintaan verifikasi tidak valid.', 'danger');
        redirect('hasil.php');
    }

    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare('SELECT * FROM pembayaran_vote WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        if (!$payment || $payment['status'] !== 'Menunggu') {
            $mysqli->rollback();
            set_flash('Transaksi tidak ditemukan atau sudah diproses.', 'warning');
            redirect('hasil.php');
        }

        if ($decision === 'setujui') {
            $stmt = $mysqli->prepare("INSERT INTO voting (user_id, kontestan_id, kategori, jumlah_suara, jenis_vote, periode_id, pesan_dukungan) VALUES (?, ?, ?, ?, 'berbayar', ?, ?)");
            $stmt->bind_param('iisiis', $payment['user_id'], $payment['kontestan_id'], $payment['kategori'], $payment['jumlah_suara'], $payment['periode_id'], $payment['pesan_dukungan']);
            $stmt->execute();
            $newStatus = 'Disetujui';
        } else {
            $newStatus = 'Ditolak';
        }
        $stmt = $mysqli->prepare('UPDATE pembayaran_vote SET status = ?, diverifikasi_at = NOW() WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $paymentId);
        $stmt->execute();
        $mysqli->commit();
        set_flash($decision === 'setujui' ? 'Pembayaran disetujui dan suara masuk ke hasil.' : 'Pembayaran ditolak.');
    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }
    redirect('hasil.php');
}

$periodId = filter_input(INPUT_GET, 'periode_id', FILTER_VALIDATE_INT);
$periods = $mysqli->query('SELECT id, nama_periode FROM periode_voting ORDER BY tanggal_mulai DESC')->fetch_all(MYSQLI_ASSOC);
if (!$periodId && $periods) $periodId = (int) $periods[0]['id'];
$selectedPeriod = null;
foreach ($periods as $item) if ((int) $item['id'] === (int) $periodId) $selectedPeriod = $item;

$ranking = [];
$totals = ['Kakang' => 0, 'Senduk' => 0];
if ($selectedPeriod) {
    $stmt = $mysqli->prepare('SELECT k.nomor_peserta, k.nama, k.kategori, k.asal_instansi, COALESCE(SUM(v.jumlah_suara), 0) AS suara FROM kontestan k LEFT JOIN voting v ON v.kontestan_id = k.id AND v.periode_id = ? GROUP BY k.id ORDER BY k.kategori, suara DESC');
    $pid = (int) $selectedPeriod['id'];
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $ranking = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($ranking as $row) $totals[$row['kategori']] += (int) $row['suara'];
}
$payments = $mysqli->query("SELECT p.*, u.nama AS nama_pemilih, u.email, k.nama AS nama_kontestan, k.nomor_peserta FROM pembayaran_vote p JOIN users u ON u.id = p.user_id JOIN kontestan k ON k.id = p.kontestan_id ORDER BY FIELD(p.status,'Menunggu','Disetujui','Ditolak'), p.created_at DESC LIMIT 200")->fetch_all(MYSQLI_ASSOC);
render_header('Hasil & Pembayaran');
?>
<section class="container py-5">
  <div class="d-flex justify-content-between align-items-end mb-4"><div><div class="eyebrow">Administrasi</div><h1 class="section-title mb-0">Hasil & pembayaran</h1></div><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div>
  <div class="card card-body mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3"><div><h2 class="h5 section-title mb-1">Rincian hasil suara</h2><p class="small text-secondary mb-0">Suara berbayar ditambahkan ke hasil setelah disetujui.</p></div>
      <form method="get" class="d-flex gap-2"><select class="form-select" name="periode_id" aria-label="Pilih periode"><?php foreach ($periods as $item): ?><option value="<?= (int) $item['id'] ?>" <?= (int) $item['id'] === (int) $periodId ? 'selected' : '' ?>><?= e($item['nama_periode']) ?></option><?php endforeach; ?></select><button class="btn btn-outline-success">Tampilkan</button></form>
    </div>
    <?php if (!$selectedPeriod): ?><div class="alert alert-info mt-3 mb-0">Belum ada periode voting.</div><?php else: ?>
      <div class="row g-3 my-2"><div class="col-md-6"><div class="stat-card"><div class="eyebrow">Suara Kakang</div><div class="stat-value"><?= number_format($totals['Kakang']) ?></div></div></div><div class="col-md-6"><div class="stat-card"><div class="eyebrow">Suara Senduk</div><div class="stat-value"><?= number_format($totals['Senduk']) ?></div></div></div></div>
      <div class="table-responsive"><table class="table align-middle"><thead><tr><th>No.</th><th>Kontestan</th><th>Kategori</th><th>Instansi</th><th>Total suara</th></tr></thead><tbody><?php foreach ($ranking as $index => $row): ?><tr><td><?= $index + 1 ?></td><td><?= e($row['nama']) ?> <small class="text-secondary">(<?= e($row['nomor_peserta']) ?>)</small></td><td><?= e($row['kategori']) ?></td><td><?= e($row['asal_instansi']) ?></td><td><strong><?= number_format((int) $row['suara']) ?></strong></td></tr><?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </div>
  <div class="card card-body"><div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 section-title mb-0">Verifikasi dukungan ekstra</h2><span class="small text-secondary">Maksimal 200 transaksi terbaru</span></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Waktu / pemilih</th><th>Kontestan</th><th>Suara</th><th>Jumlah</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
      <?php foreach ($payments as $payment): ?><tr>
        <td><?= e(date('d M Y H:i', strtotime($payment['created_at']))) ?><div class="small text-secondary"><?= e($payment['nama_pemilih']) ?> · <?= e($payment['email']) ?></div></td>
        <td><?= e($payment['nama_kontestan']) ?><div class="small text-secondary"><?= e($payment['kategori']) ?> · #<?= e($payment['nomor_peserta']) ?></div></td>
        <td><?= number_format((int) $payment['jumlah_suara']) ?></td><td><?= format_rupiah($payment['total_harga']) ?></td>
        <td><span class="badge <?= $payment['status'] === 'Menunggu' ? 'text-bg-warning' : ($payment['status'] === 'Disetujui' ? 'text-bg-success' : 'text-bg-secondary') ?>"><?= e($payment['status']) ?></span><?php if ($payment['pesan_dukungan']): ?><div class="small text-secondary mt-1"><?= e($payment['pesan_dukungan']) ?></div><?php endif; ?></td>
        <td><?php if ($payment['status'] === 'Menunggu'): ?><form method="post" class="d-flex gap-1" data-confirm="Proses keputusan untuk transaksi #<?= (int) $payment['id'] ?>?"><?= csrf_field() ?><input type="hidden" name="pembayaran_id" value="<?= (int) $payment['id'] ?>"><button class="btn btn-sm btn-success" name="keputusan" value="setujui">Setujui</button><button class="btn btn-sm btn-outline-danger" name="keputusan" value="tolak">Tolak</button></form><?php else: ?><span class="small text-secondary"><?= e($payment['diverifikasi_at'] ?? '') ?></span><?php endif; ?></td>
      </tr><?php endforeach; ?>
      <?php if (!$payments): ?><tr><td colspan="6" class="text-center text-secondary">Belum ada transaksi dukungan ekstra.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
</section>
<?php render_footer(); ?>
