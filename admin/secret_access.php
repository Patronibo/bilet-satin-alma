<?php
session_start();

// GÜVENLİ: Secret Master Key PHP'de tanımlanıyor (client-side'da görünmez)
const SECRET_MASTER_KEY = 'de0422ac66fd6854c3d189e3d0f2549965428ba3997170d24b934ea65fbc871e'; // Bu değeri değiştir!

// AJAX isteği geldiğinde kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'verify_key') {
        $masterKey = $_POST['masterKey'] ?? '';
        
        if (hash_equals(SECRET_MASTER_KEY, $masterKey)) {
            // Başarılı! Session'a kaydet
            $_SESSION['secret_master_authenticated'] = true;
            $_SESSION['secret_master_time'] = time();
            
            echo json_encode(['success' => true, 'message' => 'Access Granted!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid master key. Access denied.']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'check_auth') {
        // Session kontrolü
        $isAuthenticated = isset($_SESSION['secret_master_authenticated']) && 
                          $_SESSION['secret_master_authenticated'] === true &&
                          (time() - ($_SESSION['secret_master_time'] ?? 0)) < 86400; // 24 saat
        
        echo json_encode(['authenticated' => $isAuthenticated]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access Portal</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #000000 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }
        
        /* Animasyonlu arka plan */
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(0, 113, 227, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(138, 43, 226, 0.1) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .container {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-icon {
            font-size: 80px;
            margin-bottom: 15px;
            filter: drop-shadow(0 0 20px rgba(0, 113, 227, 0.5));
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }
        
        h1 {
            color: #ffffff;
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }
        
        .subtitle {
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 40px;
        }
        
        .input-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Courier New', monospace;
        }
        
        input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(0, 113, 227, 0.5);
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.1);
        }
        
        input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        
        .btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #0071e3 0%, #005bb5 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 113, 227, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 113, 227, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-error {
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #fca5a5;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.85rem;
        }
        
        .shield-icon {
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <div class="logo-icon shield-icon">🛡️</div>
        </div>
        
        <h1>Secure Access Portal</h1>
        <p class="subtitle">Enter master key to continue</p>
        
        <div id="alert" style="display: none;"></div>
        
        <form id="accessForm" onsubmit="handleSubmit(event)">
            <div class="input-group">
                <label for="masterKey">🔑 Secret Master Key</label>
                <input 
                    type="password" 
                    id="masterKey" 
                    name="masterKey" 
                    placeholder="Enter your secret master key"
                    autocomplete="off"
                    required
                    autofocus
                >
            </div>
            
            <button type="submit" class="btn">
                🚀 Unlock Access
            </button>
        </form>
        
        <div class="footer">
            🔐 Ultra Secure Access System<br>
            Unauthorized access is strictly prohibited
        </div>
    </div>
    
    <script>
        
        
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            alert.style.display = 'block';
            
            if (type === 'success') {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 2000);
            }
        }
        
        async function handleSubmit(event) {
            event.preventDefault();
            
            const masterKey = document.getElementById('masterKey').value;
            const submitBtn = event.target.querySelector('.btn');
            
            
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Verifying...';
            
            try {
            
                const formData = new FormData();
                formData.append('action', 'verify_key');
                formData.append('masterKey', masterKey);
                
                const response = await fetch('/admin/secret_access.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('✅ ' + result.message + ' Redirecting...', 'success');
                    
                    setTimeout(() => {
                        window.location.href = '/admin/generate_access_token.php';
                    }, 1500);
                } else {
                    showAlert('❌ ' + result.message, 'error');
                    document.getElementById('masterKey').value = '';
                    document.getElementById('masterKey').focus();
                    
                    
                    submitBtn.disabled = false;
                    submitBtn.textContent = '🚀 Unlock Access';
                }
            } catch (error) {
                showAlert('❌ Connection error. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = '🚀 Unlock Access';
            }
        }
        

        window.addEventListener('load', async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'check_auth');
                
                const response = await fetch('/admin/secret_access.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.authenticated) {
                    showAlert('✅ Already authenticated! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = '/admin/generate_access_token.php';
                    }, 1000);
                }
            } catch (error) {
                
                console.error('Auth check failed:', error);
            }
        });
        
        
        console.log('%c🛡️ Secure Access Portal', 'font-size: 24px; font-weight: bold; color: #0071e3;');
        console.log('%cBu bir güvenli erişim noktasıdır.', 'font-size: 14px; color: #666;');
        console.log('%cYetkisiz erişim girişimleri kaydedilir.', 'font-size: 14px; color: #dc2626;');
        console.log('%c⚠️ Secret key artık client-side\'da görünmüyor - PHP tarafında kontrol ediliyor!', 'font-size: 12px; color: #10b981; font-weight: bold;');
    </script>
</body>
</html>

