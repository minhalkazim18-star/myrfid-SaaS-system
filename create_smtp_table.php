<?php
http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
echo "Installer web telah ditutup. Gunakan database/migrate_security.sql melalui CLI.\n";
