<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(403);
    exit('Yetkisiz erişim');
}

// POST veya GET ile ticket_id alabilir (JavaScript'siz sistem)
$ticketId = $_POST['ticket_id'] ?? $_GET['ticket_id'] ?? '';
if ($ticketId === '') {
    exit('Bilet ID gerekli');
}

$db = getDB();

// Bilet bilgilerini çek
$stmt = $db->prepare("
    SELECT 
        t.id,
        t.total_price,
        t.created_at,
        t.status,
        tr.departure_city,
        tr.destination_city,
        tr.departure_time,
        tr.arrival_time,
        u.full_name,
        u.email,
        bc.name AS company_name,
        GROUP_CONCAT(bs.seat_number, ', ') AS seats
    FROM Tickets t
    JOIN Trips tr ON t.trip_id = tr.id
    JOIN User u ON t.user_id = u.id
    JOIN Bus_Company bc ON tr.company_id = bc.id
    LEFT JOIN Booked_Seats bs ON bs.ticket_id = t.id
    WHERE t.id = ? AND t.user_id = ?
    GROUP BY t.id
");
$stmt->execute([$ticketId, $_SESSION['user_id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    exit('Bilet bulunamadı');
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet - <?= e($ticketId) ?></title>
    <style>
        @page { size: A4; margin: 15mm; }
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .ticket { box-shadow: none !important; page-break-after: avoid; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .ticket {
            background: white;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .company {
            font-size: 18px;
            color: #555;
        }
        .route-box {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        .route {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .datetime {
            font-size: 14px;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .info-label {
            width: 150px;
            font-weight: 600;
            color: #666;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            background: #dcfce7;
            color: #166534;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px dashed #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
            line-height: 1.6;
        }
        .footer strong {
            color: #667eea;
            font-size: 14px;
        }
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(102,126,234,0.3);
            transition: 0.3s;
        }
        .btn-print:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .print-info {
            position: fixed;
            top: 80px;
            right: 20px;
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px 20px;
            border-radius: 8px;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 13px;
            line-height: 1.5;
        }
        .print-info strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #664d03;
        }
        @media print {
            .print-info { display: none !important; }
        }
        .notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #28a745;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            display: none;
            z-index: 10000;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { transform: translateX(-50%) translateY(-100px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        .notification.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="notification no-print" id="notification">
        ✅ PDF başarıyla hazırlandı!
    </div>
    
    <button class="btn-print no-print" onclick="window.print()">🖨️ PDF Olarak Kaydet</button>
    
    <div class="print-info no-print">
        <strong>💡 PDF Nasıl İndirilir?</strong>
        1. Yazdırma diyaloğunda<br>
        2. <strong>"Hedef"</strong> → <strong>"PDF olarak kaydet"</strong> seçin<br>
        3. <strong>"Kaydet"</strong> butonuna tıklayın
    </div>
    
    <div class="ticket">
        <div class="header">
            <h1>🚌 OTOBÜS BİLETİ</h1>
            <div class="company"><?= e($ticket['company_name']) ?></div>
        </div>

        <div class="route-box">
            <div class="route">
                <?= e($ticket['departure_city']) ?> → <?= e($ticket['destination_city']) ?>
            </div>
            <div class="datetime">
                Kalkış: <strong><?= e(date('d.m.Y H:i', strtotime($ticket['departure_time']))) ?></strong>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                Varış: <strong><?= e(date('d.m.Y H:i', strtotime($ticket['arrival_time']))) ?></strong>
            </div>
        </div>

        <div class="section">
            <div class="section-title">YOLCU BİLGİLERİ</div>
            <div class="info-row">
                <div class="info-label">Ad Soyad:</div>
                <div class="info-value"><?= e($ticket['full_name']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">E-posta:</div>
                <div class="info-value"><?= e($ticket['email']) ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">BİLET DETAYLARI</div>
            <div class="info-row">
                <div class="info-label">Bilet No:</div>
                <div class="info-value"><?= e($ticket['id']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Satın Alma:</div>
                <div class="info-value"><?= e(date('d.m.Y H:i', strtotime($ticket['created_at']))) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Koltuk Numaraları:</div>
                <div class="info-value"><?= e($ticket['seats']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Toplam Ücret:</div>
                <div class="info-value"><strong><?= e(number_format((int)$ticket['total_price'], 2)) ?> ₺</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Durum:</div>
                <div class="info-value"><span class="status"><?= e(strtoupper($ticket['status'])) ?></span></div>
            </div>
        </div>

        <div class="footer">
            <p><strong>Önemli Bilgilendirme</strong></p>
            <p>Bu bilet elektronik ortamda oluşturulmuştur.</p>
            <p>Seyahat esnasında yanınızda bulundurmanız gerekmektedir.</p>
            <p>Sefer saatinden en az 15 dakika önce terminalde bulunmanız rica olunur.</p>
            <p style="margin-top: 10px;"><strong style="font-size: 16px;">İyi yolculuklar dileriz! 🚍</strong></p>
        </div>
    </div>

    <script>
        // Sayfa yüklendiğinde otomatik yazdırma diyaloğunu aç
        window.onload = function() {
            setTimeout(function() {
                window.print();
                
                // Print diyaloğu kapatıldığında bilgi göster
                window.onafterprint = function() {
                    showNotification();
                }
            }, 500);
        }
        
        function showNotification() {
            var notification = document.getElementById('notification');
            notification.classList.add('show');
            
            // 3 saniye sonra otomatik gizle
            setTimeout(function() {
                notification.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>
