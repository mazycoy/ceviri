# 🌐 Website Dosya Çevirici

Web sitesi ve tema dosyalarınızı otomatik olarak istediğiniz dile çeviren profesyonel PHP sistemi. Gemini AI veya Groq API kullanarak HTML, PHP, CSS, JavaScript ve JSON dosyalarındaki tüm metinleri tespit eder ve çevirir.

## ✨ Özellikler

- 🎯 **Akıllı Metin Tespiti**: HTML, PHP, CSS, JavaScript, JSON dosyalarındaki tüm metinleri otomatik tespit
- 🌍 **12+ Dil Desteği**: Türkçe, İngilizce, Almanca, Fransızca, İspanyolca ve daha fazlası
- 🤖 **Çift API Desteği**: Gemini AI veya Groq API ile çalışma seçeneği
- 🔒 **Güvenli**: Otomatik temizlik sistemi ile dosyalarınız işlem sonrası silinir
- ⚡ **Hızlı**: Modern PHP ile optimize edilmiş performans
- 📦 **Kolay Kullanım**: ZIP yükle, çevir, indir
- 🎨 **Modern UI**: Kullanıcı dostu arayüz
- 🧹 **Otomatik Temizlik**: İndirme sonrası veya belirli süre sonra dosyaları temizler

## 📋 Gereksinimler

- PHP 7.4 veya üzeri
- cPanel Hosting
- PHP ZipArchive extension
- cURL extension
- Gemini API Key VEYA Groq API Key

## 🚀 Kurulum

### 1. Dosyaları Yükleme

cPanel File Manager üzerinden projeyi `public_html/ceviri` klasörüne yükleyin.

```
public_html/
└── ceviri/
    ├── config.php
    ├── index.php
    ├── upload.php
    ├── download.php
    ├── translator.php
    ├── cleanup.php
    ├── .htaccess
    └── README.md
```

### 2. API Key Alma

