<?php
/*
   pengumuman sistem — bertugas membaca teks pengumuman dari file teks eksternal.
   admin bisa mengubah pengumuman cukup dengan mengedit isi file tekspengumuman.txt
   melalui form di dashboard admin, tanpa perlu menyentuh kode PHP.
   jika file teks dikosongkan, pengumuman otomatis tidak akan ditampilkan.
*/

// buat path absolut ke file teks pengumuman menggunakan __DIR__
// __DIR__ adalah konstanta PHP yang berisi direktori dari file ini sendiri
// sehingga path tidak akan salah meskipun file ini di-include dari folder mana saja
$_filePengumuman = __DIR__ . '/tekspengumuman.txt';

// cek apakah file teks pengumuman ada, jika ada baca isinya
// trim() dipakai untuk membuang spasi dan baris kosong di awal/akhir teks
// jika file tidak ada, variabel diisi string kosong sehingga pengumuman tidak muncul
$teks_pengumuman = file_exists($_filePengumuman) ? trim(file_get_contents($_filePengumuman)) : '';
