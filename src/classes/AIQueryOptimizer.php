<?php
/**
 * AI Query Optimization for Chunked Documents
 * File: src/classes/AIQueryOptimizer.php
 */

require_once __DIR__ . '/DocumentChunker.php';

class AIQueryOptimizer {
    private $chunker;
    private $maxTokens;
    private $reservedTokensForResponse;
    
    public function __construct($maxTokens = 8000) {
        $this->chunker = new DocumentChunker();
        $this->maxTokens = $maxTokens;
        $this->reservedTokensForResponse = 2000; // Reserve tokens for AI response
    }
    
    /**
     * Optimize query by finding relevant chunks and creating efficient context
     */
    public function optimizeDocumentQuery($documentIds, $userQuery, $systemPrompt = '') {
        if (empty($documentIds)) {
            return [
                'optimized_query' => $userQuery,
                'context_summary' => '',
                'chunks_used' => [],
                'token_estimate' => $this->estimateTokens($userQuery)
            ];
        }
        
        // Find relevant chunks across all documents
        $allRelevantChunks = [];
        
        foreach ($documentIds as $documentId) {
            $documentInfo = $this->chunker->getDocumentInfo($documentId);
            
            if (!$documentInfo || $documentInfo['processing_status'] !== 'completed') {
                continue;
            }
            
            $chunks = $this->chunker->findRelevantChunks($documentId, $userQuery, 5);
            
            foreach ($chunks as $chunk) {
                $chunk['document_info'] = $documentInfo;
                $allRelevantChunks[] = $chunk;
            }
        }
        
        // Sort by relevance score if available
        usort($allRelevantChunks, function($a, $b) {
            $scoreA = ($a['content_score'] ?? 0) + ($a['keyword_score'] ?? 0);
            $scoreB = ($b['content_score'] ?? 0) + ($b['keyword_score'] ?? 0);
            return $scoreB <=> $scoreA;
        });
        
        // Select best chunks within token limit
        $selectedChunks = $this->selectChunksWithinTokenLimit($allRelevantChunks, $userQuery, $systemPrompt);
        
        // Create optimized query
        $optimizedQuery = $this->buildOptimizedQuery($selectedChunks, $userQuery, $systemPrompt);
        
        return [
            'optimized_query' => $optimizedQuery,
            'context_summary' => $this->createContextSummary($selectedChunks),
            'chunks_used' => $this->formatChunksForResponse($selectedChunks),
            'token_estimate' => $this->estimateTokens($optimizedQuery),
            'documents_processed' => count($documentIds)
        ];
    }
    
    /**
     * Select chunks within token limit
     */
    private function selectChunksWithinTokenLimit($chunks, $userQuery, $systemPrompt) {
        $availableTokens = $this->maxTokens - $this->reservedTokensForResponse;
        $baseQueryTokens = $this->estimateTokens($systemPrompt . $userQuery);
        $availableForChunks = $availableTokens - $baseQueryTokens;
        
        $selectedChunks = [];
        $usedTokens = 0;
        
        foreach ($chunks as $chunk) {
            $chunkTokens = $this->estimateTokens($chunk['content']);
            
            if ($usedTokens + $chunkTokens <= $availableForChunks) {
                $selectedChunks[] = $chunk;
                $usedTokens += $chunkTokens;
                
                // Limit to 3 best chunks for efficiency
                if (count($selectedChunks) >= 3) {
                    break;
                }
            }
        }
        
        return $selectedChunks;
    }
    
