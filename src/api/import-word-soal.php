<?php
// Enable error logging for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/import-word-error.log');

session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a guru
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'guru') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../logic/soal-logic.php';
require_once __DIR__ . '/../logic/koneksi.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Text;

class WordImporter {
    private $db;
    private $soalLogic;
    private $guru_id;

    public function __construct() {
        try {
            error_log("WordImporter constructor - Getting DB connection");
            $this->db = getConnection();
            error_log("WordImporter constructor - DB connection successful");
            
            $this->soalLogic = new SoalLogic();
            error_log("WordImporter constructor - SoalLogic initialized");
            
            $this->guru_id = $_SESSION['user']['id'];
            error_log("WordImporter constructor - Guru ID: " . $this->guru_id);
        } catch (Exception $e) {
            error_log("WordImporter constructor error: " . $e->getMessage());
            throw $e;
        }
    }

    public function importFromWord($filePath, $ujian_id) {
        try {
            // Validate ujian belongs to guru
            if (!$this->validateUjian($ujian_id)) {
                throw new Exception('Ujian tidak ditemukan atau bukan milik Anda');
            }

            // Check if required extensions are available
            if (!extension_loaded('zip')) {
                throw new Exception('Fitur import Word memerlukan PHP ZIP extension. Silakan gunakan format teks biasa atau hubungi administrator untuk mengaktifkan extension ZIP dan GD di XAMPP.');
            }

            // Load Word document
            error_log("Loading Word document: " . $filePath);
            $phpWord = IOFactory::load($filePath);
            error_log("Word document loaded successfully");
            
            $sections = $phpWord->getSections();
            error_log("Found " . count($sections) . " sections in document");
            
            if (empty($sections)) {
                throw new Exception('Dokumen Word tidak memiliki konten yang dapat dibaca.');
            }
            $questions = [];
            $currentQuestion = null;
            $questionCounter = 0;

            foreach ($sections as $sectionIndex => $section) {
                try {
                    $elements = $section->getElements();
                    error_log("Section " . ($sectionIndex + 1) . " has " . count($elements) . " elements");
                    
                    foreach ($elements as $elementIndex => $element) {
                        try {
                            $text = $this->extractTextFromElement($element);
                            $trimmedText = trim($text);
                            error_log("Element " . $elementIndex . " text: '" . $trimmedText . "'");
                            
                            if (empty($trimmedText)) continue;
                            
                            // Handle table format parsing (common in Word templates)
                            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                                $tableResult = $this->parseTableStructure($element);
                                if (!empty($tableResult)) {
                                    $parsedQuestion = json_decode($tableResult, true);
                                    if ($parsedQuestion && !empty($parsedQuestion['question'])) {
                                        $questions[] = $parsedQuestion;
                                        error_log("Parsed table question " . count($questions) . ": " . $parsedQuestion['question']);
                                        continue;
                                    }
                                }
                                error_log("Failed to parse table structure");
                                continue;
                            }
                    
                    // Check if this is a new question (starts with number) - make more flexible
                    if (preg_match('/^(\d+)[\.\)]\s*(.+)/s', $trimmedText, $matches)) {
                        error_log("Found question pattern: " . $trimmedText);
                        // Save previous question if exists
                        if ($currentQuestion !== null && !empty(trim($currentQuestion['question']))) {
                            $questions[] = $currentQuestion;
                        }
                        
                        // Start new question
                        $questionCounter++;
                        $currentQuestion = [
                            'number' => $questionCounter,
                            'question' => trim($matches[2]),
                            'type' => 'pilihan_ganda', // Default type
                            'options' => [],
                            'answer' => '',
                            'points' => 10
                        ];
                    }
                    // Check if this is an option (A., B., C., D., E.) - make more flexible
                    else if (preg_match('/^([A-E])[\.\)]\s*(.+)/s', $trimmedText, $matches) && $currentQuestion !== null) {
                        error_log("Found option: " . $trimmedText);
                        $optionLetter = $matches[1];
                        $optionText = trim($matches[2]);
                        
                        $currentQuestion['options'][] = [
                            'letter' => $optionLetter,
                            'text' => $optionText
                        ];
                    }
                    // Check if this is answer key (Jawaban: A or Kunci: A) - make more flexible
                    else if (preg_match('/^(Jawaban|Kunci|Answer|Ans|Kunci\s*Jawaban)\s*:?\s*([A-E])/i', $trimmedText, $matches) && $currentQuestion !== null) {
                        error_log("Found answer key: " . $trimmedText);
                        $currentQuestion['answer'] = strtoupper($matches[2]);
                    }
                    // Check if this is essay/short answer question
                    else if ($currentQuestion !== null && empty($currentQuestion['options']) && !preg_match('/^[A-E][\.\)]/i', $trimmedText)) {
                        // If no options found, treat as essay
                        if (strlen($trimmedText) > 10) { // Reasonable answer length
                            $currentQuestion['type'] = 'essay';
                            $currentQuestion['answer'] = $trimmedText;
                            error_log("Set as essay answer: " . $trimmedText);
                        }
                    }
                        } catch (Exception $e) {
                            error_log("Error processing element " . $elementIndex . ": " . $e->getMessage());
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error processing section " . $sectionIndex . ": " . $e->getMessage());
                }
            }
            
            // Add last question
            if ($currentQuestion !== null && !empty(trim($currentQuestion['question']))) {
                $questions[] = $currentQuestion;
            }

            // Debug: log all found questions
            error_log("Total questions found: " . count($questions));
            foreach ($questions as $i => $q) {
                error_log("Question " . ($i + 1) . ": " . substr($q['question'], 0, 50) . "...");
                error_log("Type: " . $q['type'] . ", Options: " . count($q['options']) . ", Answer: " . $q['answer']);
            }

            // Validate we have questions
            if (empty($questions)) {
                error_log("No questions found - this could be due to format mismatch");
                throw new Exception('Tidak ada soal yang ditemukan dalam file. Pastikan format sesuai template. Format yang benar: "1. Pertanyaan?" diikuti "A. Pilihan" dan "Jawaban: A"');
            }

            // Import questions to database
            $importedCount = $this->saveQuestionsToDatabase($questions, $ujian_id);
            
            return [
                'success' => true,
                'imported_count' => $importedCount,
                'message' => "Berhasil mengimport {$importedCount} soal"
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    private function extractTextFromElement($element) {
        $text = '';
        
        try {
            // Handle different element types
            $elementClass = get_class($element);
            error_log("Processing element type: " . $elementClass);
            
            // Handle Table elements specifically - preserve table structure
            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                return $this->parseTableStructure($element);
            }
            // Handle other elements with getElements method
            else if (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $childElement) {
                    if ($childElement instanceof TextRun) {
                        foreach ($childElement->getElements() as $textElement) {
                            if ($textElement instanceof Text) {
                                $text .= $textElement->getText() . " ";
                            }
                        }
                    } else if ($childElement instanceof Text) {
                        $text .= $childElement->getText() . " ";
                    } else if (method_exists($childElement, 'getText')) {
                        $text .= $childElement->getText() . " ";
                    } else {
                        // Recursive call for nested elements
                        $nestedText = $this->extractTextFromElement($childElement);
                        if (!empty($nestedText)) {
                            $text .= $nestedText . " ";
                        }
                    }
                }
            } 
            // Handle elements with direct getText method
            else if (method_exists($element, 'getText')) {
                $text = $element->getText();
            }
            
            // Clean up text
            $text = preg_replace('/\s+/', ' ', $text); // Replace multiple spaces with single space
            $text = trim($text);
            
        } catch (Exception $e) {
            error_log("Error extracting text: " . $e->getMessage());
        }
        
        return $text;
    }

    private function parseTableStructure($table) {
        try {
            $questionData = [];
            error_log("Parsing table structure with rows: " . count($table->getRows()));
            
            foreach ($table->getRows() as $rowIndex => $row) {
                $cells = $row->getCells();
                if (count($cells) >= 2) {
                    // Get label (column 1) and content (column 2)
                    $label = '';
                    $content = '';
                    
                    // Extract text from first cell (label)
                    foreach ($cells[0]->getElements() as $element) {
                        $label .= $this->getSimpleText($element) . ' ';
                    }
                    
                    // Extract text from second cell (content)  
                    foreach ($cells[1]->getElements() as $element) {
                        $content .= $this->getSimpleText($element) . ' ';
                    }
                    
                    $label = trim($label);
                    $content = trim($content);
                    
                    error_log("Row $rowIndex - Label: '$label', Content: '$content'");
                    
                    // Store the data
                    $questionData[$label] = $content;
                }
            }
            
            // Now parse the collected data into question format
            return $this->parseCollectedTableData($questionData);
            
        } catch (Exception $e) {
            error_log("Error parsing table structure: " . $e->getMessage());
            return '';
        }
    }
    
    private function getSimpleText($element) {
        if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            return $element->getText();
        } else if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            $text = '';
            foreach ($element->getElements() as $textElement) {
                if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text .= $textElement->getText();
                }
            }
            return $text;
        } else if (method_exists($element, 'getText')) {
            return $element->getText();
        }
        return '';
    }
    
    private function parseCollectedTableData($data) {
        // Expected keys for multiple choice: '1', 'a', 'b', 'c', 'd', 'Jawaban benar'
        // Expected keys for essay: '1', 'jawaban benar' (only these two)
        $questionNumber = null;
        $questionText = '';
        $options = [];
        $answer = '';
        
        foreach ($data as $label => $content) {
            $cleanLabel = trim($label);
            $cleanContent = $this->cleanTemplateText($content);
            
            // Check if it's a question number
            if (preg_match('/^(\d+)$/', $cleanLabel, $matches)) {
                $questionNumber = $matches[1];
                $questionText = $cleanContent;
                error_log("Found question $questionNumber: $questionText");
            }
            // Check if it's an option (a, b, c, d, e)
            else if (preg_match('/^([a-eA-E])$/', $cleanLabel, $matches)) {
                $optionLetter = strtoupper($matches[1]);
                $options[] = [
                    'letter' => $optionLetter,
                    'text' => $cleanContent
                ];
                error_log("Found option $optionLetter: $cleanContent");
            }
            // Check if it's answer key
            else if (preg_match('/jawaban\s*benar/i', $cleanLabel)) {
                $answer = trim($cleanContent);
                error_log("Found answer: $answer");
            }
        }
        
        // Determine question type based on available data
        if (!empty($questionText)) {
            // If we have options (a, b, c, d), it's multiple choice
            if (count($options) >= 2) {
                // For multiple choice, answer should be a letter
                $mcAnswer = strtoupper($answer);
                if (preg_match('/^[A-E]$/', $mcAnswer)) {
                    error_log("Detected as multiple choice question");
                    return json_encode([
                        'number' => $questionNumber,
                        'question' => $questionText,
                        'type' => 'pilihan_ganda',
                        'options' => $options,
                        'answer' => $mcAnswer,
                        'points' => 10
                    ]);
                }
            }
            // If no options but has answer, it's essay
            else if (!empty($answer) && count($options) == 0) {
                error_log("Detected as essay question");
                return json_encode([
                    'number' => $questionNumber,
                    'question' => $questionText,
                    'type' => 'essay',
                    'options' => [],
                    'answer' => $answer,
                    'points' => 10
                ]);
            }
        }
        
        error_log("Could not determine question type - Question: '$questionText', Options: " . count($options) . ", Answer: '$answer'");
        return '';
    }

    private function parseTableQuestion($tableText, $expectedNumber = null) {
        try {
            error_log("Parsing table text: " . $tableText);
            error_log("Expected question number: " . $expectedNumber);
            
            // Pattern untuk format table: "1 (question text) a (option a) b (option b) c (option c) d (option d) Jawaban benar (contoh a) a"
            // Or simpler: "1. Question? A. Option A B. Option B C. Option C D. Option D Jawaban: A"
            
            $questionNumber = $expectedNumber;
            $options = [];
            $answer = '';
            
            // Split by common delimiters and analyze
            $parts = preg_split('/\s+/', $tableText);
            $currentSection = '';
            $questionText = '';
            $currentOption = '';
            $currentOptionText = '';
            
            for ($i = 0; $i < count($parts); $i++) {
                $part = $parts[$i];
                
                // Check if this is question number
                if (preg_match('/^(\d+)[\.\)]?$/', $part, $matches)) {
                    $questionNumber = $matches[1];
                    $currentSection = 'question';
                    error_log("Found question number: " . $questionNumber);
                    continue;
                }
                
                // Check if this is an option letter - but only if we have a complete question
                if (preg_match('/^([A-E])[\.\)]?$/', strtoupper($part)) && $currentSection !== 'answer' && !empty(trim($questionText))) {
                    // Save previous option if exists
                    if (!empty($currentOption) && !empty(trim($currentOptionText))) {
                        $options[] = [
                            'letter' => $currentOption,
                            'text' => trim($currentOptionText)
                        ];
                        error_log("Added option " . $currentOption . ": " . trim($currentOptionText));
                    }
                    
                    $currentOption = strtoupper($part[0]);
                    $currentOptionText = '';
                    $currentSection = 'option';
                    error_log("Found option letter: " . $currentOption);
                    continue;
                }
                
                // Check if this indicates answer section
                if (preg_match('/^(Jawaban|Kunci|Answer)/i', $part)) {
                    // Save last option
                    if (!empty($currentOption) && !empty(trim($currentOptionText))) {
                        $options[] = [
                            'letter' => $currentOption,
                            'text' => trim($currentOptionText)
                        ];
                        error_log("Added last option " . $currentOption . ": " . trim($currentOptionText));
                    }
                    $currentSection = 'answer';
                    error_log("Found answer section marker");
                    continue;
                }
                
                // Check if this is the answer letter
                if ($currentSection === 'answer' && preg_match('/^([A-E])$/', strtoupper($part))) {
                    $answer = strtoupper($part);
                    error_log("Found answer: " . $answer);
                    break;
                }
                
                // Accumulate text based on current section
                if ($currentSection === 'question') {
                    // Skip parentheses and common template text
                    if (!preg_match('/^\(.*\)$/', $part) && 
                        !preg_match('/^(ini|adalah|tempat|pertanyaan)$/i', $part)) {
                        $questionText .= $part . ' ';
                    }
                } else if ($currentSection === 'option' && !empty($currentOption)) {
                    // Skip parentheses and common template text
                    if (!preg_match('/^\(.*\)$/', $part) && 
                        !preg_match('/^(ini|adalah|tempat|jawaban)$/i', $part)) {
                        $currentOptionText .= $part . ' ';
                    }
                }
            }
            
            // Save last option if exists
            if (!empty($currentOption) && !empty(trim($currentOptionText))) {
                $options[] = [
                    'letter' => $currentOption,
                    'text' => trim($currentOptionText)
                ];
                error_log("Added final option " . $currentOption . ": " . trim($currentOptionText));
            }
            
            // Clean up question text
            $questionText = trim($questionText);
            
            // Validate we have minimum required data
            if (!empty($questionText) && count($options) >= 2) {
                $result = [
                    'number' => $questionNumber ?? $expectedNumber ?? 0,
                    'question' => $questionText,
                    'type' => 'pilihan_ganda',
                    'options' => $options,
                    'answer' => $answer,
                    'points' => 10
                ];
                error_log("Successfully parsed question: " . json_encode($result));
                return $result;
            } else {
                error_log("Validation failed - Question: '" . $questionText . "', Options: " . count($options));
                
                // Try alternative parsing approach
                return $this->parseTableQuestionAlternative($tableText, $expectedNumber);
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Error parsing table question: " . $e->getMessage());
            return null;
        }
    }

    private function parseTableQuestionAlternative($tableText, $expectedNumber = null) {
        try {
            error_log("Trying alternative parsing approach");
            
            // More aggressive pattern matching for table format
            // Look for patterns like: "2 (question) a (option) b (option) c (option) d (option) Jawaban benar (answer) X"
            
            $questionText = '';
            $options = [];
            $answer = '';
            
            // Use more specific regex patterns to prevent question text cutoff
            // Pattern 1: Try to match the full structure more carefully
            if (preg_match('/(\d+)\s+(.+?)\s+a\s+(.+?)\s+b\s+(.+?)\s+c\s+(.+?)\s+d\s+(.+?)\s+Jawaban\s+benar.+?([A-E])\s*$/i', $tableText, $matches)) {
                $questionNumber = $matches[1];
                $questionText = $this->cleanTemplateText($matches[2]);
                
                // Ensure question text is reasonable length and doesn't look like option text
                if (strlen($questionText) < 5 || preg_match('/^[a-z]{20,}$/', $questionText)) {
                    error_log("Question text seems invalid: " . $questionText);
                    return null;
                }
                
                $options = [
                    ['letter' => 'A', 'text' => $this->cleanTemplateText($matches[3])],
                    ['letter' => 'B', 'text' => $this->cleanTemplateText($matches[4])],
                    ['letter' => 'C', 'text' => $this->cleanTemplateText($matches[5])],
                    ['letter' => 'D', 'text' => $this->cleanTemplateText($matches[6])]
                ];
                
                $answer = strtoupper($matches[7]);
                
                error_log("Alternative parsing success - Q: $questionText, Answer: $answer");
                
                if (!empty($questionText) && !empty($answer)) {
                    return [
                        'number' => $questionNumber ?? $expectedNumber ?? 0,
                        'question' => $questionText,
                        'type' => 'pilihan_ganda',
                        'options' => $options,
                        'answer' => $answer,
                        'points' => 10
                    ];
                }
            }
            
            // Try another approach - split by option letters more carefully
            if (preg_match('/(\d+)\s+(.+?)\s+([aA])\s+(.+?)\s+([bB])\s+(.+?)\s+([cC])\s+(.+?)\s+([dD])\s+(.+?)\s+.*?([A-E])\s*$/', $tableText, $matches)) {
                error_log("Trying split-based parsing");
                
                $questionNumber = $matches[1];
                $questionText = $this->cleanTemplateText($matches[2]);
                
                // Validation for question text
                if (strlen($questionText) > 5 && !preg_match('/^[a-z]{15,}/', strtolower($questionText))) {
                    $options = [
                        ['letter' => 'A', 'text' => $this->cleanTemplateText($matches[4])],
                        ['letter' => 'B', 'text' => $this->cleanTemplateText($matches[6])],
                        ['letter' => 'C', 'text' => $this->cleanTemplateText($matches[8])],
                        ['letter' => 'D', 'text' => $this->cleanTemplateText($matches[10])]
                    ];
                    
                    $answer = strtoupper($matches[11]);
                    
                    error_log("Split parsing success - Q: $questionText, Answer: $answer");
                    
                    return [
                        'number' => $questionNumber ?? $expectedNumber ?? 0,
                        'question' => $questionText,
                        'type' => 'pilihan_ganda',
                        'options' => $options,
                        'answer' => $answer,
                        'points' => 10
                    ];
                }
            }
            
            error_log("Alternative parsing also failed");
            return null;
            
        } catch (Exception $e) {
            error_log("Error in alternative parsing: " . $e->getMessage());
            return null;
        }
    }
    
    private function cleanTemplateText($text) {
        // Remove common template placeholders and parentheses content
        $text = preg_replace('/\(.*?(adalah|tempat|ini|contoh).*?\)/i', '', $text);
        $text = preg_replace('/\([^)]*\)/', '', $text); // Remove any remaining parentheses
        
        // Remove common template words
        $text = preg_replace('/\b(ini|adalah|tempat|pertanyaan|jawaban)\b/i', '', $text);
        
        // Clean up multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    private function validateUjian($ujian_id) {
        $stmt = $this->db->prepare("SELECT id FROM ujian WHERE id = ? AND guru_id = ?");
        $stmt->bind_param("ii", $ujian_id, $this->guru_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    private function saveQuestionsToDatabase($questions, $ujian_id) {
        $importedCount = 0;
        
        // Get current max question number for this ujian
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(nomorSoal), 0) as max_number FROM soal WHERE ujian_id = ?");
        $stmt->bind_param("i", $ujian_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $currentMaxNumber = $result['max_number'];
        
        foreach ($questions as $question) {
            $currentMaxNumber++;
            
            // Insert question
            $stmt = $this->db->prepare("
                INSERT INTO soal (ujian_id, nomorSoal, pertanyaan, tipeSoal, kunciJawaban, poin) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("iisssi", 
                $ujian_id, 
                $currentMaxNumber, 
                $question['question'],
                $question['type'],
                $question['answer'],
                $question['points']
            );
            
            if ($stmt->execute()) {
                $soal_id = $this->db->insert_id;
                
                // Insert options for multiple choice questions
                if ($question['type'] === 'pilihan_ganda' && !empty($question['options'])) {
                    foreach ($question['options'] as $option) {
                        $isCorrect = (strtoupper($option['letter']) === strtoupper($question['answer'])) ? 1 : 0;
                        
                        $optStmt = $this->db->prepare("
                            INSERT INTO pilihan_jawaban (soal_id, opsi, teksJawaban, benar) 
                            VALUES (?, ?, ?, ?)
                        ");
                        
                        $optStmt->bind_param("issi", 
                            $soal_id,
                            $option['letter'],
                            $option['text'],
                            $isCorrect
                        );
                        
                        $optStmt->execute();
                    }
                }
                // For essay questions, no options needed - answer is stored in kunciJawaban field
                else if ($question['type'] === 'essay') {
                    error_log("Essay question saved - Answer stored in kunciJawaban field");
                }
                
                $importedCount++;
            }
        }
        
        return $importedCount;
    }
}

// Main execution
try {
    // Log request info for debugging
    error_log("Import request received - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Files: " . print_r($_FILES, true));
    error_log("Post: " . print_r($_POST, true));
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method tidak diizinkan');
    }

    if (!isset($_FILES['word_file']) || !isset($_POST['ujian_id'])) {
        throw new Exception('File atau ujian ID tidak ditemukan');
    }

    $file = $_FILES['word_file'];
    $ujian_id = (int)$_POST['ujian_id'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error saat upload file');
    }

    if (!in_array($file['type'], ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
        // Also check file extension
        $fileName = $file['name'];
        if (!preg_match('/\.docx$/i', $fileName)) {
            throw new Exception('File harus berformat .docx');
        }
    }

    // Check file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('File terlalu besar. Maksimal 10MB');
    }

    // Create upload directory if not exists
    $uploadDir = __DIR__ . '/../../uploads/temp/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Move uploaded file to temp location
    $tempFileName = uniqid('word_import_') . '.docx';
    $tempFilePath = $uploadDir . $tempFileName;
    
    if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
        throw new Exception('Gagal memindahkan file upload');
    }

    // Process import
    $importer = new WordImporter();
    $result = $importer->importFromWord($tempFilePath, $ujian_id);

    // Clean up temp file
    if (file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }

    echo json_encode($result);

} catch (Exception $e) {
    // Log the error
    error_log("Import error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clean up temp file if exists
    if (isset($tempFilePath) && file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    // Log PHP errors
    error_log("PHP Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clean up temp file if exists
    if (isset($tempFilePath) && file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>