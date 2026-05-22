<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>jajankita — Pesan &amp; Nikmati</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* variabel warna selaras dengan seluruh aplikasi */
:root {
  --utama:   #643843;
  --kedua:   #99627A;
  --latar:   #EFD9D4;
  --putihbg: #F8EBF1;
  --putih:   #FFFFFF;
  --garis:   #E7CBCB;
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html { scroll-behavior: smooth; }
body {
  font-family: 'Poppins', 'Segoe UI', sans-serif;
  background: var(--latar);
  color: var(--utama);
  min-height: 100vh;
}
a { text-decoration: none; color: inherit; }

/* ===== HERO ===== */
.hero {
  background: var(--utama);
  min-height: 100vh;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  text-align: center;
  padding: 40px 20px;
  position: relative;
  overflow: hidden;
}
.hero::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(circle at 70% 30%, rgba(153,98,122,.35) 0%, transparent 60%);
  pointer-events: none;
}

/* logo */
.logo {
  display: inline-flex; align-items: center; gap: 14px;
  margin-bottom: 32px;
}
.logo .ikon {
  width: 72px; height: 72px;
  border-radius: 20px;
  background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 30px; color: var(--putihbg);
  border: 2px solid rgba(255,255,255,.25);
}
.logo .nama {
  font-size: 40px; font-weight: 800;
  color: var(--putihbg);
  letter-spacing: -1px;
}

/* teks hero */
.hero-tagline {
  font-size: 18px; color: rgba(255,255,255,.8);
  margin-bottom: 14px; font-weight: 500;
}
.hero-judul {
  font-size: clamp(26px, 5vw, 44px);
  font-weight: 800; color: var(--putihbg);
  line-height: 1.2; max-width: 600px;
  margin-bottom: 16px;
}
.hero-sub {
  font-size: 15px; color: rgba(255,255,255,.75);
  max-width: 480px; line-height: 1.7;
  margin-bottom: 36px;
}

/* tombol hero */
.grup-tombol {
  display: flex; gap: 12px; flex-wrap: wrap;
  justify-content: center;
}
.tombol-utama {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--putihbg); color: var(--utama);
  padding: 14px 28px; border-radius: 12px;
  font-size: 15px; font-weight: 700;
  transition: opacity .15s;
}
.tombol-utama:hover { opacity: .9; }
.tombol-sekunder {
  display: inline-flex; align-items: center; gap: 8px;
  background: transparent; color: var(--putihbg);
  padding: 14px 28px; border-radius: 12px;
  font-size: 15px; font-weight: 700;
  border: 2px solid rgba(255,255,255,.45);
  transition: border-color .15s;
}
.tombol-sekunder:hover { border-color: var(--putihbg); }

/* label domain */
.label-domain {
  margin-top: 40px;
  font-size: 12px; color: rgba(255,255,255,.5);
  letter-spacing: .5px;
}

/* ===== FITUR ===== */
.seksi-fitur {
  padding: 60px 20px;
  background: var(--putihbg);
}
.seksi-fitur .judul {
  text-align: center;
  font-size: 24px; font-weight: 800; color: var(--utama);
  margin-bottom: 8px;
}
.seksi-fitur .sub {
  text-align: center; font-size: 14px; color: var(--kedua);
  margin-bottom: 36px;
}
.grid-fitur {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 18px; max-width: 900px; margin: 0 auto;
}
.kartu-fitur {
  background: var(--putih);
  border-radius: 16px; padding: 24px 20px;
  border: 1px solid var(--garis);
  text-align: center;
}
.kartu-fitur .ikon-fitur {
  width: 50px; height: 50px;
  border-radius: 14px; background: var(--latar);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; color: var(--utama);
  margin: 0 auto 14px;
}
.kartu-fitur h3 { font-size: 15px; font-weight: 700; margin-bottom: 6px; }
.kartu-fitur p  { font-size: 13px; color: var(--kedua); line-height: 1.6; }

/* ===== FOOTER ===== */
.footer {
  background: var(--utama); color: rgba(255,255,255,.8);
  text-align: center; padding: 24px 20px;
  font-size: 13px;
}
.footer a { color: rgba(255,255,255,.9); font-weight: 600; }
</style>
</head>
<body>

<!-- hero section -->
<section class="hero">
  <div class="logo">
    <div class="ikon"><i class="fa-solid fa-utensils"></i></div>
    <div class="nama">jajankita</div>
  </div>

  <div class="hero-tagline">Platform Kantin Digital</div>
  <h1 class="hero-judul">Pesan Makanan Favoritmu dengan Mudah</h1>
  <p class="hero-sub">
    jajankita menghubungkan kamu dengan berbagai kantin di sekitarmu.
    Pesan, bayar, dan ambil — semua dari satu tempat.
  </p>

  <div class="grup-tombol">
    <a href="4. autentifikasi/login.php" class="tombol-utama">
      <i class="fa-solid fa-right-to-bracket"></i> Masuk
    </a>
    <a href="4. autentifikasi/register.php" class="tombol-sekunder">
      <i class="fa-solid fa-user-plus"></i> Daftar Gratis
    </a>
  </div>

  <!-- link login admin terpisah — disembunyikan di bawah agar tidak mencolok -->
  <div class="label-domain">
    jajankita.my.id &nbsp;·&nbsp;
    <!-- link ke login admin terpisah dari login pembeli/penjual -->
    <a href="admin/login/loginadmin.php" style="color:rgba(255,255,255,.4);font-size:11px;">
      <i class="fa-solid fa-shield-halved"></i> Admin
    </a>
  </div>
</section>

<!-- fitur -->
<section class="seksi-fitur">
  <div class="judul">Kenapa jajankita?</div>
  <div class="sub">Pesan lebih cepat, nikmati lebih lama</div>
  <div class="grid-fitur">
    <div class="kartu-fitur">
      <div class="ikon-fitur"><i class="fa-solid fa-store"></i></div>
      <h3>Banyak Pilihan Kantin</h3>
      <p>Temukan berbagai kantin dengan menu beragam — semua tersedia di satu platform.</p>
    </div>
    <div class="kartu-fitur">
      <div class="ikon-fitur"><i class="fa-solid fa-cart-shopping"></i></div>
      <h3>Pemesanan Mudah</h3>
      <p>Pilih menu, masukkan keranjang, dan konfirmasi dalam hitungan detik.</p>
    </div>
    <div class="kartu-fitur">
      <div class="ikon-fitur"><i class="fa-solid fa-bell"></i></div>
      <h3>Notifikasi Real-time</h3>
      <p>Dapatkan notifikasi langsung ketika pesananmu siap diambil di kantin.</p>
    </div>
    <div class="kartu-fitur">
      <div class="ikon-fitur"><i class="fa-solid fa-star"></i></div>
      <h3>Beri Ulasan</h3>
      <p>Bantu penjual berkembang dengan rating dan ulasan setelah makan.</p>
    </div>
  </div>
</section>

<!-- footer -->
<footer class="footer">
  <p>
    &copy; 2025 <a href="index.php">jajankita</a> &mdash; jajankita.my.id &nbsp;|&nbsp;
    <a href="4. autentifikasi/login.php">Masuk</a> &nbsp;&middot;&nbsp;
    <a href="4. autentifikasi/register.php">Daftar</a> &nbsp;&middot;&nbsp;
    <!-- link ke login admin di footer agar mudah ditemukan oleh admin sekolah -->
    <a href="admin/login/loginadmin.php"><i class="fa-solid fa-shield-halved"></i> Admin</a>
  </p>
</footer>

</body>
</html>
