<?php
/**
 * Website File Translator Configuration Example
 * Bu dosyayı config.php olarak kopyalayın ve API key'lerinizi girin
 */

// Hata raporlama (production'da kapatın)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// API Ayarları - Birini seçin
define('USE_GEMINI', true);  // Gemini kullanmak için true, Groq için false
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');  // https://makersuite.google.com/app/apikey
define('GROQ_API_KEY', 'YOUR_GROQ_API_KEY_HERE');      // https://console.groq.com/keys

// Dosya Ayarları
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024);  // 50MB maksimum yükleme boyutu
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('OUTPUT_DIR', __DIR__ . '/outputs/');
define('ALLOWED_EXTENSIONS', ['php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'txt', 'twig', 'blade.php']);

// Otomatik Temizleme Ayarları
define('AUTO_CLEANUP_MINUTES', 30);  // Dosyalar 30 dakika sonra silinir

// Çeviri Ayarları
define('DEFAULT_SOURCE_LANG', 'auto');  // Otomatik kaynak dil tespiti
define('SUPPORTED_LANGUAGES', [
    'tr' => 'Türkçe',
    'en' => 'English',
    'de' => 'Deutsch',
    'fr' => 'Français',
    'es' => 'Español',
    'it' => 'Italiano',
    'pt' => 'Português',
    'ru' => 'Русский',
    'ar' => 'العربية',
    'zh' => '中文',
    'ja' => '日本語',
    'ko' => '한국어'
]);

// Dizinleri oluştur
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(OUTPUT_DIR)) {
    mkdir(OUTPUT_DIR, 0755, true);
}

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
