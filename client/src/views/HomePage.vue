<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const featuredProducts = ref([])
const categories = ref([])
const isLoading = ref(true)
const testimonials = ref([
  {
    id: 1,
    name: 'Sarah Johnson',
    role: 'Regular Customer',
    content:
      'The coffee here is absolutely amazing! The staff is friendly and the atmosphere is perfect for both work and relaxation.',
    avatar: 'https://randomuser.me/api/portraits/women/32.jpg',
  },
  {
    id: 2,
    name: 'David Chen',
    role: 'Business Professional',
    content:
      'Their specialty teas and refreshing smoothies are perfect for my afternoon meetings. The quiet environment makes it ideal for business discussions.',
    avatar: 'https://randomuser.me/api/portraits/men/44.jpg',
  },
  {
    id: 3,
    name: 'Emma Rodriguez',
    role: 'Student',
    content:
      'Their cold brew keeps me going through long study sessions. Great Wi-Fi and the best iced drinks in town!',
    avatar: 'https://randomuser.me/api/portraits/women/68.jpg',
  },
])

// Fetch featured products and categories
onMounted(async () => {
  try {
    // Fetch featured products
    const productsResponse = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/product.controller.php?limit=4`,
    )
    if (productsResponse.ok) {
      const productsData = await productsResponse.json()
      if (productsData && productsData.data && productsData.data.data) {
        featuredProducts.value = productsData.data.data
      }
    }

    // Fetch categories
    const categoriesResponse = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/category.controller.php?limit=6`,
    )
    if (categoriesResponse.ok) {
      const categoriesData = await categoriesResponse.json()
      if (categoriesData && categoriesData.data) {
        categories.value = categoriesData.data
      }
    }
  } catch (error) {
    console.error('Error fetching data:', error)
  } finally {
    isLoading.value = false
  }
})

function navigateToProducts() {
  router.push('/products')
}

function navigateToDashboard() {
  router.push('/dashboard')
}
</script>

