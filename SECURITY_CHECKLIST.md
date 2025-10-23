# 🔒 Güvenlik Kontrol Listesi

Bu dosya, projenin production'a geçmeden önce kontrol edilmesi gereken güvenlik maddelerini içerir.

## ✅ Zorunlu Güvenlik Adımları (Production'dan Önce)

### 1. Environment Variables (CRITICAL)
- [ ] `.env` dosyası oluşturuldu (`cp env.example .env`)
- [ ] Tüm secret key'ler yeniden generate edildi
- [ ] `TICKET_SECRET_KEY` değiştirildi
- [ ] `CSRF_SECRET` değiştirildi
- [ ] `SESSION_SECRET` değiştirildi
- [ ] Admin access token'lar değiştirildi (`php admin/generate_access_token.php`)

### 2. Email Configuration (CRITICAL)
- [ ] SMTP ayarları yapılandırıldı (`includes/email.php`)
- [ ] Gmail kullanıyorsanız "App Password" oluşturuldu
- [ ] Test email gönderimi yapıldı (2FA için kritik)
- [ ] Gönderen email adresi doğrulandı

### 3. Database Security
- [ ] Database dosya izinleri ayarlandı: `chmod 600 database/bilet_sistem.db`
- [ ] Database sahibi web sunucusu kullanıcısı yapıldı: `chown www-data:www-data database/bilet_sistem.db`
- [ ] Database dizini web'den erişilemez durumda
- [ ] `.htaccess` dosyası database dizininde aktif

### 4. Admin Access Security (ULTRA CRITICAL)
- [ ] `admin/secret_access.php` içindeki `SECRET_MASTER_KEY` değiştirildi
- [ ] Admin access token'lar rotate edildi
- [ ] Admin_Access_Tokens tablosu oluşturuldu
- [ ] Test admin girişi yapıldı ve çalıştığı doğrulandı

### 5. File Permissions
```bash
# Şu komutları çalıştırın:
chmod 600 database/bilet_sistem.db
chmod 600 .env
chmod 600 logs/*.log
chmod 755 logs/
chmod 755 database/
chown -R www-data:www-data database/
chown -R www-data:www-data logs/
chown www-data:www-data .env
```
- [ ] Database dosyası 600 izinli
- [ ] .env dosyası 600 izinli
- [ ] Log dosyaları 600 izinli
- [ ] Dizinler 755 izinli
- [ ] Dosya sahiplikleri www-data:www-data

