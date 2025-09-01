<?php
session_start();
require_once 'src/logic/ujian-logic.php';
$logic = new UjianLogic();
$ujian_id = 6; $siswa_id = 25; // sesuaikan siswa
echo "Server Time: ".date('Y-m-d H:i:s')."<br>";
$res = $logic->mulaiUjian($ujian_id, $siswa_id);
var_dump($res);
