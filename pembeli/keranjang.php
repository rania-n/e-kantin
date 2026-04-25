<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja</title>
    <link rel="stylesheet" href="keranjang.css">
</head>
<body>

    <div class="header">
        <h2>Keranjang</h2>
        <p>2 item • Siap dipesan</p>
    </div>

    <div class="main-container">

        <!-- Daftar Item -->
        <div class="cart-items">
            <!-- Item 1 -->
            <div class="card">
                <img src="https://i.imgur.com/6L89w2S.jpg" alt="Nasi Goreng">
                <div class="info">
                    <div class="top">
                        <div class="text">
                            <h3>Nasi Goreng</h3>
                            <div class="price">Rp 15.000</div>
                        </div>
                        <div class="hapus">🗑</div>
                    </div>

                    <div class="mid">
                        <div class="qty">
                            <button class="minus">-</button>
                            <span class="quantity">1</span>
                            <button class="plus">+</button>
                        </div>
                        <div class="hasil">Rp 15.000</div>
                    </div>

                    <input type="text" placeholder="Catatan (opsional)" class="note">
                </div>
            </div>

            <!-- Item 2 -->
            <div class="card">
                <img src="https://i.imgur.com/6L89w2S.jpg" alt="Ayam Geprek">
                <div class="info">
                    <div class="top">
                        <div class="text">
                            <h3>Ayam Geprek</h3>
                            <div class="price">Rp 18.000</div>
                        </div>
                        <div class="hapus">🗑</div>
                    </div>

                    <div class="mid">
                        <div class="qty">
                            <button class="minus">-</button>
                            <span class="quantity">1</span>
                            <button class="plus">+</button>
                        </div>
                        <div class="hasil">Rp 18.000</div>
                    </div>

                    <input type="text" placeholder="Catatan (opsional)" class="note">
                </div>
            </div>
        </div>

        <!-- Summary untuk Desktop (sidebar) -->
        <div class="summary-box desktop-summary">
            <div class="total-box">
                <div class="total">
                    <span>Subtotal (2 item)</span>
                    <span>Rp 33.000</span>
                </div>
                <div class="total grand">
                    <strong>Total</strong>
                    <strong class="green">Rp 33.000</strong>
                </div>

                <div class="payment">
                    <p class="payment-title">Pilih Metode Pembayaran</p>
                    <label class="pay-option">
                        <input type="radio" name="metode" checked>
                        <span>QRIS</span>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="metode">
                        <span>Transfer Bank</span>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="metode">
                        <span>Tunai</span>
                    </label>
                </div>

                <button class="checkout">Lanjut ke Pembayaran</button>
            </div>
        </div>
    </div>

    <!-- Floating Button untuk Mobile -->
    <label for="show-summary" class="mobile-total-btn">
        Lihat Total & Pembayaran • Rp 33.000
    </label>

    <!-- Bottom Sheet Popup untuk Mobile -->
    <input type="checkbox" id="show-summary" class="summary-toggle">

    <div class="summary-overlay">
        <div class="summary-sheet">
            <div class="sheet-header">
                <div class="drag-handle"></div>
                <label for="show-summary" class="close-btn">✕</label>
            </div>
            
            <div class="total-box">
                <div class="total">
                    <span>Subtotal (2 item)</span>
                    <span>Rp 33.000</span>
                </div>
                <div class="total grand">
                    <strong>Total</strong>
                    <strong class="green">Rp 33.000</strong>
                </div>

                <div class="payment">
                    <p class="payment-title">Pilih Metode Pembayaran</p>
                    <label class="pay-option">
                        <input type="radio" name="metode" checked>
                        <span>QRIS</span>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="metode">
                        <span>Transfer Bank</span>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="metode">
                        <span>Tunai</span>
                    </label>
                </div>

                <button class="checkout">Lanjut ke Pembayaran</button>
            </div>
        </div>
    </div>

</body>
</html>