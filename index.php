<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Dosya Çevirici - Tema Dosyalarını Çevir</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }

        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }

        .info-box h3 {
            color: #667eea;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .info-box ul {
            margin-left: 20px;
            color: #555;
            font-size: 14px;
        }

        .info-box ul li {
            margin: 5px 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        select, input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        select:focus, input[type="file"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: background 0.3s;
        }

        .file-input-wrapper:hover {
            background: #e8ebff;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: -9999px;
        }

        .file-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .file-text {
            color: #666;
            font-size: 14px;
        }

        .file-selected {
            margin-top: 10px;
            color: #667eea;
            font-weight: 600;
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .progress {
            display: none;
            margin-top: 20px;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }

        .status {
            margin-top: 10px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .api-status {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 13px;
            color: #856404;
        }

        .api-status.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .max-size {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Website Dosya Çevirici</h1>
        <p class="subtitle">Tema dosyalarınızı otomatik olarak istediğiniz dile çevirin</p>

        <?php
        $apiConfigured = (USE_GEMINI && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY_HERE') ||
                         (!USE_GEMINI && GROQ_API_KEY !== 'YOUR_GROQ_API_KEY_HERE');

        $apiName = USE_GEMINI ? 'Gemini' : 'Groq';
        ?>

        <?php if ($apiConfigured): ?>
            <div class="api-status success">
                ✓ <?php echo $apiName; ?> API aktif
            </div>
        <?php else: ?>
            <div class="api-status">
                ⚠ Lütfen config.php dosyasında <?php echo $apiName; ?> API anahtarınızı ayarlayın
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>📋 Özellikler:</h3>
            <ul>
                <li>HTML, PHP, CSS, JavaScript, JSON dosyalarını destekler</li>
                <li>Tema metinlerini otomatik tespit eder</li>
                <li>Tüm formatlamayı ve HTML etiketlerini korur</li>
                <li>İndirme sonrası otomatik temizlik</li>
                <li>Maksimum dosya boyutu: <?php echo MAX_UPLOAD_SIZE / 1024 / 1024; ?>MB</li>
            </ul>
        </div>

        <form id="uploadForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="target_lang">Hedef Dil Seçin:</label>
                <select name="target_lang" id="target_lang" required>
                    <option value="">Dil seçiniz...</option>
                    <?php foreach (SUPPORTED_LANGUAGES as $code => $name): ?>
                        <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Website Dosyalarını Yükleyin (ZIP):</label>
                <div class="file-input-wrapper" onclick="document.getElementById('fileInput').click()">
                    <div class="file-icon">📁</div>
                    <div class="file-text">
                        ZIP dosyasını seçmek için tıklayın veya sürükleyin
                    </div>
                    <div class="file-selected" id="fileSelected"></div>
                    <input type="file" id="fileInput" name="file" accept=".zip" required>
                </div>
                <div class="max-size">Maksimum: <?php echo MAX_UPLOAD_SIZE / 1024 / 1024; ?>MB</div>
            </div>

            <button type="submit" id="submitBtn" <?php echo !$apiConfigured ? 'disabled' : ''; ?>>
                🚀 Çeviriyi Başlat
            </button>
        </form>

        <div class="progress" id="progressContainer">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill">0%</div>
            </div>
            <div class="status" id="statusText">Hazırlanıyor...</div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const fileSelected = document.getElementById('fileSelected');
        const uploadForm = document.getElementById('uploadForm');
        const progressContainer = document.getElementById('progressContainer');
        const progressFill = document.getElementById('progressFill');
        const statusText = document.getElementById('statusText');
        const submitBtn = document.getElementById('submitBtn');

        // Dosya seçildiğinde göster
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                fileSelected.textContent = `✓ ${file.name} (${sizeMB}MB)`;

                // Boyut kontrolü
                if (file.size > <?php echo MAX_UPLOAD_SIZE; ?>) {
                    alert('Dosya boyutu çok büyük! Maksimum <?php echo MAX_UPLOAD_SIZE / 1024 / 1024; ?>MB olmalıdır.');
                    this.value = '';
                    fileSelected.textContent = '';
                }
            }
        });

        // Form gönderildiğinde
        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // UI güncelle
            submitBtn.disabled = true;
            progressContainer.style.display = 'block';
            updateProgress(10, 'Dosya yükleniyor...');

            try {
                const xhr = new XMLHttpRequest();

                // İlerleme takibi
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 40) + 10;
                        updateProgress(percentComplete, 'Dosya yükleniyor...');
                    }
                });

                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            updateProgress(100, 'Çeviri tamamlandı! İndiriliyor...');

                            // Dosyayı indir
                            window.location.href = 'download.php?file=' + response.filename;

                            setTimeout(() => {
                                updateProgress(0, '');
                                progressContainer.style.display = 'none';
                                submitBtn.disabled = false;
                                uploadForm.reset();
                                fileSelected.textContent = '';
                            }, 2000);
                        } else {
                            alert('Hata: ' + response.message);
                            submitBtn.disabled = false;
                            progressContainer.style.display = 'none';
                        }
                    } else {
                        alert('Sunucu hatası oluştu!');
                        submitBtn.disabled = false;
                        progressContainer.style.display = 'none';
                    }
                });

                xhr.addEventListener('error', function() {
                    alert('Bağlantı hatası oluştu!');
                    submitBtn.disabled = false;
                    progressContainer.style.display = 'none';
                });

                updateProgress(50, 'Dosyalar çevriliyor...');
                xhr.open('POST', 'upload.php', true);
                xhr.send(formData);

            } catch (error) {
                alert('Bir hata oluştu: ' + error.message);
                submitBtn.disabled = false;
                progressContainer.style.display = 'none';
            }
        });

        function updateProgress(percent, text) {
            progressFill.style.width = percent + '%';
            progressFill.textContent = percent + '%';
            statusText.textContent = text;
        }
    </script>
</body>
</html>
