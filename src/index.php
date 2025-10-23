<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$db = getDB();

// Hata mesajını al ve temizle
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// Kullanıcı bilgilerini çek (giriş yapmışsa)
$userBalance = null;
$userName = null;
$userInitial = null;
if (!empty($_SESSION['user_id']) && $_SESSION['role'] === 'user') {
    $stmt = $db->prepare('SELECT balance, full_name FROM User WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userBalance = $user['balance'];
        $userName = $user['full_name'];
        $userInitial = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8');
    }
}

$seferler = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kalkis = trim($_POST['kalkis'] ?? '');
    $varis = trim($_POST['varis'] ?? '');

    $sql = "SELECT Trips.*, Bus_Company.name AS firma_adi
        FROM Trips
        INNER JOIN Bus_Company ON Trips.company_id = Bus_Company.id
        WHERE departure_city LIKE ? AND destination_city LIKE ?
        ORDER BY departure_time";
    $stmt = $db->prepare($sql);
    $stmt->execute(["%$kalkis%", "%$varis%"]);
    $seferler = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $seferler = $db->query("
    SELECT Trips.*, Bus_Company.name AS firma_adi
    FROM Trips
    INNER JOIN Bus_Company ON Trips.company_id = Bus_Company.id
    ORDER BY departure_time
")->fetchAll(PDO::FETCH_ASSOC);
}
?>


