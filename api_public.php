<?php
require_once __DIR__ . '/security.php';
app_start_session();
header('Content-Type: application/json');
include 'db_connect.php';
require_csrf(true);

$action = $_POST['action'] ?? '';

if ($action === 'register_public') {
    $kod = trim($_POST['kod_sekolah'] ?? '');
    $nama = trim($_POST['nama_sekolah'] ?? '');
    $email = trim($_POST['email_sekolah'] ?? '');
    $notel = trim($_POST['no_tel'] ?? '');

    $a_nama = trim($_POST['admin_nama'] ?? '');
    $a_email = trim($_POST['admin_email'] ?? '');
    $a_user = trim($_POST['admin_username'] ?? '');
    $a_pass = trim($_POST['admin_password'] ?? '');

    $anggaran_murid = (int)($_POST['anggaran_murid'] ?? 0);
    $pelan = trim($_POST['pelan'] ?? 'Basic');

    if (empty($kod) || empty($nama) || empty($email) || empty($a_nama) || empty($a_email) || empty($a_user) || empty($a_pass)) {
        json_response(['status' => 'error', 'msg' => 'Sila isi semua ruangan yang bertanda *'], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !filter_var($a_email, FILTER_VALIDATE_EMAIL)) {
        json_response(['status' => 'error', 'msg' => 'Alamat e-mel tidak sah.'], 422);
    }
    if (!in_array($pelan, ['Basic', 'Pro'], true)) {
        json_response(['status' => 'error', 'msg' => 'Pelan tidak sah.'], 422);
    }
    if (strlen($a_pass) < 10) {
        json_response(['status' => 'error', 'msg' => 'Kata laluan mesti sekurang-kurangnya 10 aksara.'], 422);
    }
    if (!preg_match('/^[A-Za-z0-9._-]{2,20}$/', $kod)
        || !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $a_user)
        || mb_strlen($nama) > 150 || mb_strlen($a_nama) > 100
        || mb_strlen($notel) > 20 || mb_strlen($a_pass) > 128) {
        json_response(['status' => 'error', 'msg' => 'Format atau panjang maklumat yang dimasukkan tidak sah.'], 422);
    }
    if (login_rate_limited($conn, 'register-ip', 5, 60) || login_rate_limited($conn, 'register:' . $a_email, 3, 60)) {
        json_response(['status' => 'error', 'msg' => 'Terlalu banyak permohonan. Sila cuba semula kemudian.'], 429);
    }
    record_login_failure($conn, 'register-ip', 60);
    record_login_failure($conn, 'register:' . $a_email, 60);

    // Semak jika kod sekolah sudah wujud
    $stmt = $conn->prepare('SELECT id FROM sekolah WHERE kod_sekolah = ? LIMIT 1');
    $stmt->bind_param('s', $kod);
    $stmt->execute();
    $chk = $stmt->get_result();
    if (mysqli_num_rows($chk) > 0) {
        json_response(['status' => 'error', 'msg' => 'Kod sekolah sudah wujud dalam sistem.'], 409);
    }

    // Semak username sudah wujud
    $stmt->close();
    $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $a_user);
    $stmt->execute();
    $chk2 = $stmt->get_result();
    if (mysqli_num_rows($chk2) > 0) {
        json_response(['status' => 'error', 'msg' => 'Username pentadbir sudah digunakan. Sila pilih yang lain.'], 409);
    }
    $stmt->close();

    $hashed_pass = password_hash($a_pass, PASSWORD_DEFAULT);

    mysqli_begin_transaction($conn);
    try {
        // Insert sekolah with pending status
        $status = 'pending';
        $stmt = $conn->prepare('INSERT INTO sekolah (kod_sekolah, nama_sekolah, email_sekolah, no_tel, pelan, anggaran_murid, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssis', $kod, $nama, $email, $notel, $pelan, $anggaran_murid, $status);
        if (!$stmt->execute()) {
            throw new RuntimeException('School registration insert failed: ' . $stmt->error);
        }
        $sch_id = mysqli_insert_id($conn);
        $stmt->close();

        // Insert admin user
        $role = 'admin';
        $stmt = $conn->prepare('INSERT INTO users (school_id, nama_penuh, email, username, password, role) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssss', $sch_id, $a_nama, $a_email, $a_user, $hashed_pass, $role);
        if (!$stmt->execute()) {
            throw new RuntimeException('Admin registration insert failed: ' . $stmt->error);
        }
        $stmt->close();

        mysqli_commit($conn);
        json_response(['status' => 'success', 'msg' => 'Permohonan pendaftaran anda telah berjaya dihantar. Sila tunggu kelulusan dari Super Admin.'], 201);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log('Public school registration failed: ' . $e->getMessage());
        json_response(['status' => 'error', 'msg' => 'Permohonan tidak dapat dihantar. Kod sekolah, username atau e-mel mungkin telah digunakan.'], 409);
    }
    exit;
}

json_response(['status' => 'error', 'msg' => 'Aksi tidak sah.'], 400);
