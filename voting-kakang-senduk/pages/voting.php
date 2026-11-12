<?php
require_once __DIR__ . '/../config/app.php';
require_role('pemilih');
$period = active_period($mysqli);
$requestedContestant = filter_input(INPUT_GET, 'kontestan_id', FILTER_VALIDATE_INT) ?: 0;
$people = $mysqli->query('SELECT id, nomor_peserta, nama, kategori, foto, asal_instansi FROM kontestan ORDER BY kategori, nomor_peserta')->fetch_all(MYSQLI_ASSOC);
$freeUsed = [];
if ($period) {
    $stmt = $mysqli->prepare("SELECT kategori FROM voting WHERE user_id = ? AND periode_id = ? AND jenis_vote = 'gratis'");
    $userId = current_user()['id'];
    $periodId = $period['id'];
    $stmt->bind_param('ii', $userId, $periodId);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $freeUsed[$row['kategori']] = true;
}
render_header('Voting', 'voting');
?>
<section class="container py-5">
  <div class="eyebrow">Partisipasi Anda</div><h1 class="section-title">Dukung kontestan</h1>
  <?php if (!$period): ?>
    <div class="alert alert-warning mt-4">Belum ada periode voting yang aktif. Silakan kembali saat periode pemilihan dibuka.</div>
  <?php elseif (!$people): ?>
    <div class="alert alert-info mt-4">Data kontestan belum tersedia.</div>
  <?php else: ?>
    <div class="card card-body mt-3 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between gap-2"><div><div class="eyebrow">Periode aktif</div><strong><?= e($period['nama_periode']) ?></strong></div><div class="text-secondary">Satu akun mendapat satu suara gratis untuk setiap kategori.</div></div>
    </div>
    <div class="row g-4">
      <?php foreach (['Kakang', 'Senduk'] as $category): ?>
        <div class="col-lg-6">
          <div class="d-flex align-items-center justify-content-between mb-3"><h2 class="h4 section-title mb-0"><?= e($category) ?></h2>
            <?php if (!empty($freeUsed[$category])): ?><span class="badge text-bg-secondary">Suara gratis digunakan</span><?php else: ?><span class="badge text-bg-success">Suara gratis tersedia</span><?php endif; ?>
          </div>
          <?php $categoryPeople = array_filter($people, function ($person) use ($category) { return $person['kategori'] === $category; }); ?>
          <?php if (!$categoryPeople): ?><div class="alert alert-light border">Belum ada finalis kategori <?= e($category) ?>.</div><?php endif; ?>
          <div class="d-grid gap-3">
            <?php foreach ($categoryPeople as $person): ?>
              <article class="card card-body">
                <div class="d-flex align-items-start gap-3">
                  <?php if ($person['foto']): ?><img src="<?= e($person['foto']) ?>" alt="" class="rounded-3" style="width:76px;height:88px;object-fit:cover"><?php else: ?><div class="rounded-3 d-grid place-items-center" style="width:76px;height:88px;background:var(--cream);color:var(--forest);font-size:2rem;font-weight:bold"><?= e(mb_substr($person['nama'], 0, 1)) ?></div><?php endif; ?>
                  <div class="flex-grow-1"><span class="small text-secondary">No. <?= e($person['nomor_peserta']) ?></span><h3 class="h6 mb-1"><?= e($person['nama']) ?></h3><div class="small text-secondary"><?= e($person['asal_instansi']) ?></div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                      <?php if (empty($freeUsed[$category])): ?>
                        <form method="post" action="../proses/voting.php" data-confirm="Gunakan suara gratis untuk <?= e($person['nama']) ?>?">
                          <?= csrf_field() ?><input type="hidden" name="aksi" value="gratis"><input type="hidden" name="kontestan_id" value="<?= (int) $person['id'] ?>">
                          <button class="btn btn-sm btn-success" style="background:var(--forest)">Vote gratis</button>
                        </form>
                      <?php endif; ?>
                      <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#paidVoteModal" data-contestant="<?= (int) $person['id'] ?>" data-name="<?= e($person['nama']) ?>">Dukung ekstra</button>
                    </div>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="modal fade" id="paidVoteModal" tabindex="-1" aria-labelledby="paidVoteTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="post" action="../proses/voting.php">
          <div class="modal-header"><div><div class="eyebrow">Dukungan ekstra</div><h2 class="modal-title fs-5" id="paidVoteTitle">Konfirmasi dukungan</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
          <div class="modal-body">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="berbayar"><input type="hidden" name="kontestan_id" id="paidContestant">
            <p class="text-secondary">Untuk <strong id="paidContestantName"></strong> · Rp 3.500 per suara.</p>
            <div class="d-flex flex-wrap gap-2 mb-3"><?php foreach ([10, 50, 100] as $qty): ?><button type="button" class="btn btn-sm btn-outline-secondary" data-set-votes="<?= $qty ?>"><?= $qty ?> suara</button><?php endforeach; ?></div>
            <label class="form-label" for="voteAmount">Jumlah suara</label><input class="form-control" id="voteAmount" name="jumlah_suara" type="number" min="1" max="10000" value="10" data-vote-amount data-price="3500" required>
            <label class="form-label mt-3" for="supportMessage">Pesan dukungan (opsional)</label><textarea class="form-control" id="supportMessage" name="pesan_dukungan" maxlength="500" rows="2"></textarea>
            <div class="mt-3 p-3 rounded-3" style="background:var(--cream)">Total simulasi: <strong data-vote-price></strong></div>
            <div class="form-text mt-2">Pembayaran QRIS belum terintegrasi. Vote akan menunggu verifikasi admin.</div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-gold">Lanjutkan</button></div>
        </form>
      </div></div>
    </div>
  <?php endif; ?>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-contestant]').forEach(function (button) {
    button.addEventListener('click', function () {
      document.getElementById('paidContestant').value = button.dataset.contestant;
      document.getElementById('paidContestantName').textContent = button.dataset.name;
    });
  });
  var requested = <?= (int) $requestedContestant ?>;
  var candidateButton = document.querySelector('[data-contestant="' + requested + '"]');
  if (requested && candidateButton) candidateButton.click();
});
</script>
<?php render_footer(); ?>
