<?php
require_once __DIR__ . '/security.php';
app_start_session();
require_once __DIR__ . '/db_connect.php';
require_active_account($conn, false);

header('Content-Type: text/html; charset=UTF-8');

$limit = 10;
$page = max(1, (int)($_POST['page'] ?? 1));
$startFrom = ($page - 1) * $limit;
$schoolId = current_school_id();
$tahun = trim((string)($_POST['tahun'] ?? ''));
$kelas = trim((string)($_POST['kelas'] ?? ''));
$keyword = trim((string)($_POST['keyword'] ?? ''));

$conditions = ['school_id = ?'];
$types = 'i';
$params = [$schoolId];

if ($tahun !== '' && $tahun !== 'Pilih...') {
    $conditions[] = 'darjah = ?';
    $types .= 's';
    $params[] = $tahun;
}
if ($kelas !== '' && $kelas !== 'Pilih...') {
    $conditions[] = 'nama_kelas = ?';
    $types .= 's';
    $params[] = $kelas;
}
if ($keyword !== '') {
    $conditions[] = '(nama_penuh LIKE ? OR rfid_uid LIKE ?)';
    $types .= 'ss';
    $like = '%' . $keyword . '%';
    $params[] = $like;
    $params[] = $like;
}

$where = implode(' AND ', $conditions);
$sql = "SELECT id, rfid_uid, nama_penuh, darjah, nama_kelas, status FROM pelajar WHERE $where ORDER BY status DESC, nama_kelas ASC, nama_penuh ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$listTypes = $types . 'ii';
$listParams = [...$params, $limit, $startFrom];
$stmt->bind_param($listTypes, ...$listParams);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<tr><td colspan='6' class='text-center'>Tiada rekod pelajar dijumpai.</td></tr>";
    $stmt->close();
    exit;
}

$bil = $startFrom + 1;
$canEdit = in_array((string)($_SESSION['role'] ?? ''), ['admin', 'gpk'], true);
$canDelete = (($_SESSION['role'] ?? '') === 'admin');
while ($row = $result->fetch_assoc()) {
    $nama = escape_html($row['nama_penuh']);
    $rfid = escape_html($row['rfid_uid']);
    $kelasText = escape_html($row['nama_kelas']);
    $darjah = escape_html($row['darjah']);
    $status = (int)$row['status'];
    $statusLabel = $status === 1 ? '<span class="badge badge-success">Layak</span>' : '<span class="badge badge-danger">Tidak Layak</span>';
    $rowStyle = $status === 1 ? '' : 'background-color:#fce8e6;';
    echo "<tr style='$rowStyle'>";
    echo '<td>' . $bil++ . '</td>';
    echo "<td>$nama<br><small>$statusLabel</small></td>";
    echo "<td>$rfid</td><td>" . ($kelasText !== '' ? $kelasText : '<span class="text-danger">Tiada Kelas</span>') . "</td><td>Tahun $darjah</td><td>";
    if ($canEdit) {
        echo "<button type='button' class='btn btn-warning btn-xs btn_edit_modal' data-id='$rfid' data-nama='$nama' data-darjah='$darjah' data-kelas='$kelasText' data-status='$status' title='Edit Pelajar'><i class='fa fa-pencil'></i></button> ";
    }
    if ($canDelete) {
        echo "<button type='button' class='btn btn-danger btn-xs btn_delete_modal' data-id='$rfid' title='Nyahaktif'><i class='fa fa-trash'></i></button>";
    }
    echo '</td></tr>';
}
$stmt->close();

$countSql = "SELECT COUNT(*) AS total FROM pelajar WHERE $where";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = (int)ceil($total / $limit);

if ($totalPages > 1) {
    echo "<tr><td colspan='6' class='text-center bg-light'>";
    for ($i = 1; $i <= $totalPages; $i++) {
        $class = $i === $page ? 'btn-primary' : 'btn-default';
        echo "<button type='button' class='btn btn-sm $class pagination_link' id='$i'>$i</button> ";
    }
    echo '</td></tr>';
}
