<?php
// Prevent any output before headers
ob_start();

// Set PHP limits for file processing
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 30);
ini_set('max_input_time', 30);

// Include Composer autoloader for smalot/pdfparser
require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di output JSON
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/parse_document_error.log');

// Import PDF Parser library
use Smalot\PdfParser\Parser;

function sendJsonResponse($success, $data = null, $error = null, $debug = null) {
    // Clear any output buffer to ensure clean JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $response = [
        'success' => $success,
        'data' => $data,
        'error' => $error
    ];
    
    // Clean data for JSON encoding if it contains text content
    if ($data && isset($data['content'])) {
        // Ensure the content is clean UTF-8
        $cleanContent = mb_convert_encoding($data['content'], 'UTF-8', 'UTF-8');
        $cleanContent = @iconv('UTF-8', 'UTF-8//IGNORE', $cleanContent);
        if ($cleanContent !== false) {
            $data['content'] = $cleanContent;
            $response['data'] = $data;
        }
    }
    
    // Add debug info in development
    if ($debug && isset($_GET['debug'])) {
        $response['debug'] = $debug;
    }
    
    // Create JSON response
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
    
    // Check for JSON encoding errors
    if ($jsonResponse === false) {
        $jsonError = json_last_error_msg();
        error_log("JSON encoding error: " . $jsonError);
        
        // Try with a simplified response
        $fallbackResponse = [
            'success' => $success,
            'error' => $error ? $error : 'Dokumen berhasil diproses tetapi mengandung karakter khusus'
        ];
        
        // If there was data, provide a simplified message
        if ($data && isset($data['content'])) {
            $fallbackResponse['data'] = [
                'content' => 'Dokumen telah diproses, namun mengandung karakter khusus yang tidak dapat ditampilkan. Total karakter: ' . strlen($data['content'])
            ];
        }
        
        $jsonResponse = json_encode($fallbackResponse);
        
        // If even fallback fails, return basic error
        if ($jsonResponse === false) {
            $jsonResponse = '{"success":false,"error":"Character encoding error"}';
        }
    }
    
    // Log the response (truncated for log)
    $logResponse = strlen($jsonResponse) > 500 ? substr($jsonResponse, 0, 500) . '...' : $jsonResponse;
    error_log("Document Parser Response: " . $logResponse);
    
    // Additional debug info
    error_log("Response length: " . strlen($jsonResponse));
    error_log("Headers already sent: " . (headers_sent() ? 'YES' : 'NO'));
    
    // Set proper headers
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Length: ' . strlen($jsonResponse));
    header('Cache-Control: no-cache, must-revalidate');
    
    // Output and flush
    echo $jsonResponse;
    
    // Ensure all output is sent
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        // Check if there's an output buffer before trying to flush it
        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();
    }
    
    exit;
}

