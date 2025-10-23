# 📝 Changelog

Bu dosya projenin tüm önemli değişikliklerini içerir.

Format [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) standardına uygundur ve
bu proje [Semantic Versioning](https://semver.org/spec/v2.0.0.html) kullanır.

---

## [1.0.0] - 2025-10-23

### 🎉 İlk Stabil Sürüm

#### ✨ Added (Eklenenler)

**Kullanıcı Özellikleri:**
- Güvenli kullanıcı kayıt ve giriş sistemi (bcrypt password hashing)
- 💰 Sanal bakiye sistemi (800 TL başlangıç bakiyesi)
- 💺 İnteraktif 2+1 otobüs koltuk seçimi
- 👥 Cinsiyet bazlı koltuk renklendirmesi (erkek/kadın)
- 💳 Çift ödeme yöntemi (sanal bakiye + kredi kartı)
- 📄 PDF bilet indirme (FPDF kütüphanesi)
- 🎫 Bilet iptal sistemi (1 saat içinde tam iade, sonrası %80 iade)
- 🎟️ İndirim kupon sistemi
- 👤 Kullanıcı profil sayfası ve bilet yönetimi

**Firma Admin Paneli:**
- 🚌 Firma dashboard ve sefer yönetimi
- ➕ Yeni sefer ekleme formu
- 📊 Yolcu listesi ve koltuk haritası görüntüleme
- ❌ Firma tarafından bilet iptali
- 🎟️ Kupon oluşturma ve yönetimi
- 🔐 2FA email doğrulama ile güvenli giriş

**Sistem Admin Paneli:**
- 🏢 Otobüs firması ekleme ve düzenleme
- 🔑 Rotating token sistemi (ultra güvenli admin erişimi)
- 🛡️ Secret master key ile çift faktörlü erişim
- 📝 Firma bilgileri güncelleme

**Güvenlik Katmanları:**
- ✅ CSRF koruması (tüm formlarda token validation)
- ✅ XSS koruması (HTML encoding ve sanitization)
- ✅ SQL Injection koruması (PDO prepared statements)
- ✅ Session güvenliği (HttpOnly, Secure, SameSite cookies)
- ✅ Rate limiting (brute force saldırılarına karşı)
- ✅ 2FA email doğrulama
- ✅ Password hashing (bcrypt algoritması)
- ✅ Input validation (server-side)
- ✅ JavaScript obfuscation (kod gizleme)
- ✅ Security headers (X-Frame-Options, CSP, vb.)

**API Endpoints:**
- `GET /src/occupied_seats.php` - Dolu koltukları getir
- `GET /firma_admin/trip_data.php` - Sefer detaylarını getir
- `POST /src/odeme/check_coupon.php` - Kupon kontrolü
- `POST /user/cancel_ticket.php` - Bilet iptali

**Database:**
- SQLite3 veritabanı
- 8 tablo (User, Bus_Company, Trips, Tickets, Booked_Seats, Coupons, User_Coupons, Admin_Access_Tokens)
- Foreign key ilişkileri
- Transaction management

**UI/UX:**
- 🎨 Modern, Apple-style design
- 📱 Responsive design (mobil uyumlu)
- ✨ CSS animations ve gradients
- 🎭 Modal-based seat selection
- 🌈 Gradient backgrounds ve blur effects
- 🎬 Smooth transitions

**DevOps:**
- 🐳 Docker support (Dockerfile + docker-compose.yml)
- 🍎 Apache configuration (.htaccess)
- 📦 Kurulum scripti (init_db.php)

**Dokümantasyon:**
- 📖 Kapsamlı README.md
- 🔒 SECURITY_CHECKLIST.md
- 🔐 ULTRA_SECURE_SYSTEM.md
- 🔄 ROTATING_TOKEN_SYSTEM.md
- 🛡️ SECURITY.md
- 👨‍💼 ADMIN_ACCESS_GUIDE.md
- 📝 CHANGELOG.md
- 🤝 CONTRIBUTING.md
- ⚖️ LICENSE (MIT)
- 🌍 env.example (environment variables template)
- 📝 .gitignore (hassas dosyalar için)

#### 🔧 Changed (Değişenler)
- N/A (İlk sürüm)

#### 🗑️ Deprecated (Kullanımdan kaldırılanlar)
- N/A (İlk sürüm)

#### ❌ Removed (Kaldırılanlar)
- `user/download_ticket_secure.php` - Signed URL sistemi kullanılmıyor
- `user/generate_ticket_link.php` - AJAX token generator kullanılmıyor
- `src/purchase_ticket.php` - Eski purchase sistemi yerine yeni ödeme akışı
- `database/update_balance.sql` - Geçici SQL dosyası
- `logs/2fa_codes.log` - Hassas loglar temizlendi

#### 🐛 Fixed (Düzeltilenler)
- N/A (İlk sürüm)

#### 🔒 Security (Güvenlik)
- Tüm güvenlik katmanları ilk sürümden itibaren aktif
- OWASP Top 10 güvenlik açıklarına karşı korunma
- Penetration test hazır altyapı

---


## 📊 Version History

| Version | Release Date | Status | Notes |
|---------|-------------|--------|-------|
| 1.0.0   | 2025-10-23  | ✅ Stable | İlk stabil sürüm, production ready |

---

## 🔗 Linkler

- [GitHub Repository](https://github.com/Patronibo/siber-otobus)
- [Documentation](https://github.com/Patronibo/siber-otobus/wiki)
- [Issues](https://github.com/Patronibo/siber-otobus/issues)
- [Pull Requests](https://github.com/Patronibo/siber-otobus/pulls)

---

## 📝 Notlar

### Semantic Versioning
Bu proje [Semantic Versioning](https://semver.org/) kullanır:
- **MAJOR** version: Backward incompatible değişiklikler
- **MINOR** version: Backward compatible yeni özellikler
- **PATCH** version: Backward compatible bug fix'ler

### Changelog Kategorileri
- **Added**: Yeni özellikler
- **Changed**: Mevcut özelliklerde değişiklikler
- **Deprecated**: Yakında kaldırılacak özellikler
- **Removed**: Kaldırılan özellikler
- **Fixed**: Bug fix'ler
- **Security**: Güvenlik güncellemeleri

---

**Son Güncelleme:** 2025-10-23  
**Maintainer:** Patronibo

