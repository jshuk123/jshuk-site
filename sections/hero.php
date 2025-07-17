<!-- SECONDARY HERO SECTION -->
<section class="secondary-hero">
  <div class="container">
    <div class="secondary-hero-content">
      <h2>Your Complete Jewish Community Hub</h2>
      <p class="hero-subtitle">
        Explore local Jewish businesses, simchas, trades, tutors, classifieds, and more — trusted by 1,000+ families in the UK.
      </p>
      <div class="hero-cta-buttons">
        <a href="<?= BASE_PATH ?>businesses.php" class="hero-btn primary">Browse Businesses</a>
        <a href="<?= BASE_PATH ?>recruitment.php" class="hero-btn secondary">Find Jobs</a>
        <a href="<?= BASE_PATH ?>classifieds.php" class="hero-btn secondary">Browse Classifieds</a>
      </div>
    </div>
  </div>
</section>

<style>
.secondary-hero {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 4rem 0;
  text-align: center;
}

.secondary-hero-content {
  max-width: 800px;
  margin: 0 auto;
}

.secondary-hero h2 {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 1rem;
}

.hero-subtitle {
  font-size: 1.2rem;
  color: #6c757d;
  margin-bottom: 2rem;
  line-height: 1.6;
}

.hero-cta-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.hero-btn {
  display: inline-block;
  padding: 12px 24px;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.hero-btn.primary {
  background: #007bff;
  color: white;
}

.hero-btn.primary:hover {
  background: #0056b3;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.hero-btn.secondary {
  background: transparent;
  color: #007bff;
  border-color: #007bff;
}

.hero-btn.secondary:hover {
  background: #007bff;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

@media (max-width: 768px) {
  .secondary-hero {
    padding: 2rem 0;
  }
  
  .secondary-hero h2 {
    font-size: 2rem;
  }
  
  .hero-cta-buttons {
    flex-direction: column;
    align-items: center;
  }
  
  .hero-btn {
    width: 100%;
    max-width: 300px;
    text-align: center;
  }
}
</style>
