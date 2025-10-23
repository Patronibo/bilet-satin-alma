<?php
// includes/db.php
declare(strict_types=1);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // database dizinine göre path -> includes klasöründen bir üst
    $dbPath = __DIR__ . '/../database/bilet_sistem.db';
    // tercih: gerçek yolu al (mutlak)
    $dbFile = realpath($dbPath) ?: $dbPath;

    try {
        $pdo = new PDO('sqlite:' . $dbFile);
        // Hata modu: exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // fetch modu
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // emulate prepares false (daha güvenli)
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        // Foreign key'leri aç
        $pdo->exec('PRAGMA foreign_keys = ON;');
        // yazma sırasında bekleme süresi (ms)
        $pdo->exec('PRAGMA busy_timeout = 5000;');

        return $pdo;
    } catch (PDOException $e) {
        // geliştirme ortamında detayı göster; prod için log yaz
        die("Veritabanı bağlantı hatası: " . $e->getMessage());
    }
}