#### Gemini API (Önerilen)
1. [Google AI Studio](https://makersuite.google.com/app/apikey) adresine gidin
2. "Create API Key" butonuna tıklayın
3. API Key'inizi kopyalayın

#### Groq API (Alternatif)
1. [Groq Console](https://console.groq.com/keys) adresine gidin
2. Hesap oluşturun
3. API Key oluşturun ve kopyalayın

### 3. Yapılandırma

`config.php` dosyasını düzenleyin:

```php
// Gemini kullanmak için
define('USE_GEMINI', true);
define('GEMINI_API_KEY', 'buraya_api_keyinizi_yazin');

// VEYA Groq kullanmak için
define('USE_GEMINI', false);
define('GROQ_API_KEY', 'buraya_api_keyinizi_yazin');
```

### 4. Klasör İzinleri

cPanel File Manager'da aşağıdaki klasörlere 755 izni verin:

```bash
chmod 755 uploads/
chmod 755 outputs/
```

### 5. Otomatik Temizlik (Opsiyonel)

cPanel > Cron Jobs bölümünden:

```bash
# Her 15 dakikada bir temizlik çalıştır
*/15 * * * * php /home/kullaniciadi/public_html/ceviri/cleanup.php

# Veya her saat başı
0 * * * * php /home/kullaniciadi/public_html/ceviri/cleanup.php
```

## 📖 Kullanım

### Web Arayüzü Üzerinden

1. Tarayıcınızda `https://siteniz.com/ceviri` adresini açın
2. Hedef dili seçin (örn: İngilizce)
3. Web sitenizin ZIP dosyasını yükleyin
4. "Çeviriyi Başlat" butonuna tıklayın
5. İşlem tamamlandığında dosyalar otomatik indirilir

### Desteklenen Dosya Formatları

- ✅ HTML (.html, .htm)
- ✅ PHP (.php)
- ✅ CSS (.css)
- ✅ JavaScript (.js)
- ✅ JSON (.json)
- ✅ XML (.xml)
- ✅ Text (.txt)
- ✅ Twig (.twig)
- ✅ Blade (.blade.php)

### Desteklenen Diller

| Kod | Dil |
|-----|-----|
| tr | Türkçe |
| en | English |
| de | Deutsch |
| fr | Français |
| es | Español |
| it | Italiano |
| pt | Português |
| ru | Русский |
| ar | العربية |
| zh | 中文 |
| ja | 日本語 |
| ko | 한국어 |

## 🔧 Yapılandırma Seçenekleri

`config.php` dosyasında düzenleyebileceğiniz ayarlar:

```php
// Maksimum dosya boyutu (byte cinsinden)
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024);  // 50MB

// Otomatik temizleme süresi (dakika)
define('AUTO_CLEANUP_MINUTES', 30);  // 30 dakika

// İzin verilen dosya uzantıları
define('ALLOWED_EXTENSIONS', ['php', 'html', 'css', 'js', 'json']);
```

## 🛡️ Güvenlik

- ✅ Sadece ZIP dosyaları kabul edilir
- ✅ Dosya boyutu kontrolü
- ✅ Dosya tipi doğrulaması
- ✅ Otomatik klasör temizliği
- ✅ Güvenli dosya isimlendirme
- ✅ XSS koruması
- ✅ Path traversal koruması

## 📊 Çeviri İstatistikleri

Her çeviri işlemi sonrası:

- Toplam dosya sayısı
- İşlenen dosya sayısı
- Çevrilen string sayısı
- Oluşan hatalar (varsa)

## 🐛 Sorun Giderme

### "API Key geçersiz" hatası
- `config.php` dosyasında API key'inizi kontrol edin
- API key'inizin aktif olduğundan emin olun

### "Dosya yüklenemedi" hatası
- `uploads/` klasörünün yazma iznine sahip olduğunu kontrol edin
- `.htaccess` dosyasının doğru yapılandırıldığından emin olun
- PHP upload limitlerini kontrol edin

### "Çeviri başarısız" hatası
- İnternet bağlantınızı kontrol edin
- API limitinizi kontrol edin (Gemini/Groq)
- Dosyanızın çok büyük olmadığından emin olun

### Dosyalar silinmiyor
- Cron job'un doğru ayarlandığından emin olun
- `cleanup.php` dosyasının çalıştırılabilir olduğunu kontrol edin
- Klasör izinlerini kontrol edin

## 📝 Örnekler

### WordPress Tema Çevirisi

1. WordPress temanızın klasörünü ZIP olarak indirin
2. Sisteme yükleyin ve hedef dili seçin
3. Çevrilen ZIP'i indirin
4. Çıkan dosyaları tema klasörünüze yükleyin

### HTML Template Çevirisi

1. HTML template'inizin tüm dosyalarını ZIP'leyin
2. Sisteme yükleyin
3. İstediğiniz dile çevirin
4. Çevrilen dosyaları kullanın

## 🔄 Güncelleme Notları

### v1.0.0 (2024)
- ✨ İlk sürüm
- ✅ Gemini API desteği
- ✅ Groq API desteği
- ✅ 12 dil desteği
- ✅ Otomatik temizlik sistemi
- ✅ Modern UI

## 📞 Destek

Sorunlarınız için:
1. Bu README dosyasını dikkatlice okuyun
2. `config.php` ayarlarınızı kontrol edin
3. PHP hata loglarını kontrol edin (cPanel > Error Log)

## 📄 Lisans

Bu proje özel kullanım içindir. Ticari kullanım için izin alınmalıdır.

## 🙏 Katkıda Bulunanlar

- Google Gemini API
- Groq API
- PHP Community

---

**Not**: Bu sistem cPanel hosting üzerinde çalışmak üzere tasarlanmıştır. Diğer hosting ortamlarında çalışması için ek yapılandırma gerekebilir.

**Güvenlik Uyarısı**: API key'lerinizi asla paylaşmayın ve `config.php` dosyasını public erişime kapatın!
