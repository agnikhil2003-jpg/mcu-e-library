<?php
// ============================================================
// includes/footer.php
// ============================================================
?>
</main><!-- /.main-content -->

<!-- ── Footer ────────────────────────────────────────────── -->
<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-brand">
      <span class="brand-icon"><i class="fas fa-book-open"></i></span>
      <span>MCU E-Library</span>
      <p>Makhanlal Chaturvedi National University of Journalism &amp; Communication</p>
    </div>
    <div class="footer-links">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="/index.php">Home</a></li>
        <li><a href="/books.php">Browse Books</a></li>
        <li><a href="/login.php">Login</a></li>
        <li><a href="/register.php">Register</a></li>
      </ul>
    </div>
    <div class="footer-contact">
      <h4>Contact</h4>
      <p><i class="fas fa-map-marker-alt"></i> B-38, Press Complex, Bhopal, MP 462011</p>
      <p><i class="fas fa-envelope"></i> library@mcu.ac.in</p>
      <p><i class="fas fa-phone"></i> +91 755-2557741</p>
    </div>
    <div class="footer-ai">
      <h4>AI Features</h4>
      <ul>
        <li><a href="/chatbot.php"><i class="fas fa-robot"></i> AI Chatbot</a></li>
        <li><a href="/recommend.php"><i class="fas fa-lightbulb"></i> Book Recommendations</a></li>
        <li><a href="/books.php"><i class="fas fa-magic"></i> Smart Search</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> MCU E-Library Management System. Developed with <i class="fas fa-heart" style="color:#e74c3c"></i> for students.</p>
  </div>
</footer>

<!-- ── Floating AI Chatbot Button ──────────────────────────── -->
<a href="/chatbot.php" class="fab-chatbot" title="AI Assistant">
  <i class="fas fa-robot"></i>
  <span class="fab-label">AI Help</span>
</a>

<script src="/mcu-e-library/assets/js/script.js"></script>
</body>
</html>