<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Siber Otobüs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
   <!-- 🚍 Emoji favicon -->
  <link rel="icon" href="data:image/svg+xml,
  <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'>
    <text y='.9em' font-size='90'>🚍</text>
  </svg>">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: linear-gradient(to bottom, #1c1c1e 0%, #f5f5f7 60%, #ebebef 100%);
      color: #1c1c1e;
      line-height: 1.6;
      min-height: 100vh;
    }

    header {
      background: transparent;
      color: #1d1d1f;
      padding: 1.5rem 2rem;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(16px);
      padding: 1rem 2rem;
      border-radius: 18px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.05);
    }

    header h2 {
      font-size: 1.5rem;
      font-weight: 600;
      letter-spacing: -0.02em;
      color: #1d1d1f;
    }

    nav {
      display: flex;
      align-items: center;
      gap: 1rem;
      font-size: 0.9rem;
    }

    nav span {
      color: #1d1d1f;
      font-weight: 500;
    }

    nav a {
      color: #fff;
      text-decoration: none;
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 8px;
      transition: all 0.3s ease;
      background: #0071e3;
    }

    nav a:hover {
      background: #0077ed;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3);
    }

    /* USER PROFILE DROPDOWN */
    .user-profile {
      position: relative;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: linear-gradient(135deg, #0071e3 0%, #0051a8 100%);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1.1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3);
      user-select: none;
    }

    .user-avatar:hover {
      transform: scale(1.1);
      box-shadow: 0 6px 20px rgba(0, 113, 227, 0.5);
    }

    .user-info {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }

    .user-name {
      font-weight: 600;
      color: #1d1d1f;
      font-size: 0.95rem;
    }

    .user-balance {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      padding: 4px 10px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.8rem;
      margin-top: 2px;
      box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
    }

    .dropdown-menu {
      position: absolute;
      top: calc(100% + 10px);
      right: 0;
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      padding: 8px;
      min-width: 220px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
    }

    .dropdown-menu.active {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .dropdown-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 14px;
      color: #1d1d1f;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s ease;
      font-weight: 500;
      font-size: 0.9rem;
    }

    .dropdown-item:hover {
      background: #f5f5f7;
    }

    .dropdown-item.logout {
      color: #dc2626;
      border-top: 1px solid #e5e7eb;
      margin-top: 4px;
    }

    .dropdown-item.logout:hover {
      background: #fef2f2;
    }

    .dropdown-icon {
      font-size: 1.2rem;
    }

    main {
      max-width: 1200px;
      margin: 2.5rem auto;
      padding: 0 1.5rem;
    }

    .search-section {
      background: rgba(255, 255, 255, 0.85);
      padding: 40px 35px;
      border-radius: 18px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.05);
      backdrop-filter: blur(16px);
      margin-bottom: 2rem;
      animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h3 {
      text-align: center;
      font-size: 1.75rem;
      margin-bottom: 1.5rem;
      color: #1d1d1f;
      font-weight: 600;
      letter-spacing: -0.02em;
    }

    form {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: flex-end;
    }

    .form-group {
      flex: 1;
      min-width: 200px;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      font-size: 0.875rem;
      color: #333;
    }

    input[type="text"], input[type="number"] {
      width: 100%;
      padding: 14px 16px;
      border: 1.8px solid #d2d2d7;
      border-radius: 10px;
      font-size: 15px;
      transition: all 0.3s ease;
      outline: none;
      background-color: #fafafa;
    }

    input[type="text"]:focus, input[type="number"]:focus {
      border-color: #0071e3;
      background-color: #fff;
      box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15);
    }

    button {
      padding: 14px 2rem;
      background: #0071e3;
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    button:hover {
      background: #0077ed;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 113, 227, 0.3);
    }

    button:active {
      transform: translateY(0);
    }

    .results-section {
      animation: fadeIn 0.7s ease 0.2s both;
    }

    .trip-card {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 18px;
      padding: 40px 35px;
      margin-bottom: 1.25rem;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.05);
      backdrop-filter: blur(16px);
      transition: all 0.3s ease;
    }

    .trip-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }

    .trip-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .company-name {
      font-weight: 600;
      font-size: 1.05rem;
      color: #0071e3;
      background: rgba(0, 113, 227, 0.1);
      padding: 8px 16px;
      border-radius: 8px;
      letter-spacing: -0.01em;
    }

    .trip-route {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex: 1;
      margin: 1rem 0;
    }

    .city {
      font-weight: 600;
      font-size: 1.2rem;
      color: #1d1d1f;
      letter-spacing: -0.01em;
    }

    .arrow {
      color: #6e6e73;
      font-size: 1.4rem;
    }

    .trip-details {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.5rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(0,0,0,0.08);
    }

    .detail-item {
      display: flex;
      flex-direction: column;
    }

    .detail-label {
      font-size: 0.8rem;
      color: #6e6e73;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }

    .detail-value {
      font-size: 1rem;
      font-weight: 600;
      color: #1d1d1f;
    }

    .price {
      font-size: 1.5rem;
      font-weight: 700;
      color: #0071e3;
    }

    .ticket-btn {
      padding: 12px 1.5rem;
      font-size: 0.95rem;
      background: #0071e3;
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .ticket-btn:hover {
      background: #0077ed;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 113, 227, 0.3);
    }

    .login-link {
      color: #0071e3;
      text-decoration: none;
      font-weight: 600;
      padding: 12px 1.5rem;
      background: rgba(0, 113, 227, 0.1);
      border-radius: 8px;
      transition: all 0.3s ease;
      display: inline-block;
    }

    .login-link:hover {
      background: rgba(0, 113, 227, 0.15);
      color: #005bb5;
      text-decoration: underline;
    }

    .no-results {
      text-align: center;
      padding: 3rem;
      color: #6e6e73;
      font-size: 1.1rem;
      background: rgba(255, 255, 255, 0.85);
      border-radius: 18px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.05);
      backdrop-filter: blur(16px);
    }

    /* SEFER DETAYLARI MODAL */
    .trip-details-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(4px);
    }

    .trip-details-modal.active {
      display: flex;
    }

    .modal-content {
      background: white;
      border-radius: 18px;
      padding: 35px;
      max-width: 600px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: translateY(-30px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid #e5e7eb;
    }

    .modal-header h3 {
      color: #1d1d1f;
      font-size: 1.5rem;
      margin: 0;
    }

    .modal-close {
      background: #f3f4f6;
      border: none;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      font-size: 1.3rem;
      color: #6b7280;
    }

    .modal-close:hover {
      background: #e5e7eb;
      color: #1d1d1f;
    }

    .modal-body {
      color: #374151;
      line-height: 1.8;
    }

    .modal-body p {
      margin-bottom: 15px;
    }

    .details-btn {
      padding: 8px 16px;
      font-size: 0.875rem;
      background: #f3f4f6;
      color: #374151;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .details-btn:hover {
      background: #e5e7eb;
      border-color: #9ca3af;
    }

    /* KOLTUK SEÇİMİ MODAL - TAM SIĞAN PROFESYONEL TASARIM */
    .seat-selection-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.6);
      z-index: 2000;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(8px);
      padding: 15px;
    }

    .seat-selection-modal.active {
      display: flex;
    }

    .seat-modal-content {
      background: white;
      border-radius: 20px;
      width: 95%;
      max-width: 1000px;
      height: 85vh;
      max-height: 650px;
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
      animation: modalSlideIn 0.4s ease-out;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .seat-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 18px 25px;
      border-bottom: 2px solid #e5e7eb;
      background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
      flex-shrink: 0;
    }

    .seat-modal-header h2 {
      margin: 0;
      font-size: 1.3rem;
      color: #1d1d1f;
    }

    .seat-modal-body {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 20px;
      padding: 20px;
      flex: 1;
      overflow: hidden;
      min-height: 0;
    }

    .seat-main-section {
      display: flex;
      flex-direction: column;
      overflow: hidden;
      min-height: 0;
    }

    .seat-main-section h3 {
      margin: 0 0 12px 0;
      font-size: 1rem;
      color: #1d1d1f;
    }

    .bus-layout-compact {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 12px;
      border: 2px solid #e5e7eb;
      position: relative;
      flex: 1;
      overflow-x: auto;
      overflow-y: hidden;
      min-height: 0;
    }

    .seats-container-compact {
      position: relative;
      height: 100%;
      min-height: 240px;
      margin-left: 5px;
    }

    .seat-compact {
      position: absolute;
      width: 38px;
      height: 38px;
      border: 2px solid #d1d5db;
      background: #e5e7eb;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 0.75rem;
      color: #374151;
      transition: all 0.2s ease;
      box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    }

    .seat-compact:hover:not(.male):not(.female) {
      transform: scale(1.1);
      border-color: #0071e3;
      box-shadow: 0 4px 8px rgba(0, 113, 227, 0.3);
    }

    .seat-compact.selected {
      background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
      border-color: #059669;
      color: white;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
      transform: scale(1.05);
    }

    .seat-compact.male {
      background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%);
      border-color: #3b82f6;
      color: #1e3a8a;
      cursor: not-allowed;
    }

    .seat-compact.female {
      background: linear-gradient(135deg, #fce7f3 0%, #f9a8d4 100%);
      border-color: #ec4899;
      color: #831843;
      cursor: not-allowed;
    }

    .seat-sidebar-compact {
      background: #f8f9fa;
      padding: 18px;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      overflow-y: auto;
    }

    .seat-sidebar-compact h3 {
      margin: 0 0 15px 0;
      font-size: 1rem;
      color: #0071e3;
    }

    .gender-selection-compact {
      margin-bottom: 15px;
    }

    .gender-selection-compact label {
      font-weight: 600;
      margin-bottom: 8px;
      display: block;
      color: #374151;
      font-size: 0.85rem;
    }

    .gender-options-compact {
      display: flex;
      gap: 8px;
    }

    .gender-option-compact {
      flex: 1;
      padding: 10px 8px;
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      cursor: pointer;
      text-align: center;
      transition: all 0.2s;
      background: white;
    }

    .gender-option-compact:hover {
      border-color: #0071e3;
    }

    .gender-option-compact input[type="radio"] {
      display: none;
    }

    .gender-option-compact input[type="radio"]:checked + label {
      color: #0071e3;
      font-weight: 700;
    }

    .gender-option-compact:has(input[type="radio"]:checked) {
      border-color: #0071e3;
      background: rgba(0, 113, 227, 0.05);
    }

    .gender-option-compact label {
      cursor: pointer;
      font-size: 0.85rem;
    }

    .selected-info-compact {
      padding: 12px;
      background: white;
      border-radius: 8px;
      margin-bottom: 15px;
      border: 1px solid #e5e7eb;
    }

    .selected-info-compact p {
      margin-bottom: 6px;
      color: #374151;
      font-size: 0.85rem;
    }

    .selected-info-compact p:last-child {
      margin-bottom: 0;
    }

    .selected-info-compact strong {
      color: #0071e3;
    }

    .btn-full {
      width: 100%;
      padding: 12px 18px;
      background: #0071e3;
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      margin-bottom: 8px;
    }

    .btn-full:hover {
      background: #0077ed;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 113, 227, 0.3);
    }

    .btn-full:disabled {
      background: #9ca3af;
      cursor: not-allowed;
      transform: none;
    }

    .btn-full.btn-secondary {
      background: #6b7280;
    }

    .btn-full.btn-secondary:hover {
      background: #4b5563;
    }

    .seat-legend-compact {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
      padding: 10px 0;
      margin-top: 10px;
    }

    .legend-item-compact {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.8rem;
      color: #374151;
    }

    .legend-box {
      width: 22px;
      height: 22px;
      border-radius: 4px;
      border: 2px solid;
      flex-shrink: 0;
    }

    .legend-box.available {
      background: #e5e7eb;
      border-color: #d1d5db;
    }

    .legend-box.selected {
      background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
      border-color: #059669;
    }

    .legend-box.male {
      background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%);
      border-color: #3b82f6;
    }

    .legend-box.female {
      background: linear-gradient(135deg, #fce7f3 0%, #f9a8d4 100%);
      border-color: #ec4899;
    }

    @media (max-width: 1024px) {
      .seat-modal-body {
        grid-template-columns: 1fr;
      }
      
      .bus-layout-compact {
        height: 360px;
      }
    }

    @media (max-width: 768px) {
      header {
        padding: 1rem;
      }

      .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }

      nav {
        flex-direction: column;
        gap: 0.5rem;
        width: 100%;
      }

      nav a {
        width: 100%;
        text-align: center;
      }

      .search-section {
        padding: 30px 20px;
      }

      form {
        flex-direction: column;
      }

      .form-group {
        width: 100%;
      }

      .trip-card {
        padding: 30px 20px;
      }

      .trip-header, .trip-details {
        flex-direction: column;
        align-items: flex-start;
      }

      .trip-route {
        width: 100%;
      }
    }
  </style>
