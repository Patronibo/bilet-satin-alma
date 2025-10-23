<?php
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/../../includes/db.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$ticketId = (string)($_GET['ticket'] ?? '');

// Ticket ID yoksa veya geçersizse ana sayfaya yönlendir
if (empty($ticketId)) {
    header('Location: ../index.php');
    exit;
}

// Ticket'in varlığını ve kullanıcıya ait olduğunu kontrol et
$db = getDB();
$stmt = $db->prepare("SELECT id, total_price FROM Tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$ticketId, $_SESSION['user_id'] ?? '']);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    // Geçersiz ticket ID - ana sayfaya yönlendir
    header('Location: ../index.php');
    exit;
}

// Kullanıcının güncel bakiyesini al
$userStmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
$userStmt->execute([$_SESSION['user_id'] ?? '']);
$userBalance = $userStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>✅ Ödeme Başarılı</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      animation: gradientShift 10s ease infinite;
      background-size: 200% 200%;
    }
    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    .container { 
      background: white;
      border-radius: 24px;
      padding: 50px 40px;
      max-width: 650px;
      width: 100%;
      box-shadow: 0 30px 90px rgba(0, 0, 0, 0.3);
      text-align: center;
      animation: slideUp 0.6s ease-out;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .success-icon {
      font-size: 120px;
      margin-bottom: 20px;
      animation: scaleIn 0.5s ease-out 0.2s both, bounce 1s ease-in-out 0.7s;
    }
    @keyframes scaleIn {
      from { transform: scale(0); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }
    @keyframes bounce {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }
    h2 {
      color: #10b981;
      font-size: 2.5rem;
      margin-bottom: 15px;
      animation: fadeIn 0.5s ease-out 0.4s both;
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .subtitle {
      color: #6b7280;
      font-size: 1.15rem;
      margin-bottom: 35px;
      animation: fadeIn 0.5s ease-out 0.6s both;
    }
    .ticket-info {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      padding: 25px;
      border-radius: 16px;
      margin-bottom: 30px;
      border-left: 5px solid #10b981;
      animation: fadeIn 0.5s ease-out 0.8s both;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    .ticket-label {
      font-size: 0.95rem;
      color: #6b7280;
      font-weight: 600;
      margin-bottom: 8px;
    }
    .ticket-number {
      font-size: 1.4rem;
      color: #0071e3;
      font-weight: 700;
      word-break: break-all;
    }
    .info-text {
      font-size: 1.05rem;
      color: #4b5563;
      margin-bottom: 30px;
      animation: fadeIn 0.5s ease-out 1s both;
    }
    .btn-container {
      display: flex;
      flex-direction: column;
      gap: 12px;
      animation: fadeIn 0.5s ease-out 1.2s both;
    }
    .btn { 
      padding: 16px 24px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      font-size: 1.05rem;
      transition: all 0.3s ease;
      display: block;
      width: 100%;
    }
    .btn-success {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
    }
    .btn-success:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
    }
    .btn-primary {
      background: #0071e3;
      color: white;
    }
    .btn-primary:hover {
      background: #0077ed;
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(0, 113, 227, 0.4);
    }
    .btn-secondary {
      background: #6b7280;
      color: white;
    }
    .btn-secondary:hover {
      background: #4b5563;
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(107, 114, 128, 0.4);
    }
    @media (max-width: 600px) {
      .container { padding: 35px 25px; }
      h2 { font-size: 2rem; }
      .success-icon { font-size: 90px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="success-icon">🎉</div>
    <h2>Ödeme Başarılı!</h2>
    <p class="subtitle">İşleminiz başarıyla tamamlandı</p>
    
        <div class="ticket-info">
            <div class="ticket-label">🎫 Bilet Numaranız</div>
            <div class="ticket-number"><?= e($ticketId) ?></div>
        </div>
        
        <?php if ($userBalance !== false): ?>
        <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 15px; border-radius: 12px; margin-top: 20px;">
            <div style="color: #1e40af; font-size: 1rem; font-weight: 600;">
                💰 Yeni Bakiyeniz: <strong><?= number_format((float)$userBalance, 2) ?> ₺</strong>
            </div>
        </div>
        <?php endif; ?>
    
    <p class="info-text">
      Biletiniz başarıyla oluşturuldu. Aşağıdaki seçenekleri kullanabilirsiniz:
    </p>
    
    <div class="btn-container">
      <a class="btn btn-primary" href="../index.php">
        🏠 Ana Sayfaya Dön
      </a>
      <a class="btn btn-secondary" href="/user/profile.php">
        👤 Profilim / Biletlerim
      </a>
    </div>
  </div>
</body>
</html>