### 6. HTTPS Configuration (CRITICAL)
- [ ] SSL/TLS sertifikası yüklendi (Let's Encrypt önerilir)
- [ ] HTTPS zorunlu hale getirildi (HTTP → HTTPS redirect)
- [ ] HSTS başlığı aktif edildi (`includes/config.php`)
- [ ] Secure cookie flag'i aktif (`SESSION_COOKIE_SECURE=true`)

### 7. Error Reporting & Logging
- [ ] `display_errors` kapalı (zaten `includes/config.php`'de)
- [ ] `error_reporting` sadece log dosyasına yazıyor
- [ ] Log dosyaları web'den erişilemez
- [ ] Hassas bilgiler log dosyalarında yok

### 8. Git & Version Control
- [ ] `.gitignore` dosyası kontrol edildi
- [ ] `.env` dosyası git'e commit edilmedi
- [ ] Database dosyası git'e commit edilmedi
- [ ] Log dosyaları git'e commit edilmedi
- [ ] Hassas bilgiler (şifreler, tokenlar) kodda hard-coded değil

### 9. Backup Strategy
- [ ] Otomatik günlük backup cron job oluşturuldu
```bash
# Crontab'a ekle:
0 2 * * * sqlite3 /var/www/database/bilet_sistem.db ".backup '/var/backups/bilet_sistem_$(date +\%Y\%m\%d).db'"
```
- [ ] Backup dosyaları güvenli bir yerde saklanıyor
- [ ] Backup restore testi yapıldı

### 10. Session Security
- [ ] Session timeout yapılandırıldı (`SESSION_LIFETIME`)
- [ ] Session cookie'leri HttpOnly
- [ ] Session cookie'leri Secure (HTTPS için)
- [ ] Session cookie'leri SameSite=Strict
- [ ] Session regeneration aktif

---

## ✅ Önerilen Güvenlik Adımları

### 11. Rate Limiting
- [ ] Rate limiting test edildi
- [ ] Login denemeleri için rate limit aktif
- [ ] 2FA kodları için rate limit aktif
- [ ] API endpoint'leri için rate limit aktif

### 12. CSRF Protection
- [ ] Tüm formlarda CSRF token kontrolü var
- [ ] CSRF token'lar her session'da yenileniyor
- [ ] AJAX isteklerinde CSRF token gönderiliyor

### 13. XSS Protection
- [ ] Tüm output'lar `htmlspecialchars()` ile encode ediliyor
- [ ] User input sanitization yapılıyor
- [ ] Content-Security-Policy başlığı aktif

### 14. SQL Injection Protection
- [ ] Tüm SQL sorguları prepared statement kullanıyor
- [ ] User input direkt SQL'e dahil edilmiyor
- [ ] PDO::ATTR_EMULATE_PREPARES false (zaten `includes/db.php`'de)

### 15. Password Security
- [ ] Şifreler bcrypt ile hash'leniyor (zaten aktif)
- [ ] Minimum şifre uzunluğu 6 karakter
- [ ] Şifreler database'de plain text olarak saklanmıyor

### 16. 2FA Security
- [ ] 2FA kodları email ile gönderiliyor
- [ ] 2FA kodları 5 dakika geçerli
- [ ] 2FA kodları tek kullanımlık
- [ ] 2FA rate limiting aktif

### 17. File Upload Security (Eğer eklerseniz)
- [ ] Dosya tipi kontrolü yapılıyor
- [ ] Maksimum dosya boyutu sınırlandırılmış
- [ ] Dosyalar web directory dışında saklanıyor
- [ ] Dosya isimleri sanitize ediliyor

### 18. API Security
- [ ] API endpoint'leri authentication gerektiriyor
- [ ] API rate limiting aktif
- [ ] API response'ları JSON format
- [ ] API error mesajları generic

---

## 🔍 Güvenlik Testi Kontrol Listesi

### Manual Security Testing
- [ ] SQL Injection test edildi (prepared statements ile korunuyor)
- [ ] XSS test edildi (output encoding ile korunuyor)
- [ ] CSRF test edildi (token validation ile korunuyor)
- [ ] Session hijacking test edildi (secure cookie flags)
- [ ] Brute force test edildi (rate limiting ile korunuyor)

### Automated Security Scanning
- [ ] OWASP ZAP veya Burp Suite ile tarama yapıldı
- [ ] SQLMap ile SQL injection test edildi
- [ ] XSStrike ile XSS test edildi
- [ ] Security headers kontrol edildi (securityheaders.com)

---

## 📝 Production Deployment Checklist

### Server Configuration
- [ ] PHP 8.0+ yüklü
- [ ] SQLite3 extension aktif
- [ ] PDO extension aktif
- [ ] mbstring extension aktif
- [ ] openssl extension aktif

### Web Server Configuration
- [ ] Apache mod_rewrite aktif (`.htaccess` için)
- [ ] `.htaccess` dosyası çalışıyor
- [ ] Directory listing kapalı
- [ ] Sensitive dosyalar (`.env`, `database/`) web'den erişilemez

### Monitoring & Logging
- [ ] Error log monitoring yapılandırıldı
- [ ] 2FA log monitoring yapılandırıldı
- [ ] Suspicious activity monitoring
- [ ] Log rotation yapılandırıldı

---

## 🚨 Acil Durum Prosedürleri

### Güvenlik İhlali Durumunda
1. **Hemen tüm admin access token'ları yenile**
```bash
php admin/generate_access_token.php
```

2. **CSRF token secret'ını değiştir**
```bash
# .env dosyasında:
CSRF_SECRET=yeni-secret-key-buraya
```

3. **Tüm active session'ları sonlandır**
```bash
# Session dosyalarını temizle
rm -rf /var/lib/php/sessions/*
```

4. **Database'i güvenli bir yere backup'la**
```bash
sqlite3 database/bilet_sistem.db ".backup 'backup_emergency_$(date +%Y%m%d_%H%M%S).db'"
```

5. **Log dosyalarını incele**
```bash
tail -100 logs/error.log
tail -100 logs/2fa_codes.log
```

---

## ✅ Tamamlandı Mı?

Son kontrol:
```bash
# Tüm güvenlik kontrollerini yap:
./security_check.sh

# Veya manuel kontrol:
php -r "echo 'PHP Version: ' . phpversion() . PHP_EOL;"
ls -la database/bilet_sistem.db
ls -la .env
ls -la logs/
grep -r "your-secret-key" .
grep -r "admin123" .
```

**Tüm kutuları işaretlediyseniz, production'a hazırsınız! 🚀**

**Unutmayın: Güvenlik bir süreç, bir hedef değil. Düzenli olarak güncelleyin ve kontrol edin!**

