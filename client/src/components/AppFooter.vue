<template>
  <footer class="app-footer">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 col-md-6 mb-4 mb-md-0 footer-brand">
          <div class="d-flex align-items-center mb-3">
            <div class="logo-container">
              <i class="bi bi-cup-hot-fill"></i>
            </div>
            <h5 class="ms-2 mb-0">Cafeteria System</h5>
          </div>

          <p class="text-muted mb-3">
            Providing quality food and beverages to our customers with fast and reliable service.
          </p>

          <div class="social-icons">
            <a href="#" class="social-icon" title="Facebook">
              <i class="bi bi-facebook"></i>
            </a>
            <a href="#" class="social-icon" title="Twitter">
              <i class="bi bi-twitter"></i>
            </a>
            <a href="#" class="social-icon" title="Instagram">
              <i class="bi bi-instagram"></i>
            </a>
            <a href="#" class="social-icon" title="LinkedIn">
              <i class="bi bi-linkedin"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-2 col-md-6 col-6 mb-4 mb-md-0">
          <h6 class="footer-heading">Quick Links</h6>
          <ul class="list-unstyled footer-links">
            <li>
              <router-link to="/" class="footer-link">
                <i class="bi bi-house-door me-2"></i>Home
              </router-link>
            </li>
            <li>
              <router-link to="/profile" class="footer-link">
                <i class="bi bi-person me-2"></i>Profile
              </router-link>
            </li>
            <li>
              <router-link to="/cart" class="footer-link">
                <i class="bi bi-cart me-2"></i>Cart
              </router-link>
            </li>
            <li>
              <router-link to="/orders" class="footer-link">
                <i class="bi bi-bag me-2"></i>Orders
              </router-link>
            </li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-6 col-6 mb-4 mb-md-0">
          <h6 class="footer-heading">Contact Us</h6>
          <ul class="list-unstyled footer-contact">
            <li>
              <i class="bi bi-geo-alt contact-icon"></i>
              <span>123 Campus Street</span>
            </li>
            <li>
              <i class="bi bi-telephone contact-icon"></i>
              <span>(123) 456-7890</span>
            </li>
            <li>
              <i class="bi bi-envelope contact-icon"></i>
              <span>support@cafeteria.com</span>
            </li>
            <li>
              <i class="bi bi-clock contact-icon"></i>
              <span>Mon-Fri: 7am - 8pm</span>
            </li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
          <h6 class="footer-heading">Our Services</h6>
          <div class="about-features">
            <div class="feature-item">
              <i class="bi bi-check-circle-fill feature-icon"></i>
              <span>Premium locally-sourced ingredients</span>
            </div>
            <div class="feature-item">
              <i class="bi bi-check-circle-fill feature-icon"></i>
              <span>Vegetarian & gluten-free options</span>
            </div>
            <div class="feature-item">
              <i class="bi bi-check-circle-fill feature-icon"></i>
              <span>Rapid order processing & delivery</span>
            </div>
            <div class="feature-item">
              <i class="bi bi-check-circle-fill feature-icon"></i>
              <span>10% discount for students with ID</span>
            </div>
          </div>
        </div>
      </div>

      <hr class="footer-divider" />

      <div class="row align-items-center footer-bottom">
        <div class="col-md-12 text-center">
          <p class="mb-0">
            &copy; {{ new Date().getFullYear() }} Cafeteria System. All rights reserved.
          </p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
// No additional logic needed for this component
</script>

<style scoped>
.app-footer {
  background-color: #212529;
  color: #fff;
  padding: 1.5rem 2.5rem;
  margin-top: 3rem;
}

.footer-brand {
  margin-bottom: 2rem;
}

.logo-container {
  width: 40px;
  height: 40px;
  background-color: var(--primary-color);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 4px 8px rgba(13, 110, 253, 0.25);
}

.footer-heading {
  font-weight: 600;
  margin-bottom: 1.25rem;
  font-size: 1.1rem;
  color: #fff;
  position: relative;
  padding-bottom: 0.5rem;
}

.footer-heading::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  width: 40px;
  height: 2px;
  background-color: var(--primary-color);
}

.footer-link {
  display: block;
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  margin-bottom: 0.75rem;
  transition:
    color 0.3s ease,
    transform 0.3s ease;
  padding: 0.25rem 0;
}

.footer-link:hover {
  color: #fff;
  transform: translateX(5px);
}

.footer-contact li {
  display: flex;
  align-items: flex-start;
  margin-bottom: 0.75rem;
  color: rgba(255, 255, 255, 0.7);
}

.contact-icon {
  margin-right: 0.75rem;
  color: var(--primary-color);
  font-size: 1rem;
  min-width: 18px;
}

.social-icons {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.social-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background-color: rgba(255, 255, 255, 0.1);
  color: #fff;
  border-radius: 50%;
  transition: all 0.3s ease;
  text-decoration: none;
}

.social-icon:hover {
  background-color: var(--primary-color);
  transform: translateY(-5px);
}

.about-features {
  margin-top: 1rem;
}

.feature-item {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  color: rgba(255, 255, 255, 0.7);
}

.feature-icon {
  margin-right: 0.75rem;
  color: var(--primary-color);
  font-size: 0.9rem;
}

.footer-divider {
  border-color: rgba(255, 255, 255, 0.1);
  margin: 2rem 0;
}

.footer-bottom-links .list-inline-item:not(:last-child)::after {
  content: '|';
  margin: 0 0.5rem;
  color: rgba(255, 255, 255, 0.3);
}

@media (max-width: 768px) {
  .app-footer {
    padding: 2rem 1rem;
  }
}
</style>
