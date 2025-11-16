<?php
/**
 * File Upload and Translation Handler
 */

require_once 'config.php';
require_once 'translator.php';

header('Content-Type: application/json');

try {
    // Hedef dil kontrolü
    if (!isset($_POST['target_lang']) || empty($_POST['target_lang'])) {
        throw new Exception('Hedef dil seçilmedi!');
    }

    $targetLang = $_POST['target_lang'];

    if (!isset(SUPPORTED_LANGUAGES[$targetLang])) {
        throw new Exception('Geçersiz hedef dil!');
    }

    // Dosya kontrolü
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yüklenemedi!');
    }

    $uploadedFile = $_FILES['file'];

    // Dosya boyutu kontrolü
    if ($uploadedFile['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('Dosya boyutu çok büyük! Maksimum ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB olmalıdır.');
    }

    // ZIP dosyası kontrolü
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
    finfo_close($finfo);

    if ($mimeType !== 'application/zip' && $mimeType !== 'application/x-zip-compressed') {
        throw new Exception('Sadece ZIP dosyaları kabul edilir!');
    }

    // Benzersiz ID oluştur
    $uniqueId = uniqid('trans_', true);
    $uploadDir = UPLOAD_DIR . $uniqueId . '/';
    $outputDir = OUTPUT_DIR . $uniqueId . '/';

    // Klasörleri oluştur
    if (!mkdir($uploadDir, 0755, true)) {
        throw new Exception('Yükleme klasörü oluşturulamadı!');
    }

    if (!mkdir($outputDir, 0755, true)) {
        throw new Exception('Çıktı klasörü oluşturulamadı!');
    }

    // ZIP dosyasını kaydet
    $zipPath = $uploadDir . 'original.zip';
    if (!move_uploaded_file($uploadedFile['tmp_name'], $zipPath)) {
        throw new Exception('Dosya kaydedilemedi!');
    }

    // ZIP'i aç
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new Exception('ZIP dosyası açılamadı!');
    }

    $extractPath = $uploadDir . 'extracted/';
    if (!$zip->extractTo($extractPath)) {
        $zip->close();
        throw new Exception('ZIP dosyası çıkarılamadı!');
    }
    $zip->close();

    // Çeviriyi başlat
    $translator = new Translator();
    $processor = new FileProcessor($translator);

    $stats = $processor->processDirectory($extractPath, $outputDir, $targetLang);

    // Çevrilmiş dosyaları ZIP'le
    $outputZipName = 'translated_' . $targetLang . '_' . time() . '.zip';
    $outputZipPath = OUTPUT_DIR . $outputZipName;

    $outputZip = new ZipArchive();
    if ($outputZip->open($outputZipPath, ZipArchive::CREATE) !== true) {
        throw new Exception('Çıktı ZIP dosyası oluşturulamadı!');
    }

    // Tüm dosyaları ZIP'e ekle
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($outputDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($outputDir));

        if ($file->isDir()) {
            $outputZip->addEmptyDir($relativePath);
        } else {
            $outputZip->addFile($filePath, $relativePath);
        }
    }

    $outputZip->close();

    // Upload klasörünü temizle (sadece output'u tut)
    deleteDirectory($uploadDir);

    // Başarı yanıtı
    echo json_encode([
        'success' => true,
        'filename' => $outputZipName,
        'stats' => $stats,
        'message' => 'Çeviri başarıyla tamamlandı!'
    ]);

} catch (Exception $e) {
    // Hata durumunda klasörleri temizle
    if (isset($uploadDir) && file_exists($uploadDir)) {
        deleteDirectory($uploadDir);
    }
    if (isset($outputDir) && file_exists($outputDir)) {
        deleteDirectory($outputDir);
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
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
