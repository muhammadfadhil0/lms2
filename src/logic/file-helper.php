<?php
// File helper functions for assignment files

function getAssignmentFileUrl($filePath) {
    if (!$filePath) {
        return null;
    }
    
    // Convert absolute path to relative URL
    $webRoot = $_SERVER['DOCUMENT_ROOT'];
    $relativePath = str_replace($webRoot, '', $filePath);
    
    // Ensure proper URL format
    if (strpos($relativePath, '/') !== 0) {
        $relativePath = '/' . $relativePath;
    }
    
    return $relativePath;
}

function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function getFileIcon($filename) {
    $ext = getFileExtension($filename);
    
    $iconMap = [
        'pdf' => 'ti ti-file-type-pdf',
        'doc' => 'ti ti-file-type-doc',
        'docx' => 'ti ti-file-type-doc',
        'xls' => 'ti ti-file-type-xls',
        'xlsx' => 'ti ti-file-type-xls',
        'ppt' => 'ti ti-file-type-ppt',
        'pptx' => 'ti ti-file-type-ppt',
        'jpg' => 'ti ti-photo',
        'jpeg' => 'ti ti-photo',
        'png' => 'ti ti-photo',
        'gif' => 'ti ti-photo',
        'mp4' => 'ti ti-video',
        'avi' => 'ti ti-video',
        'mov' => 'ti ti-video',
        'mp3' => 'ti ti-music',
        'wav' => 'ti ti-music',
        'zip' => 'ti ti-file-zip',
        'rar' => 'ti ti-file-zip'
    ];
    
    return $iconMap[$ext] ?? 'ti ti-file';
}

function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}
?>
