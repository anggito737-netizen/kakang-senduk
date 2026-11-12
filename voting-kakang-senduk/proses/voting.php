<?php
require_once __DIR__ . '/../config/app.php';
require_role('pemilih');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('../pages/voting.php');
verify_csrf();

$action = $_POST['aksi'] ?? '';
$contestantId = filter_input(INPUT_POST, 'kontestan_id', FILTER_VALIDATE_INT);
$period = active_period($mysqli);
if (!$period || !$contestantId) {
    set_flash('Periode voting tidak aktif atau pilihan kontestan tidak valid.', 'warning');
    redirect('../pages/voting.php');
}

$stmt = $mysqli->prepare('SELECT id, kategori FROM kontestan WHERE id = ?');
$stmt->bind_param('i', $contestantId);
$stmt->execute();
$person = $stmt->get_result()->fetch_assoc();
if (!$person) {
    set_flash('Kontestan tidak ditemukan.', 'danger');
    redirect('../pages/voting.php');
}

$userId = current_user()['id'];
$periodId = (int) $period['id'];
if ($action === 'gratis') {
    try {
        $stmt = $mysqli->prepare("INSERT INTO voting (user_id, kontestan_id, kategori, jumlah_suara, jenis_vote, periode_id) VALUES (?, ?, ?, 1, 'gratis', ?)");
        $stmt->bind_param('iisi', $userId, $contestantId, $person['kategori'], $periodId);
        $stmt->execute();
        set_flash('Suara gratis Anda berhasil dicatat. Terima kasih atas dukungannya!');
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() === 1062) {
            set_flash('Anda sudah menggunakan suara gratis untuk kategori ini pada periode aktif.', 'warning');
        } else {
            throw $e;
        }
    }
    redirect('../pages/voting.php');
}

if ($action === 'berbayar') {
    $quantity = filter_input(INPUT_POST, 'jumlah_suara', FILTER_VALIDATE_INT);
    $message = trim($_POST['pesan_dukungan'] ?? '');
    if (!$quantity || $quantity < 1 || $quantity > 10000 || strlen($message) > 500) {
        set_flash('Jumlah suara harus 1-10.000 dan pesan maksimal 500 karakter.', 'danger');
        redirect('../pages/voting.php');
    }
    $price = 3500;
    $total = $quantity * $price;
    $stmt = $mysqli->prepare("INSERT INTO pembayaran_vote (user_id, kontestan_id, periode_id, kategori, jumlah_suara, total_harga, pesan_dukungan) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iiisiis', $userId, $contestantId, $periodId, $person['kategori'], $quantity, $total, $message);
    $stmt->execute();
    redirect('../pages/qris.php?id=' . $mysqli->insert_id);
}

http_response_code(400);
exit('Jenis voting tidak valid.');
