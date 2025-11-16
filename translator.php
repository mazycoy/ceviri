<?php
/**
 * Translation Engine - Gemini & Groq API Support
 */

require_once 'config.php';

class Translator {
    private $apiKey;
    private $useGemini;

    public function __construct() {
        $this->useGemini = USE_GEMINI;
        $this->apiKey = USE_GEMINI ? GEMINI_API_KEY : GROQ_API_KEY;
    }

    /**
     * Metni çevir
     */
    public function translate($text, $targetLang, $sourceLang = 'auto') {
        if (empty(trim($text))) {
            return $text;
        }

        if ($this->useGemini) {
            return $this->translateWithGemini($text, $targetLang, $sourceLang);
        } else {
            return $this->translateWithGroq($text, $targetLang, $sourceLang);
        }
    }

    /**
     * Gemini API ile çeviri
     */
    private function translateWithGemini($text, $targetLang, $sourceLang) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->apiKey;

        $langName = SUPPORTED_LANGUAGES[$targetLang] ?? $targetLang;

        $prompt = "Translate the following text to {$langName}. ";
        $prompt .= "IMPORTANT: Return ONLY the translated text, nothing else. ";
        $prompt .= "Do not add explanations, notes, or any other text. ";
        $prompt .= "Preserve all HTML tags, special characters, and formatting exactly as they are.\n\n";
        $prompt .= "Text to translate:\n{$text}";

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 2048,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Gemini API Error: " . $response);
            return $text;  // Hata durumunda orijinal metni döndür
        }

        $result = json_decode($response, true);

        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($result['candidates'][0]['content']['parts'][0]['text']);
        }

        return $text;
    }

    /**
     * Groq API ile çeviri (Llama model kullanarak)
     */
    private function translateWithGroq($text, $targetLang, $sourceLang) {
        $url = 'https://api.groq.com/openai/v1/chat/completions';

        $langName = SUPPORTED_LANGUAGES[$targetLang] ?? $targetLang;

        $prompt = "Translate the following text to {$langName}. ";
        $prompt .= "IMPORTANT: Return ONLY the translated text, nothing else. ";
        $prompt .= "Do not add explanations, notes, or any other text. ";
        $prompt .= "Preserve all HTML tags, special characters, and formatting exactly as they are.\n\n";
        $prompt .= "Text to translate:\n{$text}";

        $data = [
            'model' => 'llama-3.3-70b-versatile',  // Groq'un güçlü modeli
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.1,
            'max_tokens' => 2048,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Groq API Error: " . $response);
            return $text;
        }

        $result = json_decode($response, true);

        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }

        return $text;
    }
}

/**
 * File Processor - Dosyalardaki metinleri tespit ve çevir
 */
class FileProcessor {
    private $translator;
    private $stats = [
        'total_files' => 0,
        'processed_files' => 0,
        'translated_strings' => 0,
        'errors' => []
    ];

    public function __construct(Translator $translator) {
        $this->translator = $translator;
    }

    /**
     * Klasördeki tüm dosyaları işle
     */
    public function processDirectory($inputDir, $outputDir, $targetLang) {
        $this->copyDirectory($inputDir, $outputDir);
        $this->translateFilesInDirectory($outputDir, $targetLang);
        return $this->stats;
    }

