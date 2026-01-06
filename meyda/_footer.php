<?php
// Shared footer for public pages
// Usage: include __DIR__ . '/_footer.php'; placed before </body> in public pages
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-content">
      <div class="footer-section">
        <h3>Tentang MeyDa</h3>
        <p>MeyDa Collection adalah destinasi fashion premium yang menyediakan koleksi terbaru dengan kualitas terbaik untuk gaya hidup modern.</p>
      </div>
      
      <div class="footer-section">
        <h3>Quick Links</h3>
        <ul>
          <li><a href="index.php">Beranda</a></li>
          <li><a href="index.php#products">Produk</a></li>
          <li><a href="cart.php">Keranjang</a></li>
          <?php if (isLoggedIn()): ?>
            <li><a href="account.php">Akun Saya</a></li>
          <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Daftar</a></li>
          <?php endif; ?>
        </ul>
      </div>
      
      <div class="footer-section">
        <h3>Kontak Kami</h3>
        <p>Email: info@meyda-collection.com</p>
        <p>Telepon: +62 123 4567 890</p>
        <p>Alamat: Jl. Fashion No. 123, Jakarta, Indonesia</p>
      </div>
    </div>
    
    <div class="footer-bottom">
      <div class="footer-left">
        <small>&copy; <?php echo date('Y'); ?> MeyDa Collection. Hak Cipta Dilindungi.</small>
      </div>
      <div class="footer-right">
        <a href="#" class="social-link">Instagram</a>
        <a href="#" class="social-link">Facebook</a>
        <a href="#" class="social-link">TikTok</a>
      </div>
    </div>
  </div>
</footer>