# 🤝 Katkıda Bulunma Rehberi

Siber Otobüs projesine katkıda bulunmak istediğiniz için teşekkür ederiz! Bu rehber, katkıda bulunma sürecini kolaylaştırmak için hazırlanmıştır.

---

## 📋 İçindekiler

- [Davranış Kuralları](#davranış-kuralları)
- [Katkıda Bulunmanın Yolları](#katkıda-bulunmanın-yolları)
- [Geliştirme Ortamı Kurulumu](#geliştirme-ortamı-kurulumu)
- [Kod Standartları](#kod-standartları)
- [Pull Request Süreci](#pull-request-süreci)
- [Issue Raporlama](#issue-raporlama)
- [Güvenlik Açığı Bildirimi](#güvenlik-açığı-bildirimi)

---

## 🌟 Davranış Kuralları

### Bizim Taahhüdümüz
Açık ve misafirperver bir ortam sağlamak için, katkıda bulunanlar ve sürdürücüler olarak, projemize ve topluluğumuza katılımı herkes için taciz içermeyen bir deneyim haline getirmeyi taahhüt ediyoruz.

### Standartlarımız
✅ **Kabul edilebilir davranışlar:**
- Farklı bakış açılarına ve deneyimlere saygı göstermek
- Yapıcı eleştiriyi nazikçe kabul etmek
- Topluluk için en iyisine odaklanmak
- Diğer topluluk üyelerine empati göstermek

❌ **Kabul edilemez davranışlar:**
- Cinsel içerik veya imaj kullanımı
- Trolling, hakaret veya küçük düşürücü yorumlar
- Kamusal veya özel taciz
- Diğerlerinin kişisel bilgilerini izinsiz yayınlamak

---

## 🛠️ Katkıda Bulunmanın Yolları

### 1. Kod Katkısı
- Yeni özellikler ekleyin
- Bug fix'ler yapın
- Kod iyileştirmeleri yapın
- Test coverage'ı artırın

### 2. Dokümantasyon
- README güncellemeleri
- Kod yorumları ekleyin
- Kullanım örnekleri yazın
- Wiki sayfaları oluşturun

### 3. Design & UI/UX
- UI iyileştirmeleri
- CSS optimizasyonları
- Responsive design düzeltmeleri
- Accessibility iyileştirmeleri

### 4. Testing
- Manuel test yapın
- Bug raporları oluşturun
- Edge case'leri test edin
- Security testing

### 5. Topluluk Desteği
- Issue'lara cevap verin
- Yeni kullanıcılara yardım edin
- Discussion'lara katılın

---

## ⚙️ Geliştirme Ortamı Kurulumu

### 1. Fork ve Clone
```bash
# Projeyi fork edin (GitHub'da "Fork" butonuna tıklayın)
# Sonra kendi fork'unuzu clone edin:
git clone https://github.com/Patronibo/bilet-satin-alma.git
cd siber-otobus
```

### 2. Upstream Remote Ekleyin
```bash
git remote add upstream https://github.com/original-repo/bilet-satin-alma.git
git fetch upstream
```

### 3. Geliştirme Ortamı
```bash
# Docker ile (önerilen):
docker-compose up -d

# Veya manuel:
php -S localhost:8080 -t .
```

### 4. Veritabanını Başlatın
```bash
php init_db.php
```

### 5. Environment Variables
```bash
cp env.example .env
# .env dosyasını düzenleyin
```

---

## 📝 Kod Standartları

### PHP Standards (PSR-12)

#### 1. Dosya Yapısı
```php
<?php
// Dosya başlığı (opsiyonel)
// filename.php - Kısa açıklama

// Güvenlik kontrolü
session_start();
require_once __DIR__ . '/includes/security.php';

// Ana kod
```

#### 2. İsimlendirme Kuralları
```php
// Class names: PascalCase
class UserController {}

// Function names: camelCase
function getUserById($id) {}

// Variable names: camelCase
$userId = 123;

// Constants: UPPER_SNAKE_CASE
define('MAX_LOGIN_ATTEMPTS', 5);

// Database tables: PascalCase with underscore
// User, Bus_Company, Booked_Seats
```

#### 3. Security-First Approach
```php
// ✅ İyi: Prepared statements kullan
$stmt = $db->prepare("SELECT * FROM User WHERE id = ?");
$stmt->execute([$userId]);

// ❌ Kötü: Direkt string concatenation
$query = "SELECT * FROM User WHERE id = $userId"; // SQL INJECTION!

// ✅ İyi: Output encoding
echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');

// ❌ Kötü: Direkt output
echo $userName; // XSS!

// ✅ İyi: CSRF token kontrolü
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Invalid request');
}
```

#### 4. Error Handling
```php
// ✅ İyi: Try-catch kullan
try {
    $db->beginTransaction();
    // işlemler...
    $db->commit();
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Error: ' . $e->getMessage());
    // Kullanıcıya generic error mesajı göster
    die('An error occurred. Please try again.');
}

// ❌ Kötü: Hata detayını kullanıcıya gösterme
catch (Exception $e) {
    die($e->getMessage()); // Hassas bilgi sızıntısı!
}
```

### CSS Standards

#### 1. Naming Convention (BEM-like)
```css
/* Block */
.user-profile { }

/* Element */
.user-profile__avatar { }

/* Modifier */
.user-profile--active { }
```

#### 2. Modern CSS Practices
```css
/* ✅ İyi: Modern CSS */
.container {
    display: flex;
    gap: 1rem;
    padding: clamp(1rem, 5vw, 3rem);
}

/* ✅ İyi: CSS Variables */
:root {
    --primary-color: #0071e3;
    --border-radius: 12px;
}
```

### JavaScript Standards

#### 1. Modern JavaScript (ES6+)
```javascript
// ✅ İyi: const/let kullan
const API_URL = '/api/users';
let userId = 123;

// ❌ Kötü: var kullanma
var userId = 123;

// ✅ İyi: Arrow functions
const getUser = (id) => fetch(`/api/user/${id}`);

// ✅ İyi: Async/await
async function loadData() {
    try {
        const response = await fetch(API_URL);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error:', error);
    }
}
```

#### 2. AJAX Best Practices
```javascript
// ✅ İyi: CSRF token gönder
async function makeRequest(url, data) {
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    Object.keys(data).forEach(key => formData.append(key, data[key]));
    
    const response = await fetch(url, {
        method: 'POST',
        body: formData
    });
    
    return response.json();
}
```

---

## 🔄 Pull Request Süreci

### 1. Branch Oluşturun
```bash
# Feature branch
git checkout -b feature/kullanici-profil-guncelleme

# Bug fix branch
git checkout -b fix/koltuk-secimi-hatasi

# Hotfix branch
git checkout -b hotfix/guvenlik-acigi
```

### 2. Branch İsimlendirme
- **feature/**: Yeni özellikler için
- **fix/**: Bug fix'ler için
- **hotfix/**: Acil güvenlik düzeltmeleri için
- **refactor/**: Kod iyileştirmeleri için
- **docs/**: Dokümantasyon güncellemeleri için
- **style/**: CSS/UI değişiklikleri için

### 3. Commit Mesajları
```bash
# Format:
# <type>: <kısa açıklama>
#
# <detaylı açıklama (opsiyonel)>
#
# <footer (opsiyonel)>

# Örnekler:
git commit -m "feat: Kullanıcı profil güncelleme özelliği eklendi"
git commit -m "fix: Koltuk seçiminde çift tıklama hatası düzeltildi"
git commit -m "security: XSS açığı kapatıldı"
git commit -m "docs: README kurulum bölümü güncellendi"
```

#### Commit Types:
- **feat**: Yeni özellik
- **fix**: Bug fix
- **security**: Güvenlik düzeltmesi
- **refactor**: Kod iyileştirmesi
- **style**: CSS/UI değişikliği
- **docs**: Dokümantasyon
- **test**: Test ekleme/düzeltme
- **chore**: Build process, dependencies

### 4. Kod Test Etme
```bash
# Manuel testler:
# - Tüm formların çalıştığını kontrol edin
# - Browser console'da error olmadığını kontrol edin
# - Responsive design'ı test edin (mobile, tablet, desktop)
# - Farklı tarayıcılarda test edin (Chrome, Firefox, Safari)

# Security checklist:
# - CSRF token var mı?
# - Input validation yapılıyor mu?
# - Output encoding yapılıyor mu?
# - SQL injection koruması var mı?
```

### 5. Push ve Pull Request
```bash
# Upstream'den son değişiklikleri çekin
git fetch upstream
git rebase upstream/main

# Branch'inizi push edin
git push origin feature/kullanici-profil-guncelleme

# GitHub'da Pull Request oluşturun
```

### 6. Pull Request Template
```markdown
## 📝 Değişiklik Açıklaması
Kısa bir açıklama yazın.

## 🎯 İlgili Issue
Closes #123

## 🔧 Yapılan Değişiklikler
- [ ] Yeni özellik X eklendi
- [ ] Bug Y düzeltildi
- [ ] Dokümantasyon güncellendi

## 🧪 Test Edilen Durumlar
- [ ] Chrome'da test edildi
- [ ] Firefox'ta test edildi
- [ ] Mobile'da test edildi
- [ ] CSRF koruması test edildi
- [ ] Input validation test edildi

## 📸 Ekran Görüntüleri (varsa)
![screenshot](url)

## ⚠️ Breaking Changes
Varsa açıklayın.

## 📚 Ek Notlar
Ek bilgiler varsa yazın.
```

---

## 🐛 Issue Raporlama

### Bug Report Template
```markdown
## 🐛 Bug Açıklaması
Hatanın kısa açıklaması.

## 📍 Adımlar
1. '...' sayfasına git
2. '...' butonuna tıkla
3. '...' inputuna '...' yaz
4. Hatayı gör

## ✅ Beklenen Davranış
Ne olmasını bekliyordunuz?

## ❌ Gerçek Davranış
Ne oldu?

## 📸 Ekran Görüntüsü
![screenshot](url)

## 🌍 Ortam
- **OS:** Windows 10
- **Browser:** Chrome 120
- **PHP Version:** 8.1.0
- **Tarih:** 2025-10-23

## 📋 Ek Bilgi
Console error'ları, log kayıtları, vb.
```

### Feature Request Template
```markdown
## 💡 Özellik Açıklaması
Özelliğin kısa açıklaması.

## 🎯 Problem
Hangi problemi çözüyor?

## 💭 Önerilen Çözüm
Nasıl çalışmalı?

## 🔄 Alternatifler
Başka çözüm yolları var mı?

## 📊 Öncelik
- [ ] Kritik
- [ ] Yüksek
- [ ] Orta
- [ ] Düşük

## 🤝 Katkıda Bulunmak İster Misiniz?
- [ ] Evet, bu özelliği implement edebilirim
- [ ] Hayır, sadece öneri olarak bırakıyorum
```

---

## 🔒 Güvenlik Açığı Bildirimi

### ⚠️ ÖNEMLİ: Public issue AÇMAYIN!

Güvenlik açığı tespit ederseniz:

1. **Email Gönderin:** security@siber-otobus.com
2. **Başlık:** `[SECURITY] Kısa açıklama`
3. **İçerik:**
   ```markdown
   ## Güvenlik Açığı Detayları
   
   ### Açık Türü
   (XSS, SQL Injection, CSRF, vb.)
   
   ### Etkilenen Dosya/URL
   /path/to/file.php
   
   ### Tekrarlama Adımları
   1. ...
   2. ...
   3. ...
   
   ### Etki
   Hangi veriler risk altında? Saldırgan ne yapabilir?
   
   ### Önerilen Çözüm
   Nasıl düzeltilmeli?
   
   ### PoC (Proof of Concept)
   Code snippet veya screenshot
   ```

### Responsible Disclosure
- 🕐 **24 saat içinde** ilk yanıtı alırsınız
- 🔧 **7 gün içinde** düzeltme planı paylaşılır
- 🏆 **Hall of Fame'de** isminiz yer alır
- 💰 **Bug Bounty** (gelecekte planlanıyor)

---

## ✅ Checklist (PR göndermeden önce)

### Kod Kalitesi
- [ ] Kod PSR-12 standartlarına uygun
- [ ] Değişken isimleri anlamlı ve tutarlı
- [ ] Fonksiyonlar tek sorumluluk prensibi ile yazılmış
- [ ] Kod tekrarı yok (DRY prensibi)
- [ ] Yorum satırları gerekli yerlerde eklenmiş

### Güvenlik
- [ ] CSRF koruması var
- [ ] XSS koruması var (output encoding)
- [ ] SQL Injection koruması var (prepared statements)
- [ ] Input validation yapılıyor
- [ ] Authentication kontrolleri var
- [ ] Authorization kontrolleri var
- [ ] Hassas bilgiler log'a yazılmıyor
- [ ] Error mesajları generic

### Test
- [ ] Kod manuel olarak test edildi
- [ ] Tüm formlar çalışıyor
- [ ] Browser console'da error yok
- [ ] Responsive design test edildi
- [ ] Farklı tarayıcılarda test edildi
- [ ] Edge case'ler test edildi

### Dokümantasyon
- [ ] README gerekiyorsa güncellendi
- [ ] CHANGELOG.md güncellendi
- [ ] Kod yorumları eklendi
- [ ] API değişiklikleri dokümante edildi

### Git
- [ ] Commit mesajları anlamlı
- [ ] Branch ismi standartlara uygun
- [ ] Upstream'den son değişiklikler merge edildi
- [ ] Conflict'ler çözüldü

---

## 🎓 Öğrenme Kaynakları

### PHP Security
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP The Right Way](https://phptherightway.com/)
- [PSR-12 Coding Style](https://www.php-fig.org/psr/psr-12/)

### Modern CSS
- [CSS Tricks](https://css-tricks.com/)
- [MDN Web Docs](https://developer.mozilla.org/en-US/docs/Web/CSS)
- [Can I Use](https://caniuse.com/)

### JavaScript
- [JavaScript.info](https://javascript.info/)
- [MDN JavaScript Guide](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide)
- [Eloquent JavaScript](https://eloquentjavascript.net/)

### Git
- [Git Book](https://git-scm.com/book/en/v2)
- [Oh Shit, Git!](https://ohshitgit.com/)
- [Learn Git Branching](https://learngitbranching.js.org/)

---

## 📞 İletişim

- **GitHub Issues:** [github.com/Patronibo/bilet-satin-alma/issues](https://github.com/Patronibo/bilet-satin-alma/issues)
- **Discussions:** [github.com/Patronibo/bilet-satin-alma/discussions](https://github.com/Patronibo/bilet-satin-alma/discussions)
- **Email:** ibrahimaltindag.321@gmail.com

---

## 🙏 Teşekkürler!

Projeye katkıda bulunmayı düşündüğünüz için teşekkür ederiz! Her türlü katkı değerlidir. 🎉

**Happy Coding! 🚀**

