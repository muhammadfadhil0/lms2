<?php
/**
 * Document Chunking System
 * File: src/classes/DocumentChunker.php
 */

class DocumentChunker {
    private $db;
    private $minChunkSize = 500;  // Minimum words per chunk
    private $maxChunkSize = 1000; // Maximum words per chunk
    private $overlapSize = 50;    // Words to overlap between chunks
    
    public function __construct($database = null) {
        $this->db = $database ?: $this->getDB();
    }
    
    /**
     * Get database connection
     */
    private function getDB() {
        $host = 'localhost';
        $dbname = 'lms';
        $username = 'root';
        $password = '';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Process uploaded document and create chunks
     */
    public function processDocument($filePath, $originalFilename, $userId = null) {
        try {
            // Insert document record
            $documentId = $this->createDocumentRecord($filePath, $originalFilename, $userId);
            
            // Extract text content
            $textContent = $this->extractTextFromFile($filePath, $originalFilename);
            
            if (empty($textContent)) {
                throw new Exception('Unable to extract text from document');
            }
            
            // Create chunks
            $chunks = $this->createChunks($textContent);
            
            // Save chunks to database
            $this->saveChunks($documentId, $chunks);
            
            // Update document status
            $this->updateDocumentStatus($documentId, 'completed', count($chunks), str_word_count($textContent));
            
            return [
                'success' => true,
                'document_id' => $documentId,
                'total_chunks' => count($chunks),
                'total_words' => str_word_count($textContent)
            ];
            
        } catch (Exception $e) {
            if (isset($documentId)) {
                $this->updateDocumentStatus($documentId, 'failed', 0, 0, $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Create document record in database
     */
    private function createDocumentRecord($filePath, $originalFilename, $userId) {
        $fileSize = filesize($filePath);
        $fileType = $this->getFileType($originalFilename);
        
        $stmt = $this->db->prepare("
            INSERT INTO documents (original_filename, file_type, file_size, file_path, user_id, processing_status) 
            VALUES (?, ?, ?, ?, ?, 'processing')
        ");
        
        $stmt->execute([$originalFilename, $fileType, $fileSize, $filePath, $userId]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Extract text content from various file types
     */
    private function extractTextFromFile($filePath, $filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'txt':
                return $this->extractFromTxt($filePath);
            case 'pdf':
                return $this->extractFromPdf($filePath);
            case 'doc':
            case 'docx':
                return $this->extractFromWord($filePath);
            default:
                throw new Exception("Unsupported file type: $extension");
        }
    }
    
    /**
     * Extract text from TXT file
     */
    private function extractFromTxt($filePath) {
        $content = file_get_contents($filePath);
        return mb_convert_encoding($content, 'UTF-8', 'auto');
    }
    
    /**
     * Extract text from PDF (using existing smalot/pdfparser)
     */
    private function extractFromPdf($filePath) {
        try {
            require_once __DIR__ . '/../../vendor/autoload.php';
            
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            // Clean up text
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            return $text;
        } catch (Exception $e) {
            throw new Exception("Failed to extract PDF text: " . $e->getMessage());
        }
    }
    
    /**
     * Extract text from Word document (basic implementation)
     */
    private function extractFromWord($filePath) {
        // For DOCX files, we can extract from the XML
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'docx') {
            return $this->extractFromDocx($filePath);
        } else {
            throw new Exception("DOC format not supported, please convert to DOCX");
        }
    }
    
    /**
     * Extract text from DOCX file
     */
    private function extractFromDocx($filePath) {
        try {
            $zip = new ZipArchive();
            
            if ($zip->open($filePath) !== TRUE) {
                throw new Exception("Cannot open DOCX file");
            }
            
            $xml_content = $zip->getFromName('word/document.xml');
            $zip->close();
            
            if ($xml_content === false) {
                throw new Exception("Cannot read document content");
            }
            
            // Extract text from XML
            $xml = simplexml_load_string($xml_content);
            $text = '';
            
            foreach ($xml->xpath('//w:t') as $textNode) {
                $text .= (string)$textNode . ' ';
            }
            
            return trim($text);
        } catch (Exception $e) {
            throw new Exception("Failed to extract DOCX text: " . $e->getMessage());
        }
    }
    
    /**
     * Create chunks from text content
     */
    private function createChunks($text) {
        // Split text into sentences
        $sentences = $this->splitIntoSentences($text);
        
        $chunks = [];
        $currentChunk = '';
        $currentWordCount = 0;
        
        foreach ($sentences as $sentence) {
            $sentenceWordCount = str_word_count($sentence);
            
            // If adding this sentence would exceed max chunk size, save current chunk
            if ($currentWordCount + $sentenceWordCount > $this->maxChunkSize && $currentWordCount >= $this->minChunkSize) {
                if (!empty($currentChunk)) {
                    $chunks[] = [
                        'content' => trim($currentChunk),
                        'word_count' => $currentWordCount,
                        'keywords' => $this->extractKeywords($currentChunk)
                    ];
                }
                
                // Start new chunk with overlap
                $currentChunk = $this->getOverlapText($currentChunk) . ' ' . $sentence;
                $currentWordCount = str_word_count($currentChunk);
            } else {
                // Add sentence to current chunk
                $currentChunk .= ' ' . $sentence;
                $currentWordCount += $sentenceWordCount;
            }
        }
        
        // Don't forget the last chunk
        if (!empty($currentChunk) && $currentWordCount >= 100) { // Minimum viable chunk
            $chunks[] = [
                'content' => trim($currentChunk),
                'word_count' => $currentWordCount,
                'keywords' => $this->extractKeywords($currentChunk)
            ];
        }
        
        return $chunks;
    }
    
    /**
     * Split text into sentences
     */
    private function splitIntoSentences($text) {
        // Simple sentence splitting (can be improved with NLP libraries)
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Clean up sentences
        return array_map('trim', $sentences);
    }
    
    /**
     * Get overlap text from previous chunk
     */
    private function getOverlapText($text) {
        $words = explode(' ', $text);
        
        if (count($words) <= $this->overlapSize) {
            return $text;
        }
        
        return implode(' ', array_slice($words, -$this->overlapSize));
    }
    
    /**
     * Extract keywords from text chunk
     */
    private function extractKeywords($text) {
        // Remove common words (stop words)
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from',
            'up', 'about', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'between',
            'among', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does',
            'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'shall', 'can', 'yang', 'dan',
            'atau', 'tetapi', 'di', 'pada', 'ke', 'untuk', 'dari', 'dengan', 'oleh', 'adalah', 'akan', 'telah'
        ];
        
        // Extract words
        $words = str_word_count(strtolower($text), 1);
        
        // Filter out stop words and short words
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) >= 3 && !in_array($word, $stopWords);
        });
        
        // Count frequency and get top keywords
        $wordCount = array_count_values($keywords);
        arsort($wordCount);
        
        // Return top 20 keywords
        return array_keys(array_slice($wordCount, 0, 20));
    }
    
    /**
     * Save chunks to database
     */
    private function saveChunks($documentId, $chunks) {
        $stmt = $this->db->prepare("
            INSERT INTO document_chunks (document_id, chunk_index, content, word_count, keywords) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($chunks as $index => $chunk) {
            $keywordsJson = json_encode($chunk['keywords']);
            $stmt->execute([
                $documentId, 
                $index, 
                $chunk['content'], 
                $chunk['word_count'], 
                $keywordsJson
            ]);
        }
    }
    
    /**
     * Update document processing status
     */
    private function updateDocumentStatus($documentId, $status, $totalChunks = 0, $totalWords = 0, $error = null) {
        $stmt = $this->db->prepare("
            UPDATE documents 
            SET processing_status = ?, total_chunks = ?, total_words = ?, processing_error = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$status, $totalChunks, $totalWords, $error, $documentId]);
    }
    
    /**
     * Get file type from filename
     */
    private function getFileType($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Find relevant chunks for a query
     */
    public function findRelevantChunks($documentId, $query, $limit = 3) {
        // Extract keywords from query
        $queryKeywords = $this->extractKeywords($query);
        
        if (empty($queryKeywords)) {
            // Fallback to first few chunks if no keywords
            return $this->getFirstChunks($documentId, $limit);
        }
        
        // Search for chunks with matching keywords
        $stmt = $this->db->prepare("
            SELECT id, chunk_index, content, word_count, keywords,
                   MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) as content_score,
                   MATCH(keywords) AGAINST(? IN NATURAL LANGUAGE MODE) as keyword_score
            FROM document_chunks 
            WHERE document_id = ? 
            ORDER BY (content_score + keyword_score * 2) DESC, chunk_index ASC
            LIMIT " . intval($limit) . "
        ");
        
        $queryString = implode(' ', $queryKeywords);
        $stmt->execute([$queryString, $queryString, $documentId]);
        
        $chunks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no matches found, return first chunks
        if (empty($chunks)) {
            return $this->getFirstChunks($documentId, $limit);
        }
        
        return $chunks;
    }
    
    /**
     * Get first chunks as fallback
     */
    private function getFirstChunks($documentId, $limit) {
        $stmt = $this->db->prepare("
            SELECT id, chunk_index, content, word_count, keywords
            FROM document_chunks 
            WHERE document_id = ? 
            ORDER BY chunk_index ASC
            LIMIT " . intval($limit) . "
        ");
        
        $stmt->execute([$documentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get document info
     */
    public function getDocumentInfo($documentId) {
        $stmt = $this->db->prepare("
            SELECT * FROM documents WHERE id = ?
        ");
        
        $stmt->execute([$documentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>