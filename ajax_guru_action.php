<?php
require_once __DIR__ . '/security.php';
app_start_session();
header('Content-Type: application/json');

include 'db_connect.php';
include 'functions.php';

require_active_account($conn, true);
require_csrf(true);

$sch_id = current_school_id();
$current_role = $_SESSION['role'] ?? '';
$action = $_POST['action'] ?? '';

// ==========================================
// 0. SENARAI GURU (HTML ROWS)
// ==========================================
if ($action === 'list') {
    header('Content-Type: text/html');
    $sql = "SELECT * FROM users WHERE jawatan != 'Developer' AND role != 'superadmin' AND school_id = '$sch_id' ORDER BY role ASC, nama_penuh ASC";
    $result = mysqli_query($conn, $sql);
    $bil = 1;

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $bil++ . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_penuh']) . "</td>";
            
            if ($current_role === 'admin') {
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            }
            
            $badge_color = 'bg-secondary';
            if ($row['role'] === 'admin') $badge_color = 'bg-danger';
            if ($row['role'] === 'gpk')   $badge_color = 'bg-warning text-dark';
            if ($row['role'] === 'guru')  $badge_color = 'bg-info text-white';
            
            echo "<td><span class='badge $badge_color'>" . htmlspecialchars($row['jawatan']) . "</span></td>";
            echo "<td>" . htmlspecialchars($row['no_tel']) . "</td>";
            
            if ($current_role === 'admin') {
                echo "<td class='text-center'>
                        <button class='btn btn-warning btn-sm btn_edit_guru mr-1' 
                           data-id='" . $row['id'] . "'
                           data-nama='" . htmlspecialchars($row['nama_penuh'], ENT_QUOTES) . "'
                           data-username='" . htmlspecialchars($row['username'], ENT_QUOTES) . "' 
                           data-email='" . htmlspecialchars($row['email'] ?? '', ENT_QUOTES) . "'
                           data-tel='" . htmlspecialchars($row['no_tel'], ENT_QUOTES) . "'
                           data-jawatan='" . htmlspecialchars($row['jawatan'], ENT_QUOTES) . "'>
                           <i class='fa fa-pencil'></i>
                        </button>
                        <button class='btn btn-danger btn-sm btn_delete_guru' 
                           data-id='" . $row['id'] . "'
                           data-nama='" . htmlspecialchars($row['nama_penuh'], ENT_QUOTES) . "'>
                           <i class='fa fa-trash'></i>
                        </button>
                      </td>";
            }
            echo "</tr>";
        }
    } else {
        $colspan = ($current_role === 'admin') ? 6 : 4;
        echo "<tr><td colspan='$colspan' class='text-center text-muted'>Tiada data guru.</td></tr>";
    }
    exit;
}