try {
    error_log("Document parser started. METHOD: " . $_SERVER['REQUEST_METHOD']);
    error_log("Document parser FILES: " . json_encode($_FILES));
    error_log("Document parser POST: " . json_encode($_POST));
    
    // Handle non-POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
        sendJsonResponse(false, null, 'Only POST method is allowed');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'No file uploaded';
        if (isset($_FILES['document']['error'])) {
            $errorMsg .= '. Upload error code: ' . $_FILES['document']['error'];
        }
        error_log("Upload error: " . $errorMsg);
        sendJsonResponse(false, null, $errorMsg);
    }

    $file = $_FILES['document'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    error_log("Processing file: $fileName (Size: $fileSize bytes, Extension: $fileExtension)");

    // Check file size (max 10MB, 5MB for PDF)
    $maxSize = ($fileExtension === 'pdf') ? 5 * 1024 * 1024 : 10 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        $maxSizeText = ($fileExtension === 'pdf') ? '5MB' : '10MB';
        sendJsonResponse(false, null, "File terlalu besar. Maksimal {$maxSizeText} untuk file {$fileExtension}.");
    }

    // Allowed file types
    $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'md'];
    if (!in_array($fileExtension, $allowedTypes)) {
        sendJsonResponse(false, null, 'Format file tidak didukung. Gunakan PDF, DOC, DOCX, TXT, RTF, atau MD.');
    }

    $content = '';
    error_log("Starting content extraction for: $fileExtension");

    switch ($fileExtension) {
        case 'txt':
        case 'rtf':
        case 'md':
            error_log("Reading text file...");
            $content = file_get_contents($fileTmpName);
            if ($content === false) {
                throw new Exception('Gagal membaca file teks');
            }
            error_log("Text file read successfully, length: " . strlen($content));
            break;

        case 'pdf':
            error_log("Parsing PDF file...");
            $content = parsePdf($fileTmpName);
            error_log("PDF parsed, length: " . strlen($content));
            break;

        case 'doc':
        case 'docx':
            error_log("Parsing Word document...");
            $content = parseWord($fileTmpName, $fileExtension);
            error_log("Word document parsed, length: " . strlen($content));
            break;

        default:
            throw new Exception('Format file tidak didukung');
    }

    // Clean and limit content
    $content = cleanText($content);
    error_log("Content cleaned, final length: " . strlen($content));
    
    // Limit content to reasonable size (max 50000 characters)
    if (strlen($content) > 50000) {
        $content = substr($content, 0, 50000) . "\n\n[Dokumen dipotong karena terlalu panjang...]";
        error_log("Content truncated to 50000 characters");
    }

    if (empty(trim($content))) {
        $content = 'Dokumen kosong atau tidak dapat dibaca. Pastikan file memiliki konten yang dapat dibaca.';
        error_log("Warning: Document appears to be empty or unreadable");
    }

    error_log("Sending successful response with content length: " . strlen($content));
    sendJsonResponse(true, ['content' => $content], null);

} catch (Exception $e) {
    error_log("Document parsing error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendJsonResponse(false, null, $e->getMessage());
}

function parsePdf($filePath) {
    error_log("Starting PDF parsing with smalot/pdfparser for: $filePath");
    
    // Check file size first (increased limit since smalot/pdfparser is more robust)
    $fileSize = filesize($filePath);
    error_log("PDF file size: $fileSize bytes");
    
    if ($fileSize > 5 * 1024 * 1024) { // 5MB limit for PDF processing
        error_log("PDF file too large for parsing: " . $fileSize . " bytes");
        return 'PDF terlalu besar untuk diproses (max 5MB). Silakan gunakan PDF yang lebih kecil atau format lain.';
    }
    
    // Log initial memory usage
    error_log("Memory usage before PDF parsing: " . memory_get_usage(true) . " bytes");
    
    try {
        // Initialize the PDF parser
        $parser = new Parser();
        error_log("PDF parser initialized successfully");
        
        // Parse the PDF file
        $pdf = $parser->parseFile($filePath);
        error_log("PDF file parsed successfully");
        
        // Extract text from all pages
        $text = $pdf->getText();
        error_log("Text extracted, raw length: " . strlen($text));
        
        // Clean and process the text
        if (!empty($text)) {
            $text = cleanText($text);
            error_log("Text cleaned, final length: " . strlen($text));
            
            if (strlen($text) > 0) {
                // Check if the extracted text is mostly readable
                $readableChars = preg_match_all('/[a-zA-Z0-9\s.,!?;:()"-]/', $text);
                $totalChars = strlen($text);
                
                if ($totalChars > 0) {
                    $readableRatio = $readableChars / $totalChars;
                    error_log("Text readability ratio: " . $readableRatio . " (readable: $readableChars, total: $totalChars)");
                    
                    // If less than 30% readable characters, consider it problematic (lowered threshold for better support)
                    if ($readableRatio < 0.3) {
                        error_log("Extracted text appears to be mostly unreadable");
                        return 'PDF berisi data yang sulit dibaca. Silakan coba dengan PDF yang berbeda atau gunakan format DOC/TXT.';
                    }
                    
                    return $text;
                }
            }
        }
        
        // Try to extract metadata if text extraction fails
        $details = $pdf->getDetails();
        error_log("Trying to extract metadata: " . json_encode($details));
        
        if (isset($details['Producer']) || isset($details['Creator'])) {
            return 'PDF berhasil dibuka tetapi tidak mengandung teks yang dapat diekstrak. ' .
                   'PDF ini mungkin berisi gambar atau format yang tidak didukung untuk ekstraksi teks. ' .
                   'Silakan gunakan PDF yang mengandung teks atau format lain seperti DOC/DOCX/TXT.';
        }
        
        return 'PDF tidak mengandung teks yang dapat diekstrak. Silakan gunakan format lain atau PDF yang mengandung teks.';
        
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
        error_log("PDF parsing error: " . $errorMessage);
        
        // Handle specific error types
        if (strpos($errorMessage, 'Secured') !== false || strpos($errorMessage, 'password') !== false) {
            return 'PDF terenkripsi atau dilindungi password. Silakan gunakan PDF yang tidak terenkripsi.';
        }
        
        if (strpos($errorMessage, 'Corrupted') !== false || strpos($errorMessage, 'Invalid') !== false) {
            return 'File PDF rusak atau tidak valid. Silakan gunakan file PDF yang valid.';
        }
        
        if (strpos($errorMessage, 'Memory') !== false) {
            return 'PDF terlalu kompleks untuk diproses. Silakan gunakan PDF yang lebih kecil atau format lain.';
        }
        
        error_log("Falling back to simple text extraction");
        
        // Fallback: Try simple file reading for text-based PDFs
        try {
            $content = file_get_contents($filePath);
            if ($content !== false) {
                // Try to find readable text using basic regex
                if (preg_match_all('/[A-Za-z0-9\s\.,;:!?]{10,}/', $content, $matches)) {
                    $fallbackText = implode(' ', $matches[0]);
                    $fallbackText = preg_replace('/\s+/', ' ', trim($fallbackText));
                    
                    if (strlen($fallbackText) > 50) {
                        error_log("Fallback text extraction successful: " . strlen($fallbackText) . " chars");
                        return cleanText($fallbackText);
                    }
                }
            }
        } catch (\Exception $fallbackError) {
            error_log("Fallback parsing also failed: " . $fallbackError->getMessage());
        }
        
        return 'Tidak dapat memproses PDF ini. Error: ' . $errorMessage . '. ' .
               'Silakan coba dengan PDF yang berbeda atau gunakan format DOC/DOCX/TXT.';
    }
}

function parseWord($filePath, $extension) {
    if ($extension === 'docx') {
        return parseDocx($filePath);
    } else {
        return parseDoc($filePath);
    }
}

function parseDocx($filePath) {
    try {
        // DOCX is a ZIP file, extract the XML
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== TRUE) {
            throw new Exception('Gagal membuka file DOCX');
        }

        // Get the main document content
        $content = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($content === false) {
            throw new Exception('Gagal membaca konten DOCX');
        }

        // Parse XML and extract text
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            throw new Exception('Gagal parsing XML DOCX');
        }

        // Register namespace
        $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        
        // Extract all text nodes
        $textNodes = $xml->xpath('//w:t');
        $text = '';
        
        if ($textNodes) {
            foreach ($textNodes as $textNode) {
                $text .= (string)$textNode . ' ';
            }
        }

        return $text;

    } catch (Exception $e) {
        return 'Gagal membaca file DOCX: ' . $e->getMessage();
    }
}

