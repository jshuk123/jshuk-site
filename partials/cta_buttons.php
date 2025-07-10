<section class="py-5 text-center bg-light">
  <div class="container">
    <a href="/auth/register.php" class="btn btn-lg btn-primary mx-2 px-4 py-2 shadow glow-button">
      <i class="fas fa-user-plus me-2"></i> Sign Up & List Your Business
    </a>

    <a href="https://wa.me/YOURNUMBER" target="_blank" class="btn btn-lg btn-success mx-2 px-4 py-2 shadow glow-button">
      <i class="fab fa-whatsapp me-2"></i> Join WhatsApp Status
    </a>

    <a href="/newsletter/subscribe.php" class="btn btn-lg btn-warning mx-2 px-4 py-2 shadow glow-button">
      <i class="fas fa-envelope me-2"></i> Join Our Newsletter
    </a>
  </div>

  <style>
    .glow-button {
      position: relative;
      z-index: 1;
      transition: all 0.3s ease;
    }
    .glow-button::after {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      border-radius: 2rem;
      background: radial-gradient(circle, rgba(255,255,255,0.2), transparent 70%);
      opacity: 0;
      z-index: -1;
      transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .glow-button:hover::after {
      opacity: 1;
      transform: scale(1.05);
    }
    .glow-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 0 15px rgba(255, 215, 0, 0.5), 0 0 20px rgba(0, 0, 0, 0.15);
    }
  </style>
</section>