    /**
     * Build optimized query with context
     */
    private function buildOptimizedQuery($chunks, $userQuery, $systemPrompt = '') {
        if (empty($chunks)) {
            return $systemPrompt . "\n\nPertanyaan: " . $userQuery;
        }
        
        $contextText = "KONTEKS DOKUMEN:\n\n";
        
        foreach ($chunks as $index => $chunk) {
            $docInfo = $chunk['document_info'];
            $chunkNumber = $index + 1;
            
            $contextText .= "--- BAGIAN {$chunkNumber} dari \"{$docInfo['original_filename']}\" ---\n";
            $contextText .= $chunk['content'] . "\n\n";
        }
        
        $optimizedQuery = $systemPrompt . "\n\n";
        $optimizedQuery .= $contextText;
        $optimizedQuery .= "INSTRUKSI:\n";
        $optimizedQuery .= "- Berdasarkan konteks dokumen di atas, jawab pertanyaan berikut\n";
        $optimizedQuery .= "- Kutip bagian relevan dari dokumen jika perlu\n";
        $optimizedQuery .= "- Jika informasi tidak ada dalam konteks, katakan dengan jelas\n";
        $optimizedQuery .= "- JANGAN gunakan tabel dalam jawaban\n";
        $optimizedQuery .= "- Jawab dengan singkat dan fokus pada informasi penting\n\n";
        $optimizedQuery .= "PERTANYAAN: " . $userQuery;
        
        return $optimizedQuery;
    }
    
    /**
     * Create summary of context used
     */
    private function createContextSummary($chunks) {
        if (empty($chunks)) {
            return 'Tidak ada dokumen yang dianalisis.';
        }
        
        $documents = [];
        $totalWords = 0;
        
        foreach ($chunks as $chunk) {
            $docInfo = $chunk['document_info'];
            $filename = $docInfo['original_filename'];
            
            if (!isset($documents[$filename])) {
                $documents[$filename] = [
                    'chunks' => 0,
                    'words' => 0
                ];
            }
            
            $documents[$filename]['chunks']++;
            $documents[$filename]['words'] += $chunk['word_count'];
            $totalWords += $chunk['word_count'];
        }
        
        $summary = "Menganalisis " . count($chunks) . " bagian teks";
        
        if (count($documents) == 1) {
            $filename = array_keys($documents)[0];
            $summary .= " dari dokumen \"$filename\"";
        } else {
            $summary .= " dari " . count($documents) . " dokumen";
        }
        
        $summary .= " (total ~$totalWords kata)";
        
        return $summary;
    }
    
    /**
     * Format chunks for response
     */
    private function formatChunksForResponse($chunks) {
        return array_map(function($chunk) {
            return [
                'document' => $chunk['document_info']['original_filename'],
                'chunk_index' => $chunk['chunk_index'],
                'word_count' => $chunk['word_count'],
                'relevance_score' => ($chunk['content_score'] ?? 0) + ($chunk['keyword_score'] ?? 0)
            ];
        }, $chunks);
    }
    
    /**
     * Estimate token count (rough approximation)
     * 1 token ≈ 0.75 words for English, adjust for Indonesian
     */
    private function estimateTokens($text) {
        $wordCount = str_word_count($text);
        // Indonesian text tends to be slightly more tokens per word
        return intval($wordCount * 1.2);
    }
    
    /**
     * Get processing statistics for documents
     */
    public function getDocumentStats($documentIds) {
        $stats = [
            'total_documents' => count($documentIds),
            'processed_documents' => 0,
            'total_chunks' => 0,
            'total_words' => 0,
            'processing_status' => []
        ];
        
        foreach ($documentIds as $documentId) {
            $info = $this->chunker->getDocumentInfo($documentId);
            
            if ($info) {
                $stats['processing_status'][] = [
                    'document_id' => $documentId,
                    'filename' => $info['original_filename'],
                    'status' => $info['processing_status'],
                    'chunks' => $info['total_chunks'],
                    'words' => $info['total_words']
                ];
                
                if ($info['processing_status'] === 'completed') {
                    $stats['processed_documents']++;
                    $stats['total_chunks'] += $info['total_chunks'];
                    $stats['total_words'] += $info['total_words'];
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Clean old chunks and optimize database
     */
    public function cleanupOldChunks($daysOld = 30) {
        $db = getDB();
        
        // Delete old documents and their chunks (CASCADE will handle chunks)
        $stmt = $db->prepare("
            DELETE FROM documents 
            WHERE upload_date < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        
        $stmt->execute([$daysOld]);
        
        return $stmt->rowCount();
    }
}

/**
 * Simple database connection function (duplicate for standalone usage)
 */
function getDB() {
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
?>