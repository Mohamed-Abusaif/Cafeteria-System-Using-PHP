import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'HomePage',
      component: () => import('../views/HomePage.vue'),
    },
    {
      path: '/login',
      name: 'Login',
      component: () => import('../views/LoginPage.vue'),
    },
    {
      path: '/reset-password',
      name: 'ResetPassword',
      component: () => import('../views/ResetPassword.vue'),
    },
    {
      path: '/profile',
      name: 'Profile',
      component: () => import('../views/ProfilePage.vue'),
    },
    {
      path: '/cart',
      name: 'Cart',
      component: () => import('../views/CartPage.vue'),
    },
    {
      path: '/orders',
      name: 'Orders',
      component: () => import('../views/OrderPage.vue'),
    },
    {
      path: '/dashboard',
      name: 'Dashboard',
      component: () => import('../views/admin/DashBoard.vue'),
    },
    {
      path: '/dashboard/users',
      name: 'User',
      component: () => import('../views/admin/UserPage.vue'),
    },
    {
      path: '/dashboard/categories',
      name: 'Category',
      component: () => import('../views/admin/CategoryPage.vue'),
    },
    {
      path: '/dashboard/rooms',
      name: 'Room',
      component: () => import('../views/admin/RoomPage.vue'),
    },
    {
      path: '/dashboard/products',
      name: 'Product',
      component: () => import('../views/admin/ProductPage.vue'),
    },
    {
      path: '/dashboard/orders',
      name: 'Order',
      component: () => import('../views/admin/OrderPage.vue'),
    },
    {
      path: '/dashboard/checks',
      name: 'Check',
      component: () => import('../views/admin/CheckPage.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('../views/NotFound.vue'),
    },
  ],
})

export default router
