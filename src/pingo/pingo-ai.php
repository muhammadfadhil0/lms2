<?php
require_once 'config.php';
require_once 'ai-logger.php';

/**
 * PingoAI Class
 * Main class untuk handle AI question generation menggunakan Groq API
 */
class PingoAI {
    private $apiKey;
    private $apiUrl;
    private $model;
    
    public function __construct() {
        $this->apiKey = GROQ_API_KEY;
        $this->apiUrl = GROQ_API_URL;
        $this->model = GROQ_MODEL;
        
        if (empty($this->apiKey) || $this->apiKey === 'your_groq_api_key_here') {
            throw new Exception('API Key Groq belum dikonfigurasi');
        }
    }
    
    /**
     * Generate questions menggunakan AI
     */
    public function generateQuestions($params) {
        try {
            $prompt = $this->buildPrompt($params);
            $response = $this->callGroqAPI($prompt);
            
            if (!$response) {
                throw new Exception('Tidak ada response dari AI');
            }
            
            $questions = $this->parseAIResponse($response, $params);
            
            return [
                'success' => true,
                'questions' => $questions,
                'total' => count($questions)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Build prompt untuk AI berdasarkan parameter
     */
    private function buildPrompt($params) {
        $examName = $params['exam_name'] ?? 'Ujian';
        $subject = $params['subject'] ?? 'Umum';
        $description = $params['description'] ?? '';
        $topik = $params['topik'] ?? ''; // ← Tambahkan parameter topik
        $questionCount = $params['question_count'] ?? 5;
        $questionType = $params['question_type'] ?? 'multiple_choice';
        $answerOptions = $params['answer_options'] ?? 4;
        $difficulty = $params['difficulty'] ?? 'sedang';
        $existingQuestions = $params['existing_questions'] ?? [];
        
        $prompt = "Anda adalah seorang ahli pendidikan yang bertugas membuat soal ujian berkualitas tinggi.\n\n";
        
        $prompt .= "INFORMASI UJIAN:\n";
        $prompt .= "- Nama Ujian: {$examName}\n";
        $prompt .= "- Mata Pelajaran: {$subject}\n";
        if (!empty($topik)) {
            $prompt .= "- TOPIK/MATERI SPESIFIK: {$topik}\n";
        }
        if (!empty($description)) {
            $prompt .= "- Deskripsi Tambahan: {$description}\n";
        }
        $prompt .= "\n";
        
        // ← Tambahkan instruksi khusus untuk topik
        if (!empty($topik)) {
            $prompt .= "PENTING: Semua soal HARUS fokus pada topik/materi: \"{$topik}\"\n";
            $prompt .= "Jangan membuat soal di luar topik ini, meskipun masih dalam mata pelajaran yang sama.\n\n";
        }
        
        if (!empty($existingQuestions)) {
            $prompt .= "SOAL YANG SUDAH ADA (jangan duplikasi):\n";
            foreach ($existingQuestions as $idx => $q) {
                $prompt .= ($idx + 1) . ". " . strip_tags($q) . "\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "SPESIFIKASI SOAL:\n";
        $prompt .= "- Jumlah soal: {$questionCount} soal\n";
        $prompt .= "- Tingkat kesulitan: {$difficulty}\n";
        
        if ($questionType === 'multiple_choice') {
            $optionLabels = $this->getOptionLabels($answerOptions);
            $prompt .= "- Tipe: Pilihan Ganda dengan {$answerOptions} pilihan ({$optionLabels})\n";
            $prompt .= "- Setiap soal bernilai 10 poin\n\n";
            
            $prompt .= "FORMAT OUTPUT (HARUS TEPAT):\n";
            $prompt .= "Berikan response dalam format JSON seperti ini:\n";
            $prompt .= "{\n";
            $prompt .= '  "questions": [' . "\n";
            $prompt .= "    {\n";
            $prompt .= '      "question": "Teks pertanyaan di sini",' . "\n";
            $prompt .= '      "options": {' . "\n";
            for ($i = 0; $i < $answerOptions; $i++) {
                $letter = chr(65 + $i); // A, B, C, D, E, F
                $comma = $i < $answerOptions - 1 ? ',' : '';
                $prompt .= '        "' . $letter . '": "Pilihan ' . $letter . '"' . $comma . "\n";
            }
            $prompt .= "      },\n";
            $prompt .= '      "correct_answer": "A",' . "\n";
            $prompt .= '      "explanation": "Penjelasan jawaban"' . "\n";
            $prompt .= "    }\n";
            $prompt .= "  ]\n";
            $prompt .= "}\n\n";
            
        } else if ($questionType === 'essay') {
            $prompt .= "- Tipe: Essay\n";
            $prompt .= "- Setiap soal bernilai 20 poin\n";
            $prompt .= "- PENTING: Jawaban essay harus dalam format poin-poin yang ringkas dan mudah dipahami\n\n";
            
            $prompt .= "PANDUAN KHUSUS JAWABAN ESSAY:\n";
            $prompt .= "- Jawaban harus dibuat dalam format poin-poin (a, b, c, d, dst)\n";
            $prompt .= "- Setiap poin maksimal 1-2 kalimat yang ringkas dan jelas\n";
            $prompt .= "- Hindari penjelasan yang terlalu panjang\n";
            $prompt .= "- Fokus pada poin-poin utama yang mudah dipahami\n";
            $prompt .= "- Maksimal 5-6 poin per jawaban essay\n";
            $prompt .= "- Contoh format: 'a. Poin utama pertama\\nb. Poin kedua yang relevan\\nc. Poin ketiga sebagai kesimpulan'\n\n";
            
            $prompt .= "FORMAT OUTPUT (HARUS TEPAT):\n";
            $prompt .= "Berikan response dalam format JSON seperti ini:\n";
            $prompt .= "{\n";
            $prompt .= '  "questions": [' . "\n";
            $prompt .= "    {\n";
            $prompt .= '      "question": "Pertanyaan essay di sini",' . "\n";
            $prompt .= '      "sample_answer": "a. Poin utama pertama yang singkat\\nb. Poin kedua yang relevan\\nc. Poin ketiga sebagai kesimpulan",' . "\n";
            $prompt .= '      "grading_criteria": "Kriteria penilaian berdasarkan kelengkapan poin-poin jawaban"' . "\n";
            $prompt .= "    }\n";
            $prompt .= "  ]\n";
            $prompt .= "}\n\n";
        }
        
        $prompt .= "PANDUAN PEMBUATAN SOAL:\n";
        if (!empty($topik)) {
            $prompt .= "1. PRIORITAS UTAMA: Pastikan SEMUA soal berfokus pada topik \"{$topik}\"\n";
            $prompt .= "2. Jangan keluar dari topik meskipun masih dalam mata pelajaran {$subject}\n";
            $prompt .= "3. Gunakan konsep, istilah, dan contoh yang spesifik dari topik tersebut\n";
            $prompt .= "4. Pastikan soal sesuai dengan tingkat kesulitan {$difficulty}\n";
            $prompt .= "5. Gunakan bahasa Indonesia yang baik dan benar\n";
            $prompt .= "6. Soal harus jelas, tidak ambigu, dan dapat dijawab\n";
            $prompt .= "7. Untuk pilihan ganda: semua pilihan harus masuk akal, hanya 1 yang benar\n";
            if ($questionType === 'essay') {
                $prompt .= "8. KHUSUS ESSAY: Jawaban sample_answer WAJIB format poin (a. b. c. dst), maksimal 5-6 poin singkat\n";
            }
            $prompt .= "9. Hindari pertanyaan yang terlalu mudah ditebak\n";
            $prompt .= "10. Variasikan tingkat kognitif (ingat, paham, aplikasi, analisis)\n";
            $prompt .= "11. PENTING: JANGAN PERNAH gunakan format tabel dalam soal atau penjelasan\n\n";
        } else {
            $prompt .= "1. Pastikan soal sesuai dengan mata pelajaran dan tingkat kesulitan\n";
            $prompt .= "2. Gunakan bahasa Indonesia yang baik dan benar\n";
            $prompt .= "3. Soal harus jelas, tidak ambigu, dan dapat dijawab\n";
            $prompt .= "4. Untuk pilihan ganda: semua pilihan harus masuk akal, hanya 1 yang benar\n";
            if ($questionType === 'essay') {
                $prompt .= "5. KHUSUS ESSAY: Jawaban sample_answer WAJIB format poin (a. b. c. dst), maksimal 5-6 poin singkat\n";
            }
            $prompt .= "6. Hindari pertanyaan yang terlalu mudah ditebak\n";
            $prompt .= "7. Variasikan tingkat kognitif (ingat, paham, aplikasi, analisis)\n";
            $prompt .= "8. PENTING: JANGAN PERNAH gunakan format tabel dalam soal atau penjelasan\n\n";
        }
        
        // ← Tambahkan penekanan topik di akhir prompt
        if (!empty($topik)) {
            $prompt .= "REMINDER: Semua soal harus 100% berfokus pada topik \"{$topik}\". ";
            $prompt .= "Jangan membuat soal tentang topik lain dalam mata pelajaran {$subject}.\n\n";
        }
        
        // Tambahkan penekanan khusus untuk essay
        if ($questionType === 'essay') {
            $prompt .= "WAJIB UNTUK ESSAY: sample_answer harus dalam format poin-poin seperti:\n";
            $prompt .= "\"a. Poin pertama yang singkat\\nb. Poin kedua yang relevan\\nc. Poin ketiga sebagai penutup\"\n";
            $prompt .= "JANGAN gunakan format paragraf panjang! Hanya poin-poin singkat dengan huruf a, b, c, dst.\n\n";
        }
        
        $prompt .= "LARANGAN MUTLAK - JANGAN DILANGGAR:\n";
        $prompt .= "- JANGAN PERNAH menggunakan format tabel (|---|---|) dalam soal, pilihan, atau penjelasan\n";
        $prompt .= "- JANGAN menggunakan HTML table (<table>, <tr>, <td>)\n";
        $prompt .= "- JANGAN menggunakan ASCII table atau format tabel apapun\n";
        $prompt .= "- Gunakan format list, poin, atau paragraf sebagai gantinya\n\n";
        
        $prompt .= "PENTING: Response harus berupa JSON valid tanpa teks tambahan di luar JSON.";
        
        return $prompt;
    }
    
    /**
     * Panggil Groq API
     */
    private function callGroqAPI($prompt) {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah asisten AI yang ahli dalam membuat soal ujian pendidikan. Selalu berikan response dalam format JSON yang valid.'
                ],
                [
                    'role' => 'user', 
                    'content' => $prompt
                ]
            ],
            'max_tokens' => AI_MAX_TOKENS,
            'temperature' => AI_TEMPERATURE
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => AI_TIMEOUT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Curl error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'API Error';
            throw new Exception('API Error (' . $httpCode . '): ' . $errorMsg);
        }
        
        $responseData = json_decode($response, true);
        
        if (!$responseData || !isset($responseData['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        return $responseData['choices'][0]['message']['content'];
    }
    
    /**
     * Parse response dari AI
     */
    private function parseAIResponse($response, $params) {
        // Clean response - hapus markdown code blocks jika ada
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        $response = trim($response);
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['questions'])) {
            throw new Exception('Format response AI tidak valid');
        }
        
        $questions = [];
        $questionType = $params['question_type'] ?? 'multiple_choice';
        
        foreach ($data['questions'] as $q) {
            if ($questionType === 'multiple_choice') {
                $questions[] = [
                    'type' => 'multiple_choice',
                    'question' => $q['question'] ?? '',
                    'options' => $q['options'] ?? [],
                    'correct_answer' => $q['correct_answer'] ?? 'A',
                    'explanation' => $q['explanation'] ?? '',
                    'points' => 10
                ];
            } else if ($questionType === 'essay') {
                $questions[] = [
                    'type' => 'essay',
                    'question' => $q['question'] ?? '',
                    'sample_answer' => $q['sample_answer'] ?? '',
                    'grading_criteria' => $q['grading_criteria'] ?? '',
                    'points' => 20
                ];
            }
        }
        
        return $questions;
    }
    
    /**
     * Helper untuk mendapatkan label pilihan
     */
    private function getOptionLabels($count) {
        $labels = [];
        for ($i = 0; $i < $count; $i++) {
            $labels[] = chr(65 + $i); // A, B, C, D, E, F
        }
        return implode(', ', $labels);
    }
    
    /**
     * Validasi parameter input
     */
    public function validateParams($params) {
        $errors = [];
        
        // Define supported values locally to avoid global issues
        $supportedQuestionTypes = [
            'multiple_choice' => 'Pilihan Ganda',
            'essay' => 'Essay',
            'true_false' => 'Benar/Salah',
            'short_answer' => 'Jawaban Singkat'
        ];
        
        $supportedDifficulties = [
            'mudah' => 'Mudah',
            'sedang' => 'Sedang', 
            'sulit' => 'Sulit'
        ];
        
        // Validate question count
        $questionCount = $params['question_count'] ?? 0;
        if ($questionCount < MIN_QUESTIONS_PER_REQUEST || $questionCount > MAX_QUESTIONS_PER_REQUEST) {
            $errors[] = 'Jumlah soal harus antara ' . MIN_QUESTIONS_PER_REQUEST . ' sampai ' . MAX_QUESTIONS_PER_REQUEST;
        }
        
        // Validate question type
        $questionType = $params['question_type'] ?? '';
        if (!array_key_exists($questionType, $supportedQuestionTypes)) {
            $errors[] = 'Tipe soal tidak didukung: ' . $questionType;
        }
        
        // Validate difficulty
        $difficulty = $params['difficulty'] ?? '';
        if (!array_key_exists($difficulty, $supportedDifficulties)) {
            $errors[] = 'Tingkat kesulitan tidak valid: ' . $difficulty . '. Harus salah satu dari: ' . implode(', ', array_keys($supportedDifficulties));
        }
        
        // Validate answer options for multiple choice
        if ($questionType === 'multiple_choice') {
            $answerOptions = $params['answer_options'] ?? 0;
            if ($answerOptions < 2 || $answerOptions > 6) {
                $errors[] = 'Pilihan jawaban harus antara 2 sampai 6';
            }
        }
        
        return $errors;
    }
}
?>
