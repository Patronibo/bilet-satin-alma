<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /login.php');
    exit;
}

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = getDB();

// Kullanıcı bilgileri
$stmt = $db->prepare('SELECT full_name, email, balance FROM User WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Biletler
$ticketsStmt = $db->prepare("
    SELECT 
        t.id AS ticket_id,
        t.status,
        t.total_price,
        t.created_at,
        tr.departure_city,
        tr.destination_city,
        tr.departure_time,
        tr.arrival_time,
        GROUP_CONCAT(bs.seat_number, ', ') AS seats
    FROM Tickets t
    JOIN Trips tr ON t.trip_id = tr.id
    LEFT JOIN Booked_Seats bs ON bs.ticket_id = t.id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$ticketsStmt->execute([$_SESSION['user_id']]);
$tickets = $ticketsStmt->fetchAll(PDO::FETCH_ASSOC);

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profilim</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(to bottom, #1c1c1e 0%, #f5f5f7 60%, #ebebef 100%);
            min-height: 100vh;
            color: #1c1c1e;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(16px);
            padding: 20px 30px;
            border-radius: 18px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card {
            background: rgba(255,255,255,0.92);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .balance { font-size: 2rem; color: #0071e3; font-weight: 700; }
        .ticket-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 10px 16px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #0071e3; color: #fff; }
        .btn-primary:hover { background: #005bb5; transform: translateY(-2px); }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #bb2d3b; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .badge { padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge.active { background: #dcfce7; color: #166534; }
        .badge.canceled { background: #fee2e2; color: #991b1b; }
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= e($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= e($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="header">
            <div>
                <h1>👤 Profilim</h1>
                <p><?= e($user['full_name']) ?> (<?= e($user['email']) ?>)</p>
            </div>
            <div style="text-align:right;">
                <div style="background:linear-gradient(135deg,#10b981,#059669);color:white;padding:12px 24px;border-radius:12px;margin-bottom:12px;box-shadow:0 4px 12px rgba(16,185,129,0.3);">
                    <div style="font-size:12px;opacity:0.9;margin-bottom:4px;">💰 Bakiyeniz</div>
                    <div style="font-size:24px;font-weight:700;"><?= number_format((int)$user['balance'], 2) ?> ₺</div>
                </div>
                <a href="/index.php" class="btn btn-secondary">Ana Sayfa</a>
                <a href="/logout.php" class="btn btn-danger">Çıkış</a>
            </div>
        </div>

        <div class="card">
            <h2>🎫 Biletlerim</h2>
            <?php if (empty($tickets)): ?>
                <p>Henüz bilet satın almadınız.</p>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card">
                        <div>
                            <div style="font-weight:600; font-size:1.1rem;">
                                <?= e($ticket['departure_city']) ?> → <?= e($ticket['destination_city']) ?>
                            </div>
                            <div style="color:#555; margin-top:6px;">
                                ⏰ <?= e($ticket['departure_time']) ?> → <?= e($ticket['arrival_time']) ?>
                            </div>
                            <div style="color:#777; margin-top:4px;">
                                Koltuklar: <b><?= e($ticket['seats']) ?></b> • 
                                Tutar: <b><?= number_format((int)$ticket['total_price'], 2) ?> ₺</b>
                            </div>
                            <div style="margin-top:8px;">
                                <span class="badge <?= $ticket['status'] === 'active' ? 'active' : 'canceled' ?>">
                                    <?= e($ticket['status']) ?>
                                </span>
                                <?php if ($ticket['status'] === 'active'): ?>
                                    <?php
                                        $purchaseTime = strtotime($ticket['created_at']);
                                        $now = time();
                                        $hoursSincePurchase = ($now - $purchaseTime) / 3600;
                                        $minutesLeft = max(0, (1 - $hoursSincePurchase) * 60);
                                        
                                        if ($minutesLeft > 0):
                                    ?>
                                        <span style="display:inline-block; margin-left:8px; padding:4px 10px; background:#fef3c7; color:#92400e; border-radius:999px; font-size:11px; font-weight:600;">
                                            ⏱️ Ücretsiz iptal: <?= (int)$minutesLeft ?> dk
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display:flex; gap:10px; flex-direction:column;">
                            <?php if ($ticket['status'] === 'active'): ?>
                                <button class="btn btn-primary" 
                                        onclick="downloadTicket('<?= e($ticket['ticket_id']) ?>')">
                                    🔒 PDF İndir (Güvenli)
                                </button>
                                <form method="post" action="cancel_ticket.php" style="display:inline;" class="cancel-form">
                                    <input type="hidden" name="ticket_id" value="<?= e($ticket['ticket_id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                    <button type="button" class="btn btn-danger" 
                                            onclick="showCancelConfirm(this.form)">İptal Et</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 30px; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h3 style="margin: 0 0 15px 0; color: #dc2626;">⚠️ Bilet İptali</h3>
            <p style="margin: 0 0 25px 0; color: #374151;">Bu bileti iptal etmek istediğinize emin misiniz?</p>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeCancelConfirm()" style="padding: 10px 20px; border: 1px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer; font-weight: 600;">Hayır</button>
                <button id="confirmCancelBtn" style="padding: 10px 20px; border: none; background: #dc2626; color: white; border-radius: 6px; cursor: pointer; font-weight: 600;">Evet, İptal Et</button>
            </div>
        </div>
    </div>

    <script>
        let pendingCancelForm = null;

        function showCancelConfirm(form) {
            pendingCancelForm = form;
            document.getElementById('confirmModal').style.display = 'flex';
        }

        function closeCancelConfirm() {
            document.getElementById('confirmModal').style.display = 'none';
            pendingCancelForm = null;
        }

        document.getElementById('confirmCancelBtn').addEventListener('click', function() {
            if (pendingCancelForm) {
                pendingCancelForm.submit();
            }
            closeCancelConfirm();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCancelConfirm();
            }
        });

async function downloadTicket(_0xa1){try{const _0xb2=new FormData();_0xb2.append('ticket_id',_0xa1);const _0xc3=await fetch('generate_ticket_link.php',{method:'POST',body:_0xb2}),_0xd4=await _0xc3.json();_0xd4.success?window.open(_0xd4.url,'_blank'):alert('Hata: '+(_0xd4.error||'Bilet indirilemedi'))}catch(_0xe5){console.error('İndirme hatası:',_0xe5);alert('Bilet indirilirken bir hata oluştu')}}
    </script>
</body>
</html>