    /**
     * Klasörü kopyala
     */
    private function copyDirectory($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                    $this->stats['total_files']++;
                }
            }
        }
        closedir($dir);
    }

    /**
     * Klasördeki dosyaları çevir
     */
    private function translateFilesInDirectory($dir, $targetLang) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $this->translateFile($file->getPathname(), $targetLang);
            }
        }
    }

    /**
     * Tek bir dosyayı çevir
     */
    private function translateFile($filePath, $targetLang) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            return;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->stats['errors'][] = "Could not read: " . basename($filePath);
            return;
        }

        $originalContent = $content;

        // Farklı dosya tiplerine göre işle
        switch ($extension) {
            case 'php':
            case 'html':
            case 'htm':
                $content = $this->translateHTML($content, $targetLang);
                break;
            case 'js':
                $content = $this->translateJavaScript($content, $targetLang);
                break;
            case 'json':
                $content = $this->translateJSON($content, $targetLang);
                break;
            case 'css':
                $content = $this->translateCSS($content, $targetLang);
                break;
            default:
                $content = $this->translatePlainText($content, $targetLang);
        }

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->stats['processed_files']++;
        }
    }

    /**
     * HTML/PHP dosyalarındaki metinleri çevir
     */
    private function translateHTML($content, $targetLang) {
        // DOMDocument kullanarak güvenli çeviri
        // Önce PHP kodunu koru
        $phpBlocks = [];
        $phpIndex = 0;

        // PHP bloklarını geçici placeholder'larla değiştir (tüm formatlar)
        $content = preg_replace_callback('/<\?(php|=)?.*?\?>/s', function($match) use (&$phpBlocks, &$phpIndex) {
            $placeholder = "___PHP_BLOCK_" . $phpIndex . "___";
            $phpBlocks[$placeholder] = $match[0];
            $phpIndex++;
            return $placeholder;
        }, $content);

        // Script ve style etiketlerini koru
        $scriptBlocks = [];
        $scriptIndex = 0;

        $content = preg_replace_callback('/<script[^>]*>.*?<\/script>/si', function($match) use (&$scriptBlocks, &$scriptIndex) {
            $placeholder = "___SCRIPT_BLOCK_" . $scriptIndex . "___";
            $scriptBlocks[$placeholder] = $match[0];
            $scriptIndex++;
            return $placeholder;
        }, $content);

        $styleBlocks = [];
        $styleIndex = 0;

        $content = preg_replace_callback('/<style[^>]*>.*?<\/style>/si', function($match) use (&$styleBlocks, &$styleIndex) {
            $placeholder = "___STYLE_BLOCK_" . $styleIndex . "___";
            $styleBlocks[$placeholder] = $match[0];
            $styleIndex++;
            return $placeholder;
        }, $content);

        // Sadece belirli HTML attribute'larını çevir (güvenli olanlar)
        $safeAttributes = ['title', 'alt', 'placeholder', 'aria-label', 'data-title', 'data-label'];

        foreach ($safeAttributes as $attr) {
            $pattern = '/(' . $attr . ')\s*=\s*"([^"]+)"/i';
            $content = preg_replace_callback($pattern, function($matches) use ($targetLang) {
                $attrName = $matches[1];
                $text = $matches[2];

                // Filtreler
                if (!$this->shouldTranslate($text)) {
                    return $matches[0];
                }

                $translated = $this->translator->translate($text, $targetLang);
                $this->stats['translated_strings']++;

                return $attrName . '="' . $translated . '"';
            }, $content);
        }

        // HTML etiketleri arasındaki düz metinleri çevir (ama dikkatli)
        $content = preg_replace_callback('/>([^<]+)</s', function($matches) use ($targetLang) {
            $text = $matches[1];

            // Filtreler
            if (!$this->shouldTranslate($text)) {
                return $matches[0];
            }

            // PHP değişkeni veya kodu varsa atla
            if (strpos($text, '$') !== false || strpos($text, '<?') !== false) {
                return $matches[0];
            }

            $translated = $this->translator->translate(trim($text), $targetLang);
            $this->stats['translated_strings']++;

            // Boşlukları koru
            $leadingSpace = strlen($text) - strlen(ltrim($text));
            $trailingSpace = strlen($text) - strlen(rtrim($text));

            return '>' . str_repeat(' ', $leadingSpace) . $translated . str_repeat(' ', $trailingSpace) . '<';
        }, $content);

        // Blokları geri koy (ters sırayla)
        foreach ($styleBlocks as $placeholder => $styleCode) {
            $content = str_replace($placeholder, $styleCode, $content);
        }

        foreach ($scriptBlocks as $placeholder => $scriptCode) {
            $content = str_replace($placeholder, $scriptCode, $content);
        }

        foreach ($phpBlocks as $placeholder => $phpCode) {
            $content = str_replace($placeholder, $phpCode, $content);
        }

        return $content;
    }

    /**
     * Bir metnin çevrilip çevrilmeyeceğini kontrol et
     */
    private function shouldTranslate($text) {
        $text = trim($text);

        // Boş veya çok kısa
        if (strlen($text) < 3) {
            return false;
        }

        // Sadece sayı
        if (is_numeric($text)) {
            return false;
        }

        // Sadece özel karakterler
        if (preg_match('/^[\s\W]+$/', $text)) {
            return false;
        }

        // URL
        if (preg_match('/^(https?:\/\/|www\.|\/\/)/i', $text)) {
            return false;
        }

        // Dosya yolu
        if (preg_match('/^[\/\\\\]|[a-z]:\\\/i', $text)) {
            return false;
        }

        // Email
        if (preg_match('/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i', $text)) {
            return false;
        }

        // CSS/JS kodu (class, id, function isimleri)
        if (preg_match('/^[a-z_][a-z0-9_-]*$/i', $text)) {
            return false;
        }

        // HTML/PHP kod parçaları
        if (preg_match('/<|>|\{|\}|\$|;|function|class|return|var|const|let/i', $text)) {
            return false;
        }

        // Sadece büyük harfler (constant olabilir)
        if (preg_match('/^[A-Z_0-9]+$/', $text)) {
            return false;
        }

        return true;
    }

    /**
     * JavaScript dosyalarındaki string'leri çevir
     */
    private function translateJavaScript($content, $targetLang) {
        // Sadece belirli fonksiyonlardaki string'leri çevir (alert, console.log vb.)
        $userFacingFunctions = ['alert', 'confirm', 'prompt', 'innerHTML', 'textContent', 'innerText', 'setAttribute'];

        foreach ($userFacingFunctions as $func) {
            // alert("text") formatı
            $pattern = '/' . $func . '\s*\(\s*(["\'])([^"\']+)\1\s*\)/i';
            $content = preg_replace_callback($pattern, function($matches) use ($targetLang, $func) {
                $quote = $matches[1];
                $text = $matches[2];

                if (!$this->shouldTranslate($text)) {
                    return $matches[0];
                }

                $translated = $this->translator->translate($text, $targetLang);
                $this->stats['translated_strings']++;

                return $func . '(' . $quote . $translated . $quote . ')';
            }, $content);
        }

        // NOT: Diğer JavaScript string'lerini çevirmiyoruz çünkü bunlar değişken adları,
        // API endpoint'leri, CSS class isimleri vb. olabilir

        return $content;
    }

    /**
     * JSON dosyalarındaki değerleri çevir
     */
    private function translateJSON($content, $targetLang) {
        $data = json_decode($content, true);

        if ($data === null) {
            return $content;
        }

        $data = $this->translateArray($data, $targetLang);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Array içindeki string'leri çevir
     */
    private function translateArray($array, $targetLang) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->translateArray($value, $targetLang);
            } elseif (is_string($value)) {
                // Daha dikkatli filtreleme
                if ($this->shouldTranslate($value)) {
                    $array[$key] = $this->translator->translate($value, $targetLang);
                    $this->stats['translated_strings']++;
                }
            }
        }
        return $array;
    }

    /**
     * CSS dosyalarındaki content değerlerini çevir
     */
    private function translateCSS($content, $targetLang) {
        $pattern = '/content:\s*["\']([^"\']+)["\']/i';

        $content = preg_replace_callback($pattern, function($matches) use ($targetLang) {
            $text = $matches[1];

            if (!$this->shouldTranslate($text)) {
                return $matches[0];
            }

            $translated = $this->translator->translate($text, $targetLang);
            $this->stats['translated_strings']++;

            return str_replace($text, $translated, $matches[0]);
        }, $content);

        return $content;
    }

    /**
     * Plain text çevir
     */
    private function translatePlainText($content, $targetLang) {
        // Plain text dosyaları genellikle config veya log dosyalarıdır
        // Sadece açıkça metin dosyası olduğundan emin isek çevir
        if (!$this->shouldTranslate($content)) {
            return $content;
        }

        // Çok uzun dosyaları çevirme (performans)
        if (strlen($content) > 10000) {
            return $content;
        }

        $translated = $this->translator->translate($content, $targetLang);
        $this->stats['translated_strings']++;

        return $translated;
    }

    public function getStats() {
        return $this->stats;
    }
}
?>
