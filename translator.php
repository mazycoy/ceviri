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
        $safeAttributes = ['title', 'alt', 'placeholder', 'aria-label', 'data-title', 'data-label', 'label'];

        foreach ($safeAttributes as $attr) {
            // Hem çift tırnak hem tek tırnak desteği
            $patterns = [
                '/(' . $attr . ')\s*=\s*"([^"]+)"/i',
                '/(' . $attr . ')\s*=\s*\'([^\']+)\'/i'
            ];

            foreach ($patterns as $pattern) {
                $content = preg_replace_callback($pattern, function($matches) use ($targetLang) {
                    $attrName = $matches[1];
                    $text = $matches[2];
                    $quote = strpos($matches[0], '"') !== false ? '"' : "'";

                    // Filtreler
                    if (!$this->shouldTranslate($text)) {
                        return $matches[0];
                    }

                    $translated = $this->translator->translate($text, $targetLang);
                    $this->stats['translated_strings']++;

                    return $attrName . '=' . $quote . $translated . $quote;
                }, $content);
            }
        }

        // Label etiketlerinin içindeki metinleri özel olarak çevir
        $content = preg_replace_callback('/<label[^>]*>([^<]+)<\/label>/i', function($matches) use ($targetLang) {
            $fullTag = $matches[0];
            $text = $matches[1];

            if (!$this->shouldTranslate($text)) {
                return $fullTag;
            }

            $translated = $this->translator->translate(trim($text), $targetLang);
            $this->stats['translated_strings']++;

            return str_replace($text, $translated, $fullTag);
        }, $content);

        // Önemli metin etiketlerini özel olarak işle (h1-h6, p, span, div, li, a, button)
        // DÜZELTME: .*? kullanarak tüm içeriği (whitespace dahil) yakala
        $textTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'li', 'a', 'button', 'td', 'th', 'strong', 'em', 'b', 'i'];

        foreach ($textTags as $tag) {
            // Non-greedy .*? pattern ile tüm içeriği yakala (newline ve whitespace dahil)
            $content = preg_replace_callback('/<' . $tag . '([^>]*)>(.*?)<\/' . $tag . '>/is', function($matches) use ($targetLang, $tag) {
                $attributes = $matches[1];
                $text = $matches[2];

                // İç HTML etiketi varsa atla (nested tags)
                if (preg_match('/<[a-z]/i', $text)) {
                    return $matches[0];
                }

                // PHP kodu veya placeholder varsa atla
                if (strpos($text, '$') !== false || strpos($text, '<?') !== false || strpos($text, '___') !== false) {
                    return $matches[0];
                }

                // Trim edilmiş halini kontrol et
                $trimmedText = trim($text);

                if (empty($trimmedText)) {
                    return $matches[0];
                }

                if (!$this->shouldTranslate($trimmedText)) {
                    return $matches[0];
                }

                $translated = $this->translator->translate($trimmedText, $targetLang);
                $this->stats['translated_strings']++;

                // Boşlukları koru
                $leadingSpace = strlen($text) - strlen(ltrim($text));
                $trailingSpace = strlen($text) - strlen(rtrim($text));

                // Whitespace karakterlerini koru (space, tab, newline)
                $leadingWs = substr($text, 0, $leadingSpace);
                $trailingWs = substr($text, -$trailingSpace);

                return '<' . $tag . $attributes . '>' . $leadingWs . $translated . $trailingWs . '</' . $tag . '>';
            }, $content);
        }

        // Genel HTML etiketleri arasındaki kalan metinleri çevir
        // DÜZELTME: .*? pattern ile uzun metinleri ve whitespace'leri yakala
        $content = preg_replace_callback('/>(.*?)</s', function($matches) use ($targetLang) {
            $text = $matches[1];

            // Sadece boşluk ve yeni satır varsa atla
            if (trim($text) === '') {
                return $matches[0];
            }

            // İç HTML etiketi varsa atla
            if (preg_match('/<[a-z]/i', $text)) {
                return $matches[0];
            }

            // PHP değişkeni veya kodu varsa atla
            if (strpos($text, '$') !== false || strpos($text, '<?') !== false || strpos($text, '___') !== false) {
                return $matches[0];
            }

            // Trim edilmiş halini kontrol et
            $trimmedText = trim($text);

            if (!$this->shouldTranslate($trimmedText)) {
                return $matches[0];
            }

            $translated = $this->translator->translate($trimmedText, $targetLang);
            $this->stats['translated_strings']++;

            // Boşlukları koru
            $leadingSpace = strlen($text) - strlen(ltrim($text));
            $trailingSpace = strlen($text) - strlen(rtrim($text));

            // Whitespace karakterlerini koru
            $leadingWs = substr($text, 0, $leadingSpace);
            $trailingWs = substr($text, -$trailingSpace);

            return '>' . $leadingWs . $translated . $trailingWs . '<';
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
     * Daha dengeli yaklaşım: Sadece açıkça KOD olan şeyleri atla
     */
    private function shouldTranslate($text) {
        $text = trim($text);

        // Boş
        if (strlen($text) === 0) {
            return false;
        }

        // Sadece sayı veya tek karakter
        if (is_numeric($text) || strlen($text) === 1) {
            return false;
        }

        // Sadece özel karakterler veya noktalama
        if (preg_match('/^[\s\W]+$/', $text)) {
            return false;
        }

        // ÇEVİRME - Kesin kod/teknik içerik:

        // URL'ler
        if (preg_match('/^(https?:\/\/|www\.|\/\/|\.\.\/)/i', $text)) {
            return false;
        }

        // Email
        if (preg_match('/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i', $text)) {
            return false;
        }

        // Dosya yolları
        if (preg_match('/^[\/\\\\]|^[a-z]:\\\\/i', $text)) {
            return false;
        }

        // Hex renk kodları
        if (preg_match('/^#[0-9a-f]{3,6}$/i', $text)) {
            return false;
        }

        // CSS/JS kod yapıları (süslü parantez, dolar işareti, noktalı virgül)
        if (preg_match('/[\{\}\$;]/', $text)) {
            return false;
        }

        // PHP/JS reserved keywords (tam eşleşme)
        $reservedWords = ['function', 'class', 'return', 'var', 'const', 'let', 'if', 'else',
                          'for', 'while', 'foreach', 'echo', 'print', 'new', 'this', 'self',
                          'true', 'false', 'null', 'undefined', 'array', 'object'];
        if (in_array(strtolower($text), $reservedWords)) {
            return false;
        }

        // ÇEVİR - Açıkça metin içerik:

        // Birden fazla kelime (boşluk içeriyor) - büyük ihtimalle metin
        if (strpos($text, ' ') !== false) {
            return true;
        }

        // Noktalama işareti var - cümle olabilir
        if (preg_match('/[.!?,;:]/', $text)) {
            return true;
        }

        // Büyük harfle başlayan 2+ karakter - başlık veya kelime olabilir
        if (preg_match('/^[A-Z][a-z]/', $text)) {
            return true;
        }

        // Küçük harflerden oluşan 3+ karakter kelime - muhtemelen metin
        if (preg_match('/^[a-z]{3,}$/i', $text) && strlen($text) >= 3) {
            return true;
        }

        // Türkçe karakterler içeriyor - kesinlikle çevrilmeli
        if (preg_match('/[çğıöşüÇĞİÖŞÜ]/', $text)) {
            return true;
        }

        // Tek kelime ama çok uzun (10+ karakter) - büyük ihtimalle kod değil
        if (strlen($text) >= 10) {
            return true;
        }

        // Son kontrol: Tamamen küçük harf + tire/alt çizgi = CSS class olabilir
        if (preg_match('/^[a-z][a-z0-9_-]*$/', $text)) {
            return false;
        }

        // SADECE BÜYÜK HARF = constant olabilir
        if (preg_match('/^[A-Z_0-9]+$/', $text)) {
            return false;
        }

        // Varsayılan: ÇEVİR (daha liberal yaklaşım)
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
