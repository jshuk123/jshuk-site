<!-- TRUST SECTION -->
<section class="trust-section" data-scroll>
  <div class="container">
    <div class="trust-grid">
      <div class="trust-item">
        <div class="trust-icon">
          <i class="fa-solid fa-users"></i>
        </div>
        <div class="trust-content">
          <h3><?= number_format($stats['monthly_users'] ?? 1200) ?>+</h3>
          <p>Monthly Users</p>
        </div>
      </div>
      <div class="trust-item">
        <div class="trust-icon">
          <i class="fa-solid fa-store"></i>
        </div>
        <div class="trust-content">
          <h3><?= number_format($stats['total_businesses'] ?? 500) ?>+</h3>
          <p>Businesses Listed</p>
        </div>
      </div>
      <div class="trust-item">
        <div class="trust-icon">
          <i class="fa-brands fa-whatsapp"></i>
        </div>
        <div class="trust-content">
          <h3>1,000+</h3>
          <p>WhatsApp Status Views</p>
        </div>
      </div>
    </div>
  </div>
</section> 