<template>
  <div class="homepage">
    <!-- Hero Section -->
    <section class="hero">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6 hero-text">
            <h1 class="display-4 fw-bold mb-4">
              Premium Drinks, <span class="text-primary">At Your Fingertips</span>
            </h1>
            <p class="lead mb-4">
              Experience our handcrafted beverages with easy ordering, fast service, and exceptional
              quality.
            </p>
            <div class="d-flex gap-3 mt-4">
              <button class="btn btn-primary btn-lg px-4 py-2" @click="navigateToProducts">
                View Menu
                <i class="bi bi-arrow-right-circle ms-2"></i>
              </button>
              <button class="btn btn-outline-dark btn-lg px-4 py-2" @click="navigateToDashboard">
                <i class="bi bi-gear me-2"></i>
                Dashboard
              </button>
            </div>
          </div>
          <div class="col-lg-6 mt-5 mt-lg-0">
            <div class="hero-img-container">
              <img
                src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1080&q=80"
                alt="Specialty Coffee and Drinks"
                class="img-fluid rounded-4 shadow-lg"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Categories -->
    <section class="categories py-5">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold">Explore Our <span class="text-primary">Beverages</span></h2>
          <p class="text-muted">Discover our wide variety of refreshing and delicious drinks</p>
        </div>

        <div class="row g-4 justify-content-center">
          <div v-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          <template v-else>
            <div
              class="col-lg-4 col-md-6"
              v-for="category in categories.slice(0, 6)"
              :key="category.id"
            >
              <div class="card category-card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                  <div class="category-icon mb-3">
                    <i class="bi bi-grid-3x3-gap-fill fs-1 text-primary"></i>
                  </div>
                  <h5 class="card-title fw-bold mb-3">{{ category.name }}</h5>
                  <p class="card-text text-muted">
                    Explore our delicious selection of {{ category.name.toLowerCase() }} beverages.
                  </p>
                  <a href="#" class="btn btn-sm btn-outline-primary mt-3">
                    View Products
                    <i class="bi bi-arrow-right ms-1"></i>
                  </a>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold">Featured <span class="text-primary">Drinks</span></h2>
          <p class="text-muted">Our most popular beverages that customers love</p>
        </div>

        <div class="row g-4">
          <div v-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          <template v-else>
            <div class="col-lg-3 col-md-6" v-for="product in featuredProducts" :key="product.id">
              <div class="card product-card border-0 shadow-sm h-100">
                <div class="position-relative">
                  <img
                    :src="product.image || 'https://via.placeholder.com/300x200?text=Product+Image'"
                    class="card-img-top"
                    alt="Product Image"
                    style="height: 200px; object-fit: cover"
                  />
                  <div class="product-badge" v-if="product.availability === 'available'">
                    <span class="badge bg-success">Available</span>
                  </div>
                  <div class="product-badge" v-else>
                    <span class="badge bg-danger">Unavailable</span>
                  </div>
                </div>
                <div class="card-body">
                  <h5 class="card-title fw-bold mb-2">{{ product.name }}</h5>
                  <p class="card-price text-primary fw-bold mb-2">
                    ${{ parseFloat(product.price).toFixed(2) }}
                  </p>
                  <p class="card-text text-muted small mb-3">
                    {{
                      product.description ||
                      'Delicious ' + product.name + ' prepared with premium ingredients.'
                    }}
                  </p>
                  <div class="d-flex justify-content-between align-items-center mt-3">
                    <button class="btn btn-sm btn-primary">
                      <i class="bi bi-cart-plus me-1"></i> Order Now
                    </button>
                    <button class="btn btn-sm btn-outline-dark">
                      <i class="bi bi-info-circle me-1"></i> Details
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>

        <div class="text-center mt-5">
          <button class="btn btn-lg btn-outline-primary px-4" @click="navigateToProducts">
            View All Products <i class="bi bi-arrow-right ms-2"></i>
          </button>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works py-5">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold">How It <span class="text-primary">Works</span></h2>
          <p class="text-muted">Quick and easy steps to order from our cafeteria</p>
        </div>

        <div class="row g-4 justify-content-center">
          <div class="col-md-4 text-center">
            <div class="step-card p-4">
              <div class="step-icon mb-3">
                <div class="icon-circle">
                  <i class="bi bi-search fs-2"></i>
                </div>
              </div>
              <h4 class="fw-bold">1. Browse Menu</h4>
              <p class="text-muted">
                Explore our diverse beverage menu and find your favorite drinks.
              </p>
            </div>
          </div>
          <div class="col-md-4 text-center">
            <div class="step-card p-4">
              <div class="step-icon mb-3">
                <div class="icon-circle">
                  <i class="bi bi-cart-plus fs-2"></i>
                </div>
              </div>
              <h4 class="fw-bold">2. Place Order</h4>
              <p class="text-muted">Add items to your cart and complete your order in minutes.</p>
            </div>
          </div>
          <div class="col-md-4 text-center">
            <div class="step-card p-4">
              <div class="step-icon mb-3">
                <div class="icon-circle">
                  <i class="bi bi-cup-hot fs-2"></i>
                </div>
              </div>
              <h4 class="fw-bold">3. Enjoy!</h4>
              <p class="text-muted">Collect your order or have it delivered to your room.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold">What Our <span class="text-primary">Customers Say</span></h2>
          <p class="text-muted">Hear from people who love our cafeteria</p>
        </div>

        <div class="row g-4">
          <div class="col-md-4" v-for="testimonial in testimonials" :key="testimonial.id">
            <div class="card testimonial-card border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <div class="d-flex mb-4">
                  <i class="bi bi-star-fill text-warning me-1"></i>
                  <i class="bi bi-star-fill text-warning me-1"></i>
                  <i class="bi bi-star-fill text-warning me-1"></i>
                  <i class="bi bi-star-fill text-warning me-1"></i>
                  <i class="bi bi-star-fill text-warning me-1"></i>
                </div>
                <p class="card-text mb-4">"{{ testimonial.content }}"</p>
                <div class="d-flex align-items-center">
                  <img
                    :src="testimonial.avatar"
                    alt="Customer"
                    class="rounded-circle me-3"
                    width="50"
                    height="50"
                  />
                  <div>
                    <h6 class="mb-0 fw-bold">{{ testimonial.name }}</h6>
                    <p class="text-muted small mb-0">{{ testimonial.role }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Call to Action -->
    <section class="cta py-5">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="cta-card text-center p-5 rounded-4 bg-primary text-white">
              <h2 class="display-6 fw-bold mb-3">Ready for a Refreshing Drink?</h2>
              <p class="lead mb-4">
                Experience the best beverages and service our cafeteria has to offer.
              </p>
              <button class="btn btn-light btn-lg px-4 fw-semibold" @click="navigateToProducts">
                Order Now <i class="bi bi-arrow-right-circle ms-2"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.homepage {
  overflow-x: hidden;
}

/* Hero Section */
.hero {
  padding: 80px 0;
  background: linear-gradient(to right, #f8f9fa 0%, #f1f1f1 100%);
}

.hero-text h1 {
  font-size: 3rem;
  line-height: 1.2;
}

.hero-text p {
  color: #6c757d;
  font-size: 1.2rem;
}

.hero-img-container {
  position: relative;
}

.hero-img-container img {
  transition: transform 0.5s ease;
}

.hero-img-container:hover img {
  transform: scale(1.02);
}

/* Category Cards */
.category-card {
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease;
  border-radius: 10px;
  overflow: hidden;
}

.category-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.category-icon {
  background-color: rgba(13, 110, 253, 0.1);
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}

/* Product Cards */
.product-card {
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease;
  border-radius: 10px;
  overflow: hidden;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.product-badge {
  position: absolute;
  top: 10px;
  right: 10px;
}

/* How It Works */
.step-card {
  transition: transform 0.3s ease;
}

.step-card:hover {
  transform: translateY(-5px);
}

.icon-circle {
  background-color: rgba(13, 110, 253, 0.1);
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  color: #0d6efd;
}

/* Testimonials */
.testimonial-card {
  transition: transform 0.3s ease;
  border-radius: 10px;
}

.testimonial-card:hover {
  transform: translateY(-5px);
}

/* CTA Section */
.cta-card {
  background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
  box-shadow: 0 10px 30px rgba(13, 110, 253, 0.3);
}

.btn {
  border-radius: 5px;
  transition: all 0.3s ease;
}

.btn-primary {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.btn-primary:hover {
  background-color: #0b5ed7;
  border-color: #0a58ca;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
}

.btn-outline-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
}

@media (max-width: 992px) {
  .hero {
    padding: 60px 0;
  }

  .hero-text h1 {
    font-size: 2.5rem;
  }
}

@media (max-width: 768px) {
  .hero {
    padding: 40px 0;
    text-align: center;
  }

  .hero-text h1 {
    font-size: 2rem;
  }

  .hero-text {
    margin-bottom: 2rem;
  }

  .hero .btn-group {
    justify-content: center;
  }
}
</style>
