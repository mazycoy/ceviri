<?php
/**
 * Automatic Cleanup Script
 * Bu scripti cPanel'de Cron Job olarak çalıştırın
 * Örnek: */15 * * * * php /home/username/public_html/ceviri/cleanup.php
 */

require_once 'config.php';

$deletedFiles = 0;
$deletedDirs = 0;
$errors = [];

echo "=== Otomatik Temizlik Başlatıldı ===\n";
echo "Zaman: " . date('Y-m-d H:i:s') . "\n";
echo "Temizlik süresi: " . AUTO_CLEANUP_MINUTES . " dakika\n\n";

// Uploads klasörünü temizle
if (file_exists(UPLOAD_DIR)) {
    cleanDirectory(UPLOAD_DIR, $deletedFiles, $deletedDirs, $errors);
}

// Outputs klasörünü temizle
if (file_exists(OUTPUT_DIR)) {
    cleanDirectory(OUTPUT_DIR, $deletedFiles, $deletedDirs, $errors);
}

echo "\n=== Temizlik Tamamlandı ===\n";
echo "Silinen dosya sayısı: " . $deletedFiles . "\n";
echo "Silinen klasör sayısı: " . $deletedDirs . "\n";
echo "Hata sayısı: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\nHatalar:\n";
    foreach ($errors as $error) {
        echo "- " . $error . "\n";
    }
}

/**
 * Klasördeki eski dosyaları temizle
 */
function cleanDirectory($dir, &$deletedFiles, &$deletedDirs, &$errors) {
    if (!is_dir($dir)) {
        return;
    }

    $currentTime = time();
    $maxAge = AUTO_CLEANUP_MINUTES * 60; // Dakikayı saniyeye çevir

    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . $item;

        // Dosya veya klasörün yaşını kontrol et
        if (file_exists($path)) {
            $fileAge = $currentTime - filemtime($path);

            if ($fileAge > $maxAge) {
                if (is_dir($path)) {
                    // Klasörü sil
                    if (deleteDirectory($path)) {
                        $deletedDirs++;
                        echo "Klasör silindi: " . $item . " (Yaş: " . round($fileAge / 60) . " dakika)\n";
                    } else {
                        $errors[] = "Klasör silinemedi: " . $item;
                    }
                } else {
                    // Dosyayı sil
                    if (unlink($path)) {
                        $deletedFiles++;
                        echo "Dosya silindi: " . $item . " (Yaş: " . round($fileAge / 60) . " dakika)\n";
                    } else {
                        $errors[] = "Dosya silinemedi: " . $item;
                    }
                }
            }
        }
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
?>