</head>
<body>
<?php if (!empty($errorMessage)): ?>
<div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #991b1b; padding: 15px 20px; margin: 20px auto; max-width: 1200px; border-radius: 12px; text-align: center; font-weight: 600; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2); animation: slideDown 0.5s ease-out;">
  ⚠️ <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<header>
  <div class="header-content">
    <h2>🚍 Siber Otobüs</h2>
    <nav>
      <?php if (!empty($_SESSION['name'])): ?>
          <?php if ($_SESSION['role'] === 'user' && $userName): ?>
            <div class="user-profile">
              <div class="user-info">
                <div class="user-name">Merhaba, <?= htmlspecialchars($userName) ?></div>
                <div class="user-balance">💰 <?= number_format((float)$userBalance, 2) ?> ₺</div>
              </div>
              <div class="user-avatar" onclick="toggleDropdown()" id="userAvatar">
                <?= htmlspecialchars($userInitial) ?>
              </div>
              <div class="dropdown-menu" id="dropdownMenu">
                <a href="/user/profile.php" class="dropdown-item">
                  <span class="dropdown-icon">👤</span>
                  <span>Profilim</span>
                </a>
                <a href="/user/profile.php" class="dropdown-item">
                  <span class="dropdown-icon">🎫</span>
                  <span>Biletlerim</span>
                </a>
                <a href="logout.php" class="dropdown-item logout">
                  <span class="dropdown-icon">🚪</span>
                  <span>Çıkış Yap</span>
                </a>
              </div>
            </div>
          <?php else: ?>
            <span>Hoş geldin, <b><?= htmlspecialchars($_SESSION['name']) ?></b></span>
          <a href="logout.php">Çıkış</a>
          <?php endif; ?>
      <?php else: ?>
        <a href="login.php">Giriş Yap</a>
        <a href="register.php">Kayıt Ol</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main>
  <section class="search-section">
    <h3>🔍 Sefer Ara</h3>
    <form method="post">
      <div class="form-group">
        <label>Kalkış Şehri</label>
        <input type="text" name="kalkis" placeholder="İstanbul" required>
      </div>
      <div class="form-group">
        <label>Varış Şehri</label>
        <input type="text" name="varis" placeholder="Ankara" required>
      </div>
      <button type="submit">Sefer Ara</button>
    </form>
  </section>

  <section class="results-section">
    <?php if ($seferler): ?>
      <?php foreach ($seferler as $s): ?>
        <div class="trip-card">
          <div class="trip-header">
            <span class="company-name"><?=htmlspecialchars($s['firma_adi'])?></span>
          </div>
          
          <div class="trip-route">
            <span class="city"><?=htmlspecialchars($s['departure_city'])?></span>
            <span class="arrow">→</span>
            <span class="city"><?=htmlspecialchars($s['destination_city'])?></span>
          </div>

          <div class="trip-details">
            <div class="detail-item">
              <span class="detail-label">Kalkış Saati</span>
              <span class="detail-value">⏰ <?=htmlspecialchars($s['departure_time'])?></span>
            </div>
            
            <div class="detail-item">
              <span class="detail-label">Fiyat</span>
              <span class="price"><?=number_format($s['price'],2)?> ₺</span>
            </div>

            <div class="detail-item">
              <button class="details-btn" 
                      data-trip-id="<?= $s['id'] ?>"
                      data-description="<?= htmlspecialchars($s['description'] ?? 'Sefer hakkında detaylı bilgi bulunmuyor.') ?>">
                  📋 Sefer Detayları
              </button>
            </div>

            <div class="detail-item">
              <button class="ticket-btn" 
                      onclick="openSeatSelection(<?= $s['id'] ?>, <?= (int)$s['capacity'] ?>, <?= (int)$s['price'] ?>, '<?= htmlspecialchars($s['departure_city']) ?>', '<?= htmlspecialchars($s['destination_city']) ?>')">
                  🪑 Koltuk Seç & Bilet Al
              </button>
              </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-results">
        <p>😔 Sefer bulunamadı.</p>
      </div>
    <?php endif; ?>
  </section>
