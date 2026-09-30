<?php
http_response_code(410);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['status' => 'error', 'msg' => 'Endpoint lama telah ditutup. Gunakan process_rmt.php dengan pengesahan peranti.']);
