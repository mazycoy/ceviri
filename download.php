<?php
/**
 * Download Handler - İndirme sonrası otomatik temizlik
 */

require_once 'config.php';

if (!isset($_GET['file']) || empty($_GET['file'])) {
    die('Geçersiz dosya!');
}

$filename = basename($_GET['file']); // Güvenlik için
$filepath = OUTPUT_DIR . $filename;

// Dosya var mı kontrol et
if (!file_exists($filepath)) {
    die('Dosya bulunamadı!');
}

// Dosya boyutunu al
$filesize = filesize($filepath);

// İndirme başlıklarını ayarla
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Dosyayı gönder
readfile($filepath);

// Dosyayı ve klasörünü sil (indirme tamamlandıktan sonra)
// Klasör adını çıkar (trans_xxxxx)
$pattern = '/translated_[a-z]{2}_\d+\.zip$/';
if (preg_match($pattern, $filename)) {
    // ZIP dosyasını sil
    @unlink($filepath);

    // İlgili klasörü bul ve sil
    $dir = dirname($filepath);
    if (basename($dir) !== 'outputs') {
        // Alt klasördeyse onu sil
        deleteDirectory($dir);
    }
}

/**
 * Klasörü ve içindekileri sil
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

exit;
?>
