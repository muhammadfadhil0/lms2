<?php
/**
 * Example Prompts untuk PingoAI
 * File ini berisi contoh-contoh prompt yang baik untuk berbagai mata pelajaran
 */

$examplePrompts = [
    'matematika' => [
        'multiple_choice' => [
            'mudah' => 'Buatkan soal matematika dasar tentang operasi bilangan, pecahan sederhana, dan geometri dasar. Soal harus sesuai untuk tingkat SMP.',
            'sedang' => 'Buatkan soal matematika tingkat menengah tentang aljabar, trigonometri dasar, dan statistika. Fokus pada penerapan rumus.',
            'sulit' => 'Buatkan soal matematika tingkat lanjut tentang kalkulus, fungsi kompleks, dan analisis matematika. Soal harus menantang kemampuan berpikir logis.'
        ],
        'essay' => [
            'mudah' => 'Buatkan soal essay matematika yang meminta siswa menjelaskan langkah-langkah penyelesaian masalah sederhana.',
            'sedang' => 'Buatkan soal essay matematika yang meminta analisis masalah dan penerapan konsep matematis dalam kehidupan sehari-hari.',
            'sulit' => 'Buatkan soal essay matematika yang meminta pembuktian teorema atau penyelesaian masalah kompleks dengan beberapa langkah.'
        ]
    ],
    
    'bahasa_indonesia' => [
        'multiple_choice' => [
            'mudah' => 'Buatkan soal bahasa Indonesia tentang tata bahasa dasar, kosakata, dan pemahaman teks sederhana.',
            'sedang' => 'Buatkan soal bahasa Indonesia tentang analisis teks, jenis-jenis karya sastra, dan penggunaan bahasa yang tepat.',
            'sulit' => 'Buatkan soal bahasa Indonesia tentang kritik sastra, analisis mendalam karya sastra, dan teori linguistik.'
        ],
        'essay' => [
            'mudah' => 'Buatkan soal essay yang meminta siswa menulis paragraf sederhana atau menceritakan pengalaman.',
            'sedang' => 'Buatkan soal essay yang meminta analisis cerpen atau puisi, atau menulis teks argumentatif.',
            'sulit' => 'Buatkan soal essay yang meminta kritik sastra mendalam atau analisis linguistik yang kompleks.'
        ]
    ],
    
    'ipa' => [
        'multiple_choice' => [
            'mudah' => 'Buatkan soal IPA tentang konsep dasar fisika, kimia, dan biologi. Fokus pada hafalan dan pemahaman konsep.',
            'sedang' => 'Buatkan soal IPA yang meminta penerapan konsep dalam situasi nyata dan perhitungan sederhana.',
            'sulit' => 'Buatkan soal IPA yang meminta analisis eksperimen, pemecahan masalah kompleks, dan sintesis konsep.'
        ],
        'essay' => [
            'mudah' => 'Buatkan soal essay IPA yang meminta penjelasan fenomena alam sederhana.',
            'sedang' => 'Buatkan soal essay IPA yang meminta desain eksperimen atau analisis data.',
            'sulit' => 'Buatkan soal essay IPA yang meminta evaluasi teori atau pemecahan masalah kompleks.'
        ]
    ],
    
    'ips' => [
        'multiple_choice' => [
            'mudah' => 'Buatkan soal IPS tentang sejarah, geografi, dan sosiologi dasar. Fokus pada fakta dan konsep dasar.',
            'sedang' => 'Buatkan soal IPS yang meminta analisis peristiwa sejarah, pemahaman peta, dan konsep ekonomi.',
            'sulit' => 'Buatkan soal IPS yang meminta evaluasi kebijakan, analisis perubahan sosial, dan sintesis konsep.'
        ],
        'essay' => [
            'mudah' => 'Buatkan soal essay IPS yang meminta deskripsi peristiwa sejarah atau fenomena sosial.',
            'sedang' => 'Buatkan soal essay IPS yang meminta analisis sebab-akibat peristiwa atau perbandingan kondisi.',
            'sulit' => 'Buatkan soal essay IPS yang meminta evaluasi dampak kebijakan atau prediksi tren masa depan.'
        ]
    ],
    
    'bahasa_inggris' => [
        'multiple_choice' => [
            'mudah' => 'Buatkan soal bahasa Inggris tentang vocabulary dasar, grammar sederhana, dan reading comprehension tingkat pemula.',
            'sedang' => 'Buatkan soal bahasa Inggris tentang tenses, conditional sentences, dan pemahaman teks tingkat menengah.',
            'sulit' => 'Buatkan soal bahasa Inggris tentang advanced grammar, idioms, dan analisis teks kompleks.'
        ],
        'essay' => [
            'mudah' => 'Buatkan soal essay bahasa Inggris yang meminta deskripsi sederhana atau cerita pendek.',
            'sedang' => 'Buatkan soal essay bahasa Inggris yang meminta opinion essay atau letter writing.',
            'sulit' => 'Buatkan soal essay bahasa Inggris yang meminta argumentative essay atau literary analysis.'
        ]
    ]
];

// Tips untuk prompt yang baik
$promptTips = [
    'be_specific' => 'Semakin spesifik konteks ujian, semakin baik hasil AI',
    'include_context' => 'Sertakan informasi tentang tingkat pendidikan dan tujuan pembelajaran',
    'set_format' => 'Jelaskan format output yang diinginkan dengan jelas',
    'give_examples' => 'Berikan contoh jika diperlukan untuk memperjelas',
    'avoid_ambiguity' => 'Hindari permintaan yang ambigu atau bisa ditafsirkan berbeda'
];

// Function untuk mendapatkan contoh prompt
function getExamplePrompt($subject, $questionType, $difficulty) {
    global $examplePrompts;
    
    $subject = strtolower(str_replace(' ', '_', $subject));
    
    if (isset($examplePrompts[$subject][$questionType][$difficulty])) {
        return $examplePrompts[$subject][$questionType][$difficulty];
    }
    
    // Default fallback
    return "Buatkan soal {$questionType} dengan tingkat kesulitan {$difficulty} untuk mata pelajaran {$subject}.";
}

// Jika dipanggil sebagai API
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'];
    
    switch ($action) {
        case 'get_subjects':
            echo json_encode(array_keys($examplePrompts));
            break;
            
        case 'get_example':
            $subject = $_GET['subject'] ?? 'matematika';
            $type = $_GET['type'] ?? 'multiple_choice';
            $difficulty = $_GET['difficulty'] ?? 'sedang';
            
            $prompt = getExamplePrompt($subject, $type, $difficulty);
            echo json_encode(['prompt' => $prompt]);
            break;
            
        case 'get_tips':
            echo json_encode($promptTips);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}
?>
