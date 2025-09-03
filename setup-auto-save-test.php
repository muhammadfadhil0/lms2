<?php
// Test setup untuk auto save functionality
session_start();

// Set test user session untuk testing
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'nama' => 'Test Siswa',
        'role' => 'siswa'
    ];
}

require_once 'src/logic/auto-save-logic.php';

try {
    $autoSave = new AutoSaveLogic();
    echo "<h2>✅ Auto Save Logic berhasil diinisialisasi</h2>\n";
    
    // Test database connection
    $testResult = $autoSave->getStatusJawaban(1, 1);
    if ($testResult['success'] || strpos($testResult['message'], 'tidak valid') !== false) {
        echo "<h3>✅ Database connection OK</h3>\n";
    } else {
        echo "<h3>❌ Database connection error: " . $testResult['message'] . "</h3>\n";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>\n";
}

// Test API endpoint
echo "<h3>Testing API Endpoint:</h3>\n";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Auto Save Setup Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Auto Save Setup Test</h1>
    
    <div class="test-section">
        <h3>API Test</h3>
        <button onclick="testAPI()">Test Auto Save API</button>
        <div id="api-result"></div>
    </div>
    
    <div class="test-section">
        <h3>JavaScript Test</h3>
        <button onclick="testJS()">Test AutoSaveManager Class</button>
        <div id="js-result"></div>
    </div>

    <script>
        async function testAPI() {
            const resultDiv = document.getElementById('api-result');
            resultDiv.innerHTML = 'Testing...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_status');
                formData.append('ujian_siswa_id', '1');
                
                const response = await fetch('src/logic/auto-save-api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success || result.message) {
                    resultDiv.innerHTML = `<span class="success">✅ API Response: ${JSON.stringify(result)}</span>`;
                } else {
                    resultDiv.innerHTML = `<span class="error">❌ Unexpected API response</span>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<span class="error">❌ API Error: ${error.message}</span>`;
            }
        }
        
        function testJS() {
            const resultDiv = document.getElementById('js-result');
            
            if (typeof AutoSaveManager !== 'undefined') {
                resultDiv.innerHTML = '<span class="success">✅ AutoSaveManager class available</span>';
                
                try {
                    const manager = new AutoSaveManager(1);
                    resultDiv.innerHTML += '<br><span class="success">✅ AutoSaveManager instance created successfully</span>';
                } catch (error) {
                    resultDiv.innerHTML += `<br><span class="error">❌ Error creating AutoSaveManager: ${error.message}</span>`;
                }
            } else {
                resultDiv.innerHTML = '<span class="error">❌ AutoSaveManager class not found</span>';
            }
        }
    </script>
    
    <script src="src/script/auto-save-manager.js"></script>
</body>
</html>
