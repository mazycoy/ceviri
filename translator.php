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
        // HTML içindeki metin içeriklerini bul ve çevir
        $patterns = [
            // HTML etiketleri arasındaki metinler
            '/>([^<>]+)</i',
            // Title, alt, placeholder gibi attributelar
            '/(title|alt|placeholder|value|aria-label)\s*=\s*["\']([^"\']+)["\']/i',
            // PHP echo ve print içindeki string'ler
            '/echo\s+["\']([^"\']+)["\']/i',
            '/print\s+["\']([^"\']+)["\']/i',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, function($matches) use ($targetLang) {
                $text = $matches[count($matches) - 1];

                // Boşluk veya sadece sayı kontrolü
                if (trim($text) === '' || is_numeric($text) || strlen($text) < 2) {
                    return $matches[0];
                }

                $translated = $this->translator->translate($text, $targetLang);
                $this->stats['translated_strings']++;

                return str_replace($text, $translated, $matches[0]);
            }, $content);
        }

        return $content;
    }

    /**
     * JavaScript dosyalarındaki string'leri çevir
     */
    private function translateJavaScript($content, $targetLang) {
        // String literalleri bul
        $pattern = '/(["\'`])(?:(?=(\\\\?))\2.)*?\1/';

        $content = preg_replace_callback($pattern, function($matches) use ($targetLang) {
            $quote = $matches[1];
            $text = trim($matches[0], $quote);

            if (trim($text) === '' || is_numeric($text) || strlen($text) < 2) {
                return $matches[0];
            }

            // URL, kod gibi şeyleri atla
            if (preg_match('/^(http|https|\/|\.\/|#|var |function |return |const |let )/', $text)) {
                return $matches[0];
            }

            $translated = $this->translator->translate($text, $targetLang);
            $this->stats['translated_strings']++;

            return $quote . $translated . $quote;
        }, $content);

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
            } elseif (is_string($value) && strlen(trim($value)) > 1 && !is_numeric($value)) {
                // URL veya kod değilse çevir
                if (!preg_match('/^(http|https|\/|#|{|}|\[|\])/', $value)) {
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

            if (trim($text) === '' || strlen($text) < 2) {
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
        if (strlen(trim($content)) < 2) {
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
