<?php
session_start();
require_once 'src/logic/koneksi.php';

// FORCE update untuk memastikan konten admin user tersimpan dengan benar
echo "<h1>🔧 FIXING Admin Content Integration</h1>";

// Step 1: Ensure the user's content is in database with exact match  
$userTitle = "Cara Membuat Kelas Baru";
$userContent = "Untuk membuat kelas baru di Edupoint: 1. Buka menu Kelas 2. Klik Buat Kelas Baru 3. Isi nama kelas dan deskripsi 4. Pilih mata pelajaran 5. Klik Simpan. Kelas akan langsung aktif dan dapat digunakan.";

echo "<h2>Step 1: Ensuring User Content Exists</h2>";

// Delete old entries to avoid confusion
$stmt = $pdo->prepare("DELETE FROM ai_information WHERE title = ?");
$stmt->execute([$userTitle]);
echo "✅ Cleaned old entries<br>";

// Insert fresh entry
$stmt = $pdo->prepare("INSERT INTO ai_information (title, content, target_role, created_at, updated_at) VALUES (?, ?, 'guru', NOW(), NOW())");
$stmt->execute([$userTitle, $userContent]);
echo "✅ Inserted fresh entry: $userTitle<br>";

// Step 2: Direct test of Smart CS with this specific content
echo "<h2>Step 2: Testing Smart CS Handler Directly</h2>";

$_SESSION['user'] = [
    'id' => 999,
    'namaLengkap' => 'Test Guru',
    'email' => 'guru@test.com',
    'role' => 'guru'
];

require_once 'src/pingo/smart-cs-handler.php';

try {
    $handler = new SmartCSHandler();
    
    echo "<strong>Testing query:</strong> 'Cara membuat kelas baru'<br>";
    echo "<strong>Expected:</strong> Should use content: '$userContent'<br><br>";
    
    $result = $handler->handleQuery(999, "Cara membuat kelas baru", "Test Guru", "guru");
    
    if ($result['success']) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<strong>✅ SUCCESS!</strong><br>";
        echo "<strong>Topic:</strong> " . ($result['topic'] ?? 'N/A') . "<br>";
        echo "<strong>Confidence:</strong> " . ($result['confidence'] ?? 'N/A') . "<br>";
        echo "<strong>Response:</strong> " . htmlspecialchars($result['message']) . "<br>";
        echo "</div>";
        
        // Check if response contains our content
        if (strpos($result['message'], 'Buka menu Kelas') !== false) {
            echo "<p style='color: green; font-weight: bold;'>🎉 PERFECT! AI is using admin content correctly!</p>";
        } else {
            echo "<p style='color: orange; font-weight: bold;'>⚠️ AI response doesn't seem to use admin content. Let's debug...</p>";
        }
        
    } else {
        echo "<div style='background: #ffe8e8; padding: 15px; border-radius: 5px;'>";
        echo "<strong>❌ FAILED!</strong><br>";
        echo "<strong>Error:</strong> " . ($result['error'] ?? 'Unknown') . "<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffe8e8; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ EXCEPTION!</strong><br>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "</div>";
}

// Step 3: Test API endpoint
echo "<h2>Step 3: Testing via API Endpoint</h2>";
echo "<button onclick='testAPI()' style='padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer;'>Test API Endpoint</button>";
echo "<div id='apiResult' style='margin-top: 10px;'></div>";
?>

<script>
async function testAPI() {
    const result = document.getElementById('apiResult');
    result.innerHTML = '⏳ Testing API endpoint...';
    
    try {
        const response = await fetch('src/pingo/smart-cs-api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: '__TEST_MODE__Cara membuat kelas baru'})
        });
        
        const data = await response.json();
        
        if (data.success) {
            const hasAdminContent = data.message.includes('Buka menu Kelas');
            const bgColor = hasAdminContent ? '#e8f5e8' : '#fff3cd';
            const status = hasAdminContent ? '🎉 SUCCESS - Using Admin Content!' : '⚠️ Using Fallback Response';
            
            result.innerHTML = `
                <div style='background: ${bgColor}; padding: 15px; border-radius: 5px; margin-top: 10px;'>
                    <strong>${status}</strong><br>
                    <strong>Topic:</strong> ${data.topic}<br>
                    <strong>Confidence:</strong> ${(data.confidence * 100).toFixed(1)}%<br>
                    <strong>Response:</strong> ${data.message}
                </div>
            `;
        } else {
            result.innerHTML = `
                <div style='background: #ffe8e8; padding: 15px; border-radius: 5px; margin-top: 10px;'>
                    <strong>❌ API Error:</strong> ${data.error}
                </div>
            `;
        }
    } catch (error) {
        result.innerHTML = `
            <div style='background: #ffe8e8; padding: 15px; border-radius: 5px; margin-top: 10px;'>
                <strong>❌ Network Error:</strong> ${error.message}
            </div>
        `;
    }
}
</script>