// ==========================================
// 1. TAMBAH GURU BARU
// ==========================================
if ($action === 'add') {
    if ($current_role !== 'admin' && $current_role !== 'gpk') {
        echo json_encode(['status' => 'error', 'msg' => 'Tiada kebenaran untuk menambah guru.']);
        exit;
    }

    $nama_penuh = trim($_POST['nama_penuh'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password_input = (string)($_POST['password'] ?? '');
    $password   = password_hash($password_input, PASSWORD_DEFAULT);
    $no_tel     = trim($_POST['no_tel'] ?? '');
    $jawatan    = trim($_POST['jawatan'] ?? 'Guru Biasa');
    if (!in_array($jawatan, ['Guru Biasa', 'Guru Besar', 'Guru Penolong Kanan'], true)) {
        json_response(['status' => 'error', 'msg' => 'Jawatan tidak sah.'], 422);
    }

    if (empty($nama_penuh) || empty($username)) {
        echo json_encode(['status' => 'error', 'msg' => 'Nama Penuh dan No. Kad Pengenalan wajib diisi!']);
        exit;
    }
    if (strlen($password_input) < 10) {
        json_response(['status' => 'error', 'msg' => 'Kata laluan mesti sekurang-kurangnya 10 aksara.'], 422);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['status' => 'error', 'msg' => 'Alamat e-mel tidak sah.'], 422);
    }

    // Semak duplikasi username (No IC)
    $check_stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $check_stmt->bind_param('s', $username);
    $check_stmt->execute();
    $result_check = $check_stmt->get_result();
    if (mysqli_num_rows($result_check) > 0) {
        $check_stmt->close();
        echo json_encode(['status' => 'error', 'msg' => "No. Kad Pengenalan '$username' sudah didaftarkan dalam sistem!"]);
        exit;
    }
    $check_stmt->close();

    if ($jawatan === 'Guru Besar') {
        $role = 'admin';
    } elseif ($jawatan === 'Guru Penolong Kanan') {
        $role = 'gpk';
    } else {
        $role = 'guru';
    }

    if ($current_role === 'gpk' && $role !== 'guru') {
        json_response(['status' => 'error', 'msg' => 'GPK hanya dibenarkan mencipta akaun guru biasa.'], 403);
    }

    $emailValue = $email !== '' ? $email : null;
    $stmt = $conn->prepare('INSERT INTO users (school_id, nama_penuh, username, email, password, role, no_tel, jawatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssssss', $sch_id, $nama_penuh, $username, $emailValue, $password, $role, $no_tel, $jawatan);
    if ($stmt->execute()) {
        catat_log($conn, $_SESSION['user_id'], $_SESSION['nama_penuh'], "Menambah guru baru: $nama_penuh ($jawatan)");
        echo json_encode(['status' => 'success', 'msg' => 'Berjaya menambah guru baharu!']);
    } else {
        error_log('Add teacher failed: ' . $stmt->error);
        echo json_encode(['status' => 'error', 'msg' => 'Guru tidak dapat ditambah. Username atau e-mel mungkin telah digunakan.']);
    }
    $stmt->close();
    exit;
}

// ==========================================
// 2. KEMASKINI GURU
// ==========================================
if ($action === 'update') {
    if ($current_role !== 'admin') {
        echo json_encode(['status' => 'error', 'msg' => 'Hanya Admin dibenarkan mengemaskini maklumat guru.']);
        exit;
    }

    $id         = (int)($_POST['id_guru'] ?? 0);
    $nama_penuh = trim($_POST['nama_penuh'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $no_tel     = trim($_POST['no_tel'] ?? '');
    $jawatan    = trim($_POST['jawatan'] ?? 'Guru Biasa');
    if (!in_array($jawatan, ['Guru Biasa', 'Guru Besar', 'Guru Penolong Kanan'], true)) {
        json_response(['status' => 'error', 'msg' => 'Jawatan tidak sah.'], 422);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['status' => 'error', 'msg' => 'Alamat e-mel tidak sah.'], 422);
    }

    if (empty($id) || empty($nama_penuh) || empty($username)) {
        echo json_encode(['status' => 'error', 'msg' => 'Maklumat tidak lengkap.']);
        exit;
    }

    $targetStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND school_id = ? AND role != 'superadmin' AND COALESCE(jawatan, '') != 'Developer' LIMIT 1");
    $targetStmt->bind_param('ii', $id, $sch_id);
    $targetStmt->execute();
    $targetExists = $targetStmt->get_result()->num_rows === 1;
    $targetStmt->close();
    if (!$targetExists) {
        json_response(['status' => 'error', 'msg' => 'Akaun guru tidak dijumpai untuk sekolah ini.'], 404);
    }

    // Check jika username bertukar dan telah digunakan oleh user lain
    $check_stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
    $check_stmt->bind_param('si', $username, $id);
    $check_stmt->execute();
    $check = $check_stmt->get_result();
    if (mysqli_num_rows($check) > 0) {
        $check_stmt->close();
        echo json_encode(['status' => 'error', 'msg' => "No. Kad Pengenalan '$username' sudah digunakan oleh akaun lain!"]);
        exit;
    }
    $check_stmt->close();

    if ($jawatan === 'Guru Besar') {
        $role = 'admin';
    } elseif ($jawatan === 'Guru Penolong Kanan') {
        $role = 'gpk';
    } else {
        $role = 'guru';
    }

    $emailValue = $email !== '' ? $email : null;
    $stmt = $conn->prepare('UPDATE users SET nama_penuh = ?, username = ?, email = ?, no_tel = ?, jawatan = ?, role = ? WHERE id = ? AND school_id = ?');
    $stmt->bind_param('ssssssii', $nama_penuh, $username, $emailValue, $no_tel, $jawatan, $role, $id, $sch_id);
    if ($stmt->execute()) {
        catat_log($conn, $_SESSION['user_id'], $_SESSION['nama_penuh'], "Mengemaskini maklumat guru: $nama_penuh");
        echo json_encode(['status' => 'success', 'msg' => 'Maklumat guru berjaya dikemaskini!']);
    } else {
        error_log('Update teacher failed: ' . $stmt->error);
        echo json_encode(['status' => 'error', 'msg' => 'Maklumat guru tidak dapat dikemaskini.']);
    }
    $stmt->close();
    exit;
}

// ==========================================
// 3. PADAM GURU
// ==========================================
if ($action === 'delete') {
    if ($current_role !== 'admin') {
        echo json_encode(['status' => 'error', 'msg' => 'Hanya Admin dibenarkan memadam guru.']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'msg' => 'ID guru tidak sah.']);
        exit;
    }

    if ($id == $_SESSION['user_id']) {
        echo json_encode(['status' => 'error', 'msg' => 'Anda tidak boleh memadam akaun anda sendiri!']);
        exit;
    }

    $stmt = $conn->prepare("SELECT nama_penuh FROM users WHERE id = ? AND school_id = ? AND role != 'superadmin' AND COALESCE(jawatan, '') != 'Developer' LIMIT 1");
    $stmt->bind_param('ii', $id, $sch_id);
    $stmt->execute();
    $guru = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$guru) {
        json_response(['status' => 'error', 'msg' => 'Akaun guru tidak dijumpai untuk sekolah ini.'], 404);
    }
    $nama_guru = $guru['nama_penuh'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND school_id = ? AND role != 'superadmin' AND COALESCE(jawatan, '') != 'Developer'");
    $stmt->bind_param('ii', $id, $sch_id);
    if ($stmt->execute() && $stmt->affected_rows === 1) {
        catat_log($conn, $_SESSION['user_id'], $_SESSION['nama_penuh'], "Memadam guru: $nama_guru");
        echo json_encode(['status' => 'success', 'msg' => 'Guru berjaya dipadam!']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Gagal memadam guru.']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Tindakan tidak sah.']);
exit;
