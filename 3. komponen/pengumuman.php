<?php
/*
   pengumuman sistem — teks dibaca dari teks_pengumuman.txt.
   admin bisa update lewat form di dashboard admin.
   kosongkan isi file txt untuk menonaktifkan pengumuman.
*/
$_filePengumuman = __DIR__ . '/teks_pengumuman.txt';
$teks_pengumuman = file_exists($_filePengumuman) ? trim(file_get_contents($_filePengumuman)) : '';