</main>

<!-- Sefer Detayları Modal -->
<div class="trip-details-modal" id="tripDetailsModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>📋 Sefer Detayları</h3>
      <button class="modal-close" id="closeModal">✕</button>
    </div>
    <div class="modal-body" id="modalBody">
      <!-- İçerik JavaScript ile eklenecek -->
    </div>
  </div>
</div>

<!-- Koltuk Seçimi Modal -->
<div class="seat-selection-modal" id="seatSelectionModal">
  <div class="seat-modal-content">
    <div class="seat-modal-header">
      <div>
        <h2 id="seatModalTitle">🪑 Koltuk Seçimi</h2>
        <p id="seatModalRoute" style="color:#6e6e73; margin-top:8px;"></p>
      </div>
      <button class="modal-close" id="closeSeatModal">✕</button>
    </div>
    
    <div class="seat-modal-body">
      <div class="seat-main-section">
        <h3 style="margin-bottom:20px; color:#1d1d1f;">🚌 Otobüs Yerleşimi (2+1)</h3>
        
        <div class="bus-layout-compact">
          <div class="seats-container-compact" id="seatsContainerModal">
            <!-- Koltuklar JavaScript ile render edilecek -->
                </div>
              </div>

        <div class="seat-legend-compact">
          <div class="legend-item-compact">
            <div class="legend-box available"></div>
            <span>Boş</span>
          </div>
          <div class="legend-item-compact">
            <div class="legend-box selected"></div>
            <span>Seçilen</span>
          </div>
          <div class="legend-item-compact">
            <div class="legend-box male"></div>
            <span>Erkek</span>
          </div>
          <div class="legend-item-compact">
            <div class="legend-box female"></div>
            <span>Kadın</span>
          </div>
        </div>
      </div>

      <div class="seat-sidebar-compact">
        <h3>📝 Seçim Bilgileri</h3>
        
        <form id="seatFormModal" action="bilet_al.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="trip_id" id="tripIdModal">
          <input type="hidden" name="seat_number" id="seatNumberModal" required>
          <input type="hidden" name="gender" id="genderInputModal" value="male">
          
          <div class="gender-selection-compact">
            <label>Cinsiyet:</label>
            <div class="gender-options-compact">
              <div class="gender-option-compact">
                <input type="radio" name="gender_radio_modal" value="male" id="maleModal" checked onchange="document.getElementById('genderInputModal').value='male'">
                <label for="maleModal">👨 Erkek</label>
              </div>
              <div class="gender-option-compact">
                <input type="radio" name="gender_radio_modal" value="female" id="femaleModal" onchange="document.getElementById('genderInputModal').value='female'">
                <label for="femaleModal">👩 Kadın</label>
              </div>
            </div>
          </div>

          <div class="selected-info-compact">
            <p><strong>Koltuk:</strong> <span id="selectedSeatModal">-</span></p>
            <p><strong>Fiyat:</strong> <span id="priceModal">0</span> ₺</p>
          </div>

          <button type="submit" class="btn-full" id="submitBtnModal" disabled>🎫 Devam Et</button>
          <button type="button" class="btn-full btn-secondary" onclick="closeSeatSelection()">❌ İptal</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>function toggleDropdown(){const _0xd1=document.getElementById('dropdownMenu');_0xd1.classList.toggle('active')}document.addEventListener('click',function(_0xe2){const _0xf3=document.getElementById('userAvatar'),_0xg4=document.getElementById('dropdownMenu');if(_0xf3&&!_0xf3.contains(_0xe2.target)&&_0xg4&&!_0xg4.contains(_0xe2.target)){_0xg4.classList.remove('active')}});const _0x4a2b=document.getElementById('tripDetailsModal'),_0x5c3d=document.getElementById('modalBody'),_0x6e4f=document.getElementById('closeModal');document.querySelectorAll('.details-btn').forEach(_0x7a=>_0x7a.addEventListener('click',()=>{const _0x8b=_0x7a.dataset.description||'Sefer hakkında detaylı bilgi bulunmuyor.';_0x5c3d.innerHTML='<p>'+_0x8b.replace(/\n/g,'<br>')+'</p>';_0x4a2b.classList.add('active')}));_0x6e4f.addEventListener('click',()=>_0x4a2b.classList.remove('active'));_0x4a2b.addEventListener('click',_0x9c=>{_0x9c.target===_0x4a2b&&_0x4a2b.classList.remove('active')});const _0x1d2e=document.getElementById('seatSelectionModal'),_0x3f4g=document.getElementById('closeSeatModal');let _0x5h6i=null,_0x7j8k=0;async function openSeatSelection(_0x9l,_0xa1,_0xb2,_0xc3,_0xd4){_0x5h6i=_0x9l;_0x7j8k=_0xb2;document.getElementById('seatModalRoute').textContent=_0xc3+' → '+_0xd4;document.getElementById('priceModal').textContent=_0xb2.toFixed(2);document.getElementById('tripIdModal').value=_0x9l;try{const _0xe5=await fetch('occupied_seats.php?trip_id='+encodeURIComponent(_0x9l),{cache:'no-store'}),_0xf6=await _0xe5.json(),_0xg7=Array.isArray(_0xf6.occupied)?_0xf6.occupied:[];_0xh8(_0xa1,_0xg7,_0xb2);_0x1d2e.classList.add('active');document.body.style.overflow='hidden'}catch(_0xi9){console.error('Koltuk bilgileri yüklenemedi:',_0xi9);alert('Koltuk bilgileri yüklenirken hata oluştu')}}function closeSeatSelection(){_0x1d2e.classList.remove('active');document.body.style.overflow='';document.getElementById('seatsContainerModal').innerHTML='';document.getElementById('selectedSeatModal').textContent='-';document.getElementById('seatNumberModal').value='';document.getElementById('submitBtnModal').disabled=!0}_0x3f4g.addEventListener('click',closeSeatSelection);_0x1d2e.addEventListener('click',_0xj1=>{_0xj1.target===_0x1d2e&&closeSeatSelection()});function _0xh8(_0xk2,_0xl3,_0xm4){const _0xn5=document.getElementById('seatsContainerModal');_0xn5.innerHTML='';const _0xo6=43,_0xp7=[15,58],_0xq8=180;let _0xr9=1,_0xs0=0;const _0xt1=new Map();_0xl3.forEach(_0xu2=>_0xt1.set(String(_0xu2.seat),_0xu2.gender==='female'?'female':'male'));while(_0xr9<=_0xk2){const _0xv3=_0xs0*_0xo6;for(let _0xw4=0;_0xw4<2;_0xw4++){if(_0xr9>_0xk2)break;const _0xx5=_0xp7[_0xw4],_0xy6=_0xz7(_0xr9,_0xv3,_0xx5,_0xt1);_0xn5.appendChild(_0xy6);_0xr9++}if(_0xr9<=_0xk2){const _0xa8=_0xq8,_0xb9=_0xz7(_0xr9,_0xv3,_0xa8,_0xt1);_0xn5.appendChild(_0xb9);_0xr9++}_0xs0++}}function _0xz7(_0xc1,_0xd2,_0xe3,_0xf4){const _0xg5=document.createElement('div');_0xg5.className='seat-compact';_0xg5.dataset.seat=_0xc1;_0xg5.textContent=_0xc1;_0xg5.style.left=_0xd2+'px';_0xg5.style.top=_0xe3+'px';if(_0xf4.has(String(_0xc1))){const _0xh6=_0xf4.get(String(_0xc1));_0xg5.classList.add(_0xh6==='female'?'female':'male');_0xg5.style.cursor='not-allowed'}else{_0xg5.addEventListener('click',function(){_0xi7(this)})}return _0xg5}function _0xi7(_0xj8){document.querySelectorAll('.seat-compact').forEach(_0xk9=>_0xk9.classList.remove('selected'));_0xj8.classList.add('selected');const _0xl1=_0xj8.dataset.seat;document.getElementById('seatNumberModal').value=_0xl1;document.getElementById('selectedSeatModal').textContent=_0xl1;document.getElementById('submitBtnModal').disabled=!1}document.addEventListener('keydown',_0xm2=>{_0xm2.key==='Escape'&&_0x1d2e.classList.contains('active')&&closeSeatSelection()});</script>

</body>
</html>
