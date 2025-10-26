#  Siber Otobüs - Online Otobüs Bileti Sistemi

Modern, güvenli ve kullanıcı dostu bir otobüs bileti satış ve yönetim sistemi.

![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)
![SQLite](https://img.shields.io/badge/SQLite-3-green)
![License](https://img.shields.io/badge/license-MIT-blue)
![Security](https://img.shields.io/badge/security-high-brightgreen)

##  İçindekiler

- [Özellikler](#-özellikler)
- [Güvenlik](#-güvenlik)
- [Kurulum](#-kurulum)
- [Kullanım](#-kullanım)
- [Sistem Gereksinimleri](#-sistem-gereksinimleri)
- [Veritabanı Yapısı](#-veritabanı-yapısı)
- [API Dokümantasyonu](#-api-dokümantasyonu)
- [Güvenlik Notları](#-güvenlik-notları)
- [Katkıda Bulunma](#-katkıda-bulunma)
- [Lisans](#-lisans)

---

##  Özellikler

### Kullanıcı Özellikleri
-  **Güvenli Giriş/Kayıt Sistemi** - 2FA (Two-Factor Authentication) desteği ile
-  **Çift Ödeme Yöntemi** - Sanal bakiye veya kredi kartı ile ödeme
-  **Bilet Yönetimi** - Biletlerinizi görüntüleyin, indirin, iptal edin
-  **İnteraktif Koltuk Seçimi** - 2+1 otobüs yerleşimi ile koltuk seçimi
-  **Cinsiyet Bazlı Koltuk Gösterimi** - Erkek/kadın yolcular farklı renklerde
-  **PDF Bilet İndirme** - Profesyonel tasarımlı PDF biletler
-  **Sanal Bakiye Sistemi** - Kullanıcılar 800 TL başlangıç bakiyesi ile başlar
-  **Kupon Sistemi** - İndirim kuponları uygulama

###  Firma Admin Özellikleri
-  **Firma Paneli** - Kendi seferlerinizi yönetin
-  **Sefer Ekleme** - Yeni seferler oluşturun
-  **Yolcu Görüntüleme** - Her sefer için yolcu listesi ve koltuk haritası
-  **Bilet İptali** - Firma tarafından bilet iptali
-  **Kupon Yönetimi** - İndirim kuponları oluşturun ve yönetin

###  Sistem Admin Özellikleri
-  **Firma Yönetimi** - Otobüs firmalarını ekleyin, düzenleyin
-  **Rotating Token Sistemi** - Ultra güvenli admin girişi
-  **2FA Email Doğrulama** - Her admin girişinde email ile kod
-  **Firma Düzenleme** - Firma bilgilerini güncelleyin

---

##  Güvenlik

Bu proje endüstri standartlarında güvenlik önlemleri ile geliştirilmiştir:

### Güvenlik Katmanları
1.  **CSRF Koruması** - Tüm formlarda token doğrulama
2.  **XSS Koruması** - HTML encode, sanitization
3.  **SQL Injection Koruması** - PDO prepared statements
4.  **Session Güvenliği** - Secure, HttpOnly, SameSite cookies
5.  **Rate Limiting** - Brute force saldırılarına karşı
6.  **Password Hashing** - bcrypt algoritması ile
7.  **Email 2FA** - Two-Factor Authentication
8.  **Input Validation** - Server-side validasyon
9.  **JavaScript Obfuscation** - Kod gizleme ve koruma
10.  **Security Headers** - X-Frame-Options, CSP, etc.

### Güvenlik Başlıkları
```php
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'
Referrer-Policy: strict-origin-when-cross-origin
```

---

##  Kurulum

### Docker ile Kurulum (Önerilen)

1. **Projeyi klonlayın:**
```bash
git clone https://github.com/Patronıbo/bilet-satin-alma.git
cd siber-otobus
```

2. **Docker container'ı başlatın:**
```bash
docker-compose up -d
```

3. **Veritabanını başlatın:**
```bash
docker exec -it siber-otobus-web php /var/www/html/init_db.php
```

4. **Uygulamaya erişin:**
```
http://localhost:8080
```

### Manuel Kurulum

1. **Gereksinimleri kontrol edin:**
   - PHP 8.0 veya üzeri
   - SQLite3 extension
   - PDO extension
   - mbstring extension

2. **Projeyi klonlayın:**
```bash
git clone https://github.com/Patronibo/bilet-satin-alma.git
cd siber-otobus
```

3. **Veritabanını oluşturun:**
```bash
php init_db.php
```

4. **Web sunucunuzu yapılandırın:**
   - Document root: `/path/to/siber-otobus`
   - Apache için `.htaccess` dosyası hazır

5. **Dizin izinlerini ayarlayın:**
```bash
chmod 755 database/
chmod 666 database/bilet_sistem.db
chmod 755 logs/
chmod 666 logs/*.log
```

---

##  Kullanım

### Varsayılan Giriş Bilgileri

#### Sistem Admin
- **Email:** `admin@bilet.com`
- **Şifre:** `admin123`
- **Erişim:** `/admin/secret_access.php` (Rotating token gerekli)

#### Test Kullanıcısı
Kayıt sayfasından yeni kullanıcı oluşturabilirsiniz: `/src/register.php`

### Ana Özellikler

#### 1. Bilet Satın Alma
1. Ana sayfada (`/src/index.php`) kalkış ve varış noktası seçin
2. Uygun seferi bulun ve "Koltuk Seç" butonuna tıklayın
3. Koltuk haritasından koltuk seçin ve cinsiyet belirtin
4. Ödeme yöntemi seçin (Sanal bakiye veya Kredi kartı)
5. Ödemeyi tamamlayın

#### 2. Bilet Yönetimi
- **Profilim:** `/user/profile.php` - Tüm biletlerinizi görüntüleyin
- **PDF İndir:** Her bilet için "PDF İndir" butonu
- **İptal Et:** Aktif biletleri iptal edebilirsiniz

#### 3. Firma Admin
1. Firma admin girişi: `/firma_admin/firma_admin_login.php`
2. Panelde seferlerinizi ve yolcularınızı görün
3. Yeni sefer ekleyin: "Sefer Ekle" menüsü
4. Kupon oluşturun: "Kupon Yönetimi" menüsü

---

##  Sistem Gereksinimleri

### Minimum Gereksinimler
- **PHP:** 8.0 veya üzeri
- **SQLite:** 3.x
- **Web Sunucu:** Apache 2.4+ veya Nginx 1.18+
- **RAM:** 512 MB
- **Disk:** 100 MB boş alan

### Önerilen Gereksinimler
- **PHP:** 8.1 veya üzeri
- **RAM:** 1 GB
- **Disk:** 500 MB boş alan

### PHP Extensions
```
- pdo_sqlite
- mbstring
- session
- json
- openssl
- filter
```

---

##  Veritabanı Yapısı

### Tablolar

#### User
Kullanıcı bilgileri ve roller
```sql
- id (TEXT, PRIMARY KEY)
- full_name (TEXT)
- email (TEXT)
- password (TEXT, bcrypt hashed)
- role (TEXT: user, company, admin)
- balance (INTEGER, default: 800)
- company_id (TEXT, FOREIGN KEY)
```

#### Bus_Company
Otobüs firmaları
```sql
- id (TEXT, PRIMARY KEY)
- name (TEXT, UNIQUE)
- logo_path (TEXT)
```

#### Trips
Seferler
```sql
- id (INTEGER, PRIMARY KEY)
- company_id (TEXT, FOREIGN KEY)
- departure_city (TEXT)
- destination_city (TEXT)
- departure_time (TEXT)
- arrival_time (TEXT)
- price (INTEGER)
- capacity (INTEGER)
- bus_type (TEXT, default: '2+2')
```

#### Tickets
Biletler
```sql
- id (TEXT, PRIMARY KEY)
- trip_id (INTEGER, FOREIGN KEY)
- user_id (TEXT, FOREIGN KEY)
- status (TEXT: active, canceled, expired)
- total_price (INTEGER)
```

#### Booked_Seats
Rezerve koltuklar
```sql
- id (TEXT, PRIMARY KEY)
- ticket_id (TEXT, FOREIGN KEY)
- seat_number (INTEGER)
- gender (TEXT: male, female)
```

#### Coupons
İndirim kuponları
```sql
- id (TEXT, PRIMARY KEY)
- code (TEXT, UNIQUE)
- discount (REAL)
- usage_limit (INTEGER)
- expire_date (TEXT)
```

---

##  API Dokümantasyonu

### Occupied Seats API
**Endpoint:** `/src/occupied_seats.php`  
**Method:** `GET`  
**Parameters:** `trip_id` (required)  
**Response:**
```json
{
  "success": true,
  "occupied": [
    {"seat": 1, "gender": "male"},
    {"seat": 5, "gender": "female"}
  ]
}
```

### Trip Data API
**Endpoint:** `/firma_admin/trip_data.php`  
**Method:** `GET`  
**Parameters:** `trip_id` (required)  
**Auth:** Firma admin session required  
**Response:**
```json
{
  "success": true,
  "trip": {...},
  "passengers": [...],
  "occupied": [...]
}
```

### Coupon Check API
**Endpoint:** `/src/odeme/check_coupon.php`  
**Method:** `POST`  
**Parameters:** `coupon_code` (required), `csrf_token` (required)  
**Response:**
```json
{
  "success": true,
  "discount": 10.5,
  "message": "Kupon geçerli!"
}
```

---

## 🛡️ Güvenlik Notları

### Production'a Geçiş İçin Önemli Adımlar

1. **Environment Variables (.env dosyası oluşturun):**
```bash
# Database
DB_PATH=/var/www/database/bilet_sistem.db

# Security
SECRET_KEY=your-secret-key-change-this-in-production-2024
CSRF_SECRET=another-random-secret-key

# Admin Access
ADMIN_ACCESS_TOKEN=your-rotating-token-here

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM=noreply@example.com

# Session
SESSION_LIFETIME=3600
SESSION_NAME=SIBER_OTOBUS_SESSION

# Security Headers
ENABLE_HSTS=true
CSP_POLICY=default-src 'self'
```

2. **Dosya İzinleri:**
```bash
# Database (sadece web sunucusu yazabilir)
chmod 600 database/bilet_sistem.db
chown www-data:www-data database/bilet_sistem.db

# Logs (sadece web sunucusu yazabilir)
chmod 600 logs/*.log
chown www-data:www-data logs/

# Config dosyaları (sadece okunabilir)
chmod 400 includes/config.php
chmod 400 includes/email.php
```

3. **Admin Access Token'ı Değiştirin:**
   - `admin/secret_access.php` dosyasındaki `VALID_TOKENS` dizisini güvenli tokenlarla doldurun
   - Token oluşturmak için: `php admin/generate_access_token.php`

4. **SMTP Ayarlarını Yapın:**
   - `includes/email.php` dosyasındaki email ayarlarını kendi SMTP sunucunuzla güncelleyin
   - Gmail kullanıyorsanız "App Password" oluşturun

5. **HTTPS Kullanın:**
   - SSL/TLS sertifikası yükleyin (Let's Encrypt önerilir)
   - `includes/config.php` içinde HSTS başlığını aktif edin

6. **Error Reporting Kapatın:**
   - `includes/config.php` içinde `display_errors` zaten kapalı
   - Production'da sadece log dosyasına yazılır

7. **Database Backup:**
```bash
# Günlük otomatik backup (cron job)
0 2 * * * sqlite3 /var/www/database/bilet_sistem.db ".backup '/var/backups/bilet_sistem_$(date +\%Y\%m\%d).db'"
```

### Güvenlik Kontrol Listesi

- [ ] `.env` dosyası oluşturuldu ve hassas bilgiler aktarıldı
- [ ] `.gitignore` dosyası kontrol edildi
- [ ] Admin access token'lar değiştirildi
- [ ] SMTP ayarları yapılandırıldı
- [ ] Database dosya izinleri ayarlandı (600)
- [ ] HTTPS aktif ve HSTS başlığı eklendi
- [ ] Error reporting kapatıldı
- [ ] Session timeout yapılandırıldı
- [ ] Rate limiting test edildi
- [ ] CSRF token'lar tüm formlarda var
- [ ] SQL injection test edildi (prepared statements)
- [ ] XSS test edildi (output encoding)
- [ ] Backup stratejisi oluşturuldu

---

##  Proje Yapısı

```
siber-otobus/
├── admin/                      # Sistem admin paneli
│   ├── admin_login.php        # Admin girişi
│   ├── panel.php              # Admin dashboard
│   ├── firma_admin.php        # Firma yönetimi
│   ├── secret_access.php      # Rotating token girişi
│   └── generate_access_token.php
├── firma_admin/               # Firma admin paneli
│   ├── firma_admin_login.php # Firma girişi
│   ├── panel.php              # Firma dashboard
│   ├── sefer_ekle.php         # Sefer ekleme
│   ├── kupon_yonetimi.php     # Kupon yönetimi
│   └── trip_data.php          # API endpoint
├── src/                       # Kullanıcı sayfaları
│   ├── index.php              # Ana sayfa
│   ├── login.php              # Kullanıcı girişi
│   ├── register.php           # Kullanıcı kaydı
│   ├── bilet_al.php           # Ödeme yöntemi seçimi
│   ├── occupied_seats.php     # Dolu koltuklar API
│   └── odeme/                 # Ödeme işlemleri
│       ├── index.php          # Kredi kartı ödeme
│       ├── pay_with_balance.php # Bakiye ile ödeme
│       ├── process.php        # Ödeme işleme
│       ├── success.php        # Başarı sayfası
│       └── check_coupon.php   # Kupon kontrolü
├── user/                      # Kullanıcı işlemleri
│   ├── profile.php            # Profil ve biletler
│   ├── download_ticket.php    # PDF bilet indirme
│   ├── cancel_ticket.php      # Bilet iptali
│   └── fpdf.php               # PDF kütüphanesi
├── includes/                  # Ortak dosyalar
│   ├── db.php                 # Database bağlantısı
│   ├── config.php             # Genel ayarlar
│   ├── security.php           # Güvenlik fonksiyonları
│   └── email.php              # Email gönderimi
├── database/                  # Veritabanı
│   ├── bilet_sistem.db        # SQLite database
│   └── schema.sql             # Database şeması
├── logs/                      # Log dosyaları
│   └── 2fa_codes.log          # 2FA kodları
├── docker-compose.yml         # Docker yapılandırması
├── Dockerfile                 # Docker image
├── .htaccess                  # Apache yapılandırması
├── .gitignore                 # Git ignore
├── README.md                  # Bu dosya
└── init_db.php                # Database başlatma
```

---

##  Teknolojiler

### Backend
- **PHP 8.0+** - Server-side programming
- **SQLite 3** - Lightweight database
- **PDO** - Database abstraction layer
- **bcrypt** - Password hashing
- **FPDF** - PDF generation

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Modern styling, animations, gradients
- **JavaScript (Obfuscated)** - Dynamic interactions
- **Responsive Design** - Mobile-first approach

### Security
- **CSRF Tokens** - Cross-Site Request Forgery protection
- **XSS Protection** - HTML encoding and sanitization
- **SQL Injection Protection** - Prepared statements
- **Session Security** - HttpOnly, Secure, SameSite
- **2FA** - Two-Factor Authentication

### DevOps
- **Docker** - Containerization
- **Apache** - Web server
- **Git** - Version control

---

##  Katkıda Bulunma

Katkılarınızı bekliyoruz! Lütfen şu adımları takip edin:

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/AmazingFeature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add some AmazingFeature'`)
4. Branch'inizi push edin (`git push origin feature/AmazingFeature`)
5. Pull Request açın

### Kod Standartları
- PSR-12 coding standards
- Security-first approach
- Comprehensive comments
- Input validation
- Error handling

---

##  Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

---

##  İletişim

Proje Linki: [https://github.com/Patronibo/bilet-satin-alma](https://github.com/Patronibo/bilet-satin-alma)

---

## Teşekkürler

- [FPDF](http://www.fpdf.org/) - PDF generation library
- [Font Awesome](https://fontawesome.com/) - Icons
- [Google Fonts](https://fonts.google.com/) - Typography

---

##  Sistem Durumu

-  **Güvenlik:** Production-ready
-  **Stability:** Stable
-  **Performance:** Optimized
-  **Documentation:** Complete
-  **Testing:** Manual tested

---

##  Versiyon Geçmişi

### v1.0.0 (2025-10-23)
-  İlk stabil sürüm
-  Kullanıcı kayıt/giriş sistemi
-  2FA email doğrulama
-  Çift ödeme yöntemi (bakiye + kredi kartı)
-  İnteraktif koltuk seçimi
-  PDF bilet indirme
-  Firma admin paneli
-  Sistem admin paneli
-  Kupon sistemi
-  Güvenlik katmanları (CSRF, XSS, SQL Injection)
-  JavaScript obfuscation
-  Responsive design
-  Docker support

---

** İyi Yolculuklar!**

