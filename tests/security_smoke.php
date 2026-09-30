<?php
declare(strict_types=1);

require_once __DIR__ . '/../security.php';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

putenv('APP_KEY=0123456789abcdef0123456789abcdef');
$encrypted = encrypt_secret('rahsia-ujian');
assert_true(str_starts_with($encrypted, 'enc:'), 'Rahsia tidak disulitkan.');
assert_true(decrypt_secret($encrypted) === 'rahsia-ujian', 'Rahsia tidak boleh dinyahsulitkan.');
assert_true(valid_iso_date('2028-02-29', '') === '2028-02-29', 'Tarikh lompat sah ditolak.');
assert_true(valid_iso_date('2026-02-29', '') === '', 'Tarikh tidak sah diterima.');
assert_true(escape_html('<script>') === '&lt;script&gt;', 'HTML escape gagal.');
assert_true(valid_class_name('1 Amanah'), 'Nama kelas dengan nombor ditolak.');
assert_true(valid_class_name("Pra-Sekolah A"), 'Nama kelas dengan sengkang ditolak.');
assert_true(valid_class_name('Ibnu Sina (A)'), 'Nama kelas dengan kurungan ditolak.');
assert_true(!valid_class_name('<script>'), 'Nama kelas berbahaya diterima.');
assert_true(!valid_class_name(str_repeat('A', 51)), 'Nama kelas terlalu panjang diterima.');

echo "Security smoke tests passed.\n";
