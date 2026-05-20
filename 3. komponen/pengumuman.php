<?php
/*
   pengumuman sistem — teks dibaca dari tekspengumuman.txt.
   admin bisa update lewat form di dashboard admin.
   kosongkan isi file txt untuk menonaktifkan pengumuman.
*/
$_filePengumuman = __DIR__ . '/tekspengumuman.txt';
$teks_pengumuman = file_exists($_filePengumuman) ? trim(file_get_contents($_filePengumuman)) : '';
