<?php
// init_db.php
require_once __DIR__ . '/includes/db.php';

$db = getDB();
$sql = file_get_contents(__DIR__ . '/database/schema.sql');

try {
    $db->exec($sql);
    echo "Schema başarıyla uygulandı.\n";
    echo "Admin hesabı: admin@bilet.com / admin123\n";
} catch (Exception $e) {
    echo "Schema uygulama hatası: " . $e->getMessage();
}
