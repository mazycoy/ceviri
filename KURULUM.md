# 🚀 Hızlı Kurulum Rehberi

## 1. Dosyaları cPanel'e Yükleme

### File Manager ile:
1. cPanel'e giriş yapın
2. File Manager'ı açın
3. `public_html` klasörüne gidin
4. "Create" > "New Folder" ile `ceviri` klasörü oluşturun
5. Tüm proje dosyalarını `ceviri` klasörüne yükleyin

### FTP ile:
```bash
ftp siteniz.com
cd public_html
mkdir ceviri
cd ceviri
# Dosyaları yükleyin
```

## 2. API Key Alma

### Gemini API (Önerilen - Ücretsiz):
1. https://makersuite.google.com/app/apikey adresine gidin
2. Google hesabınızla giriş yapın
3. "Create API Key" butonuna tıklayın
4. API key'inizi kopyalayın

### Groq API (Alternatif - Daha Hızlı):
1. https://console.groq.com/ adresine gidin
2. Hesap oluşturun
3. "API Keys" bölümüne gidin
4. "Create API Key" butonuna tıklayın
5. API key'inizi kopyalayın

## 3. config.php Ayarlama

`config.example.php` dosyasını `config.php` olarak kopyalayın:

```bash
cp config.example.php config.php
```

Sonra `config.php` dosyasını düzenleyin:

```php
// Gemini için
define('USE_GEMINI', true);
define('GEMINI_API_KEY', 'AIzaSy...');  // Buraya API key'inizi yapıştırın

// VEYA Groq için
define('USE_GEMINI', false);
define('GROQ_API_KEY', 'gsk_...');  // Buraya API key'inizi yapıştırın
```

## 4. Klasör İzinleri

cPanel File Manager'da:
1. `uploads` klasörüne sağ tıklayın > "Permissions" > 755
2. `outputs` klasörüne sağ tıklayın > "Permissions" > 755

veya SSH ile:

```bash
chmod 755 uploads
chmod 755 outputs
chmod 644 *.php
chmod 644 .htaccess
```

## 5. Test Etme

1. Tarayıcınızda `https://siteniz.com/ceviri` adresini açın
2. Yeşil "✓ Gemini API aktif" veya "✓ Groq API aktif" mesajını görmelisiniz
3. Bir test ZIP dosyası yükleyin
4. Çeviri işlemini test edin

## 6. Otomatik Temizlik (Önerilen)

cPanel > Cron Jobs bölümünde:

**Her 15 dakikada bir:**
```bash
*/15 * * * * php /home/KULLANICIADI/public_html/ceviri/cleanup.php
```

**NOT**: `KULLANICIADI` kısmını kendi cPanel kullanıcı adınızla değiştirin!

## 7. Güvenlik (ÖNEMLİ!)

### .htaccess kontrolü:
`uploads` klasörünün doğrudan erişime kapalı olduğundan emin olun.

### config.php güvenliği:
```php
// Production'da hata gösterimini kapatın
error_reporting(0);
ini_set('display_errors', 0);
```

## ✅ Kurulum Tamamlandı!

Artık web sitenizin dosyalarını çevirebilirsiniz:

1. ZIP dosyanızı hazırlayın
2. `https://siteniz.com/ceviri` adresine gidin
3. Hedef dili seçin
4. ZIP dosyanızı yükleyin
5. "Çeviriyi Başlat" butonuna tıklayın
6. Çevrilen dosyaları indirin

## 🆘 Sorun mu Yaşıyorsunuz?

### "API Key geçersiz" hatası:
- API key'inizi doğru kopyaladığınızdan emin olun
- API key'inizde ekstra boşluk olmadığını kontrol edin
- API servisinin aktif olduğunu kontrol edin

### "Dosya yüklenemedi" hatası:
- Klasör izinlerini kontrol edin (755)
- PHP upload limitini kontrol edin (cPanel > MultiPHP INI Editor)
- Disk alanınızı kontrol edin

### "Sayfa açılmıyor" hatası:
- .htaccess dosyasını kontrol edin
- PHP versiyonunun 7.4+ olduğunu kontrol edin (cPanel > MultiPHP Manager)
- Error log'u kontrol edin (cPanel > Errors)

## 📞 Ek Destek

1. README.md dosyasını okuyun
2. cPanel Error Log'u kontrol edin
3. PHP error_log dosyasını kontrol edin

---

**Başarılar! 🎉**
