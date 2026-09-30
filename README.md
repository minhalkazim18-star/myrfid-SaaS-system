# MyRFID / Sistem RMT

## Persediaan

1. Gunakan `.env.example` sebagai senarai konfigurasi environment pelayan. Jangan simpan rahsia sebenar dalam repository.
2. Tetapkan `APP_KEY` rawak sekurang-kurangnya 32 aksara, sambungan `DB_*`, dan `APP_URL` HTTPS.
3. Untuk Google OAuth, tetapkan `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` dan `GOOGLE_REDIRECT_URI`.
4. Jalankan migrasi hardening, kemudian daftar setiap terminal RFID di **Tetapan Sistem → Terminal RFID**. Simpan kunci yang dipaparkan sekali sahaja.
5. Jalankan `composer install --no-dev --optimize-autoloader` bagi deployment produksi.

Migrasi berikut ialah migrasi sekali jalan dan perlu dilaksanakan mengikut urutan:

```bash
mysql -u root myrfid < database/migrate_security.sql
mysql -u root myrfid < database/migrate_foreign_keys.sql
APP_ENV=development php database/migrate_security_hardening.php
```

`backup_database.sql` ialah salinan skema sahaja; ia tidak mengandungi data pengguna atau kata laluan contoh.

## Menjalankan secara tempatan

Gunakan router keselamatan yang disediakan supaya fail dalaman tidak boleh dimuat turun:

```bash
php -S 127.0.0.1:8000 router.php
```

Laman awam tersedia di `/`, manakala portal kakitangan di `/portal`.

Jangan gunakan `php -S ... -t .` tanpa `router.php`. Server terbina dalam PHP tidak membaca `.htaccess`.

## Deployment produksi

- Gunakan Apache 2.4+ dengan `mod_rewrite` dan `mod_headers`; pastikan `.htaccess` dibenarkan (`AllowOverride All`).
- Wajibkan HTTPS. Tetapkan `TRUST_PROXY_HEADERS=1` hanya di belakang reverse proxy yang dipercayai dan dikonfigurasi untuk memadam header klien asal.
- Pastikan `database/`, `vendor/`, `PHPMailer/` dan seluruh `storage/` tidak boleh dicapai terus dari web. Konfigurasi yang disediakan telah menyekat laluan ini.
- Pastikan `storage/private` dimiliki oleh akaun servis web, dengan folder mod `0750` dan fail mod `0640`.
- Jangan commit `.env`, token reset, kunci API atau fail yang mengandungi data produksi.

Terminal RFID perlu menghantar `school_id`, UID kad dan maklumat peranti melalui header—bukan query string. Imbasan hanya menerima `POST`:

```text
X-RFID-Device-ID: <ID_TERMINAL>
X-RFID-API-Key: <KUNCI_TERMINAL>

POST /api_listener.php
school_id=<ID_SEKOLAH>&rfid_uid=<UID_KAD>
```

## Semakan

```bash
composer validate --no-check-publish
composer audit --locked
find . -name '*.php' -not -path './vendor/*' -exec php -l {} \;
php tests/security_smoke.php
```
