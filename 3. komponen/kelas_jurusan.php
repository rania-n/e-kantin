<?php
/*
  helper bersama untuk dropdown kelas/jurusan pembeli SMK.

  pembeli tidak hanya murid — guru & staf sekolah juga bisa daftar sebagai pembeli.
  jadi kolom `kelas` di DB menyimpan salah satu bentuk berikut:
    - "10 RPL", "11 AK", "12 DKV", dst    (untuk murid)
    - "Guru"                              (untuk guru)
    - "Staff Sekolah"                     (untuk staf TU, kebersihan, dll)

  semua form (registrasi pembeli, tambah/edit user oleh admin) memakai satu
  dropdown bersama dengan optgroup supaya jelas pengelompokannya tanpa butuh JS.
*/

if (!function_exists('daftarTingkatKelas')) {

    // daftar tingkat kelas: 10, 11, 12 — dipakai bersama dengan daftarJurusan()
    // untuk menghasilkan seluruh kombinasi kelas murid.
    function daftarTingkatKelas(): array {
        return ['10', '11', '12'];
    }

    // daftar jurusan SMK: kunci = kode singkat (yang disimpan di DB sebagai
    // bagian dari kolom `kelas`), nilai = nama lengkap (ditampilkan ke user).
    function daftarJurusan(): array {
        return [
            'AK'   => 'Akuntansi',
            'MP'   => 'Manajemen Perkantoran',
            'BD'   => 'Bisnis Digital',
            'ULW'  => 'Usaha Layanan Wisata',
            'TKI'  => 'Teknik Kimia Industri',
            'TKJ'  => 'Teknik Komputer dan Jaringan',
            'RPL'  => 'Rekayasa Perangkat Lunak',
            'AN'   => 'Animasi',
            'DKV'  => 'Desain Komunikasi Visual',
            'PSPT' => 'Produksi dan Siaran Program Televisi',
        ];
    }

    // opsi non-murid: guru & staf. value = string yang disimpan ke kolom `kelas`.
    function daftarPembeliNonMurid(): array {
        return [
            'Guru'          => 'Guru',
            'Staff Sekolah' => 'Staff Sekolah',
        ];
    }

    // bangun daftar lengkap nilai kelas yang valid — gabungan murid (30 opsi)
    // dan non-murid (2 opsi). dipakai oleh kelasValid() untuk validasi server.
    function daftarSemuaKelas(): array {
        $semua = [];
        foreach (daftarTingkatKelas() as $tingkat) {
            foreach (daftarJurusan() as $kode => $_) {
                $semua[] = $tingkat . ' ' . $kode;
            }
        }
        foreach (daftarPembeliNonMurid() as $val => $_) {
            $semua[] = $val;
        }
        return $semua;
    }

    // validasi: pastikan string `$kelas` ada di daftar opsi resmi.
    // mencegah pengguna nakal kirim nilai sembarangan lewat devtools.
    function kelasValid(string $kelas): bool {
        return in_array($kelas, daftarSemuaKelas(), true);
    }

    // render satu <select> berisi semua opsi kelas, dikelompokkan dengan optgroup.
    // parameter:
    // - $kelasTerpilih : nilai default untuk pre-fill saat edit (cocokkan ke option)
    // - $wajib         : true = tambahkan atribut required di select
    // - $nama          : atribut name dari <select> (default 'kelas')
    function tampilkanDropdownKelas(
        string $kelasTerpilih = '',
        bool $wajib = true,
        string $nama = 'kelas'
    ): void {
        $req = $wajib ? 'required' : '';
        ?>
        <!-- dropdown <select> biasa: klik → semua pilihan langsung tampil, tinggal pilih.
             dikelompokkan optgroup (Murid SMK / Non-Murid) supaya jelas. nilai yang
             dikirim string resmi spt "10 RPL"/"Guru", divalidasi server via kelasValid(). -->
        <select name="<?= htmlspecialchars($nama) ?>" <?= $req ?> style="width:100%;">
          <option value="">— Pilih kelas / status —</option>
          <optgroup label="Murid SMK">
            <?php foreach (daftarTingkatKelas() as $t): ?>
              <?php foreach (daftarJurusan() as $kode => $namaJurusan):
                $val = $t . ' ' . $kode;
              ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= $val===$kelasTerpilih ? 'selected' : '' ?>>
                Kelas <?= htmlspecialchars($t) ?> — <?= htmlspecialchars($kode) ?> (<?= htmlspecialchars($namaJurusan) ?>)
              </option>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </optgroup>
          <optgroup label="Non-Murid">
            <?php foreach (daftarPembeliNonMurid() as $val => $label): ?>
            <option value="<?= htmlspecialchars($val) ?>" <?= $val===$kelasTerpilih ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
            <?php endforeach; ?>
          </optgroup>
        </select>
        <?php
    }
}