function parseDoc($filePath) {
    // DOC format is more complex, try basic approach
    $content = file_get_contents($filePath);
    
    if ($content === false) {
        return 'Gagal membaca file DOC';
    }
    
    // Very basic text extraction for DOC files
    // This is not reliable for all DOC files
    $text = '';
    
    // Try to find readable text (very basic approach)
    if (preg_match_all('/[\x20-\x7E\x0A\x0D]{4,}/', $content, $matches)) {
        foreach ($matches[0] as $match) {
            if (strlen($match) > 10) { // Only longer strings
                $text .= $match . ' ';
            }
        }
    }
    
    if (empty(trim($text))) {
        return 'File DOC tidak dapat dibaca. Silakan gunakan format DOCX atau TXT.';
    }
    
    return $text;
}

function cleanText($text) {
    // Fix malformed UTF-8 characters first
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    
    // Remove invalid UTF-8 sequences using iconv (replacing deprecated FILTER_SANITIZE_STRING)
    $convertedText = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
    if ($convertedText !== false) {
        $text = $convertedText;
    }
    
    // Additional cleaning for non-printable characters
    $text = preg_replace('/[^\P{C}\t\r\n]/u', '', $text);
    
    // Remove excessive whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Remove control characters except newlines and tabs
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
    
    // Additional cleanup for PDF artifacts
    $text = str_replace(['�', ''], ' ', $text);
    
    // Remove multiple spaces again after cleanup
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Trim
    $text = trim($text);
    
    // Final UTF-8 validation
    if (!mb_check_encoding($text, 'UTF-8')) {
        error_log("Warning: Text still contains invalid UTF-8 after cleaning");
        // Force to ASCII if UTF-8 is still invalid
        $text = mb_convert_encoding($text, 'UTF-8', 'ASCII');
    }
    
    return $text;
}

?>