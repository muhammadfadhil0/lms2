<?php
session_start();
echo "Current session data:<br>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

if (isset($_SESSION['user'])) {
    echo "User logged in as: " . $_SESSION['user']['username'] . " (ID: " . $_SESSION['user']['id'] . ", Role: " . $_SESSION['user']['role'] . ")";
} else {
    echo "No user logged in";
}
?>
