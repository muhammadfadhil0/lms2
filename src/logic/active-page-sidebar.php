<?php
// Function to check if current page is active
function isActivePage($pageName, $currentPage) {
    return $pageName === $currentPage;
}

// Get current page from parameter or default to 'beranda'
$currentPage = isset($currentPage) ? $currentPage : 'beranda';