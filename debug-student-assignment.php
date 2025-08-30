<?php
// Debug file for student assignment view
session_start();
$_SESSION['user'] = array('id' => 25, 'role' => 'siswa'); // siswa ID from previous check
$_GET['kelas_id'] = 7;

require_once 'src/logic/postingan-logic.php';

echo "=== DEBUGGING STUDENT ASSIGNMENT VIEW ===\n";

try {
    $postinganLogic = new PostinganLogic();
    $posts = $postinganLogic->getPostinganByKelas(7, 10, 0);
    
    echo "Found " . count($posts) . " posts\n";
    
    foreach($posts as $post) {
        echo "\n--- POST ID: " . $post['id'] . " ---\n";
        echo "Type: " . $post['tipe_postingan'] . "\n";
        echo "Author: " . $post['namaPenulis'] . " (" . $post['rolePenulis'] . ")\n";
        
        if ($post['tipe_postingan'] === 'assignment') {
            echo "Assignment ID: " . $post['assignment_id'] . "\n";
            echo "Assignment Title: " . $post['assignment_title'] . "\n";
            echo "Assignment Deadline: " . $post['assignment_deadline'] . "\n";
            echo "Assignment Max Score: " . $post['assignment_max_score'] . "\n";
            echo "Assignment File Path: " . $post['assignment_file_path'] . "\n";
            
            // Check submission status fields
            echo "Student submission status: " . ($post['student_submission_status'] ?? 'NULL') . "\n";
            echo "Student score: " . ($post['student_score'] ?? 'NULL') . "\n";
            echo "Student feedback: " . ($post['student_feedback'] ?? 'NULL') . "\n";
        }
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
