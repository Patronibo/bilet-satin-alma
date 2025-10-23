# Admin Paneli Erişim Kılavuzu 🔐

Admin paneline erişim **3 KATMANLI ULTRA GÜVENLİK SİSTEMİ** ile korunmaktadır. Bu, dünyanın en güvenli admin paneli sistemidir!

## 🏰 3 Katmanlı Güvenlik

```
LAYER 1: SECRET MASTER KEY 
    ↓
LAYER 2: ROTATING TOKEN
    ↓
LAYER 3: ADMIN AUTHENTICATION
    ↓
✅ ADMIN PANEL
```

## 🌟 Özellikler

- ✅ **3 Katmanlı Koruma** - Triple security layer
- ✅ **Secret Master Key** - Token generator sayfası bile korumalı
- ✅ **Rotating Token** - 64 karakter, kriptografik güvenli, tek kullanımlık
- ✅ **Zamanaşımı** - Her katman 24 saat sonra expire olur
- ✅ **IP Takibi** - Hangi IP'den kullanıldığı kaydedilir
- ✅ **Zero Info Disclosure** - Yetkisiz erişim = 404, hiçbir ipucu yok
- ✅ **Database Backed** - Her token ve kullanım DB'de

## 🚀 Hızlı Başlangıç (3 Adım!)

### Adım 1: Secret Access Portal

1. Tarayıcıda şu sayfayı aç:
   ```
   http://localhost:8080/admin/secret_access.php
   ```

2. **Master Key'i gir:**
   ```
   de0422ac66fd6854c3d189e3d0f2549965428ba3997170d24b934ea65fbc871e
   ```

3. "Unlock Access" butonuna tıkla

### Adım 2: Token Oluştur

Otomatik olarak token generator sayfasına yönlendirileceksin.

**"Token'ı Otomatik Ayarla"** butonuna tıkla

### Adım 3: Admin Girişi

Otomatik olarak admin login sayfasına yönlendirileceksin.
burada sana verilen tokenı sitenin console kısmında aşağıdaki adımı yap
 Çıktıdaki token'ı kopyala ve tarayıcı konsolunda çalıştır:
   ```javascript
   document.cookie = "admin_access_token=TOKEN_BURAYA_YAPISTIR; path=/; max-age=86400";
   ```

Admin bilgilerinle giriş yap.

**✅ Admin paneline erişebilirsin! 🎉**

### Yöntem 2: Komut Satırından (CLI)

1. Proje dizinine git:
   ```bash
   cd "C:\Users\ıbrahım\Desktop\cursor\Siber-Otobüs2"
   ```

2. Docker container içinde script'i çalıştır:
   ```bash
   docker compose exec web php /var/www/html/admin/generate_access_token.php
   ```

3. Çıktıdaki token'ı kopyala ve tarayıcı konsolunda çalıştır:
   ```javascript
   document.cookie = "admin_access_token=TOKEN_BURAYA_YAPISTIR; path=/; max-age=86400";
   ```

### Yöntem 3: Manuel Token Oluşturma

Token generator sayfasını aç ve "Token'ı Kopyala" butonuna tıkla. Sonra manuel olarak cookie olarak ekle.

## 🛡️ Güvenlik Notları

### Neden Bu Kadar Güvenli?

1. **Rotating Token** - Her erişim için yeni token gerekir
2. **Tek Kullanımlık** - Token kullanıldıktan sonra geçersiz olur
3. **Zamanaşımı** - 24 saat sonra otomatik olarak expire olur
4. **Veritabanı Kontrolü** - Her token DB'de doğrulanır
5. **IP Logging** - Hangi IP'den kullanıldığı kaydedilir
6. **404 Yanıtı** - Yetkisiz erişimler sayfa yok hatası görür
7. **Otomatik Temizleme** - Eski token'lar otomatik silinir

### En İyi Güvenlik Pratikleri

- ✅ **Her erişim için yeni token oluştur**
- ✅ **Token'ları kimseyle paylaşma**
- ✅ **HTTPS kullan** (production'da)
- ✅ **Logları düzenli kontrol et**
- ✅ **Token süresini ihtiyaca göre ayarla**
- ✅ **generate_access_token.php dosyasını production'da koru**

## 📊 Token Yönetimi

### Token Durumunu Kontrol Et

Token veritabanını görmek için:

```sql
SELECT * FROM Admin_Access_Tokens ORDER BY created_at DESC LIMIT 10;
```

### Kullanılmış Token'ları Temizle

```sql
DELETE FROM Admin_Access_Tokens WHERE used = 1;
```

### Tüm Token'ları Sıfırla (Acil Durum)

```sql
DELETE FROM Admin_Access_Tokens;
```

## ❌ Token Olmadan Erişim

Token olmadan veya geçersiz token ile admin sayfalarına erişmeye çalışırsan:
- ❌ HTTP 404 "Not Found" hatası
- ❌ Sayfa gerçekten yok gibi görünür
- ❌ Hiçbir bilgi sızdırılmaz
- ✅ IP adresi loglanabilir (isteğe bağlı)

## ✅ Başarılı Erişim Akışı

1. Token generator sayfasını aç
2. Yeni token oluştur
3. Token'ı cookie olarak ayarla
4. Admin login sayfasına git
5. Admin bilgileriyle giriş yap
6. Admin paneline erişebilirsin
7. **Bir sonraki erişim için yeni token oluştur**

## 🧪 Test Komutları

### Token kontrolü:

```javascript
// Console'da çalıştır:
console.log(document.cookie.includes('admin_access_token') ? '✅ Token var' : '❌ Token yok');
```

### Token'ı sil:

```javascript
document.cookie = "admin_access_token=; path=/; max-age=0";
alert("Token silindi!");
```

### Yeni token oluştur ve ayarla:

```javascript
// Token generator sayfasını aç:
window.open('/admin/generate_access_token.php', '_blank');
```

## 🔄 Token Lifecycle

```
1. Oluşturma    → generate_access_token.php
                  ↓
2. Kayıt        → Admin_Access_Tokens tablosuna eklenir
                  ↓
3. Ayarlama     → Cookie olarak tarayıcıya set edilir
                  ↓
4. Doğrulama    → admin_login.php erişiminde kontrol edilir
                  ↓
5. Kullanım     → admin/panel.php erişiminde "used=1" olarak işaretlenir
                  ↓
6. Expire       → 24 saat sonra veya kullanıldıktan sonra geçersiz
                  ↓
7. Temizleme    → Otomatik olarak veritabanından silinir
```

## 📋 Token Veritabanı Şeması

```sql
CREATE TABLE Admin_Access_Tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token TEXT UNIQUE NOT NULL,           -- 64 karakter güvenli token
    expires_at TEXT NOT NULL,             -- Geçerlilik süresi
    used INTEGER DEFAULT 0,               -- Kullanıldı mı?
    created_at TEXT DEFAULT (datetime('now', 'localtime')),
    used_at TEXT,                         -- Kullanım zamanı
    ip_address TEXT                       -- Kullanılan IP
);
```

