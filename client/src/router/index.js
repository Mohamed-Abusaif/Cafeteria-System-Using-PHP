import { createRouter, createWebHistory } from 'vue-router'
import { adminGuard, authGuard, userGuard } from './guards'

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
      beforeEnter: authGuard,
    },
    {
      path: '/cart',
      name: 'Cart',
      component: () => import('../views/CartPage.vue'),
      beforeEnter: authGuard,
    },
    {
      path: '/orders',
      name: 'Orders',
      component: () => import('../views/OrderPage.vue'),
      beforeEnter: userGuard,
    },
    {
      path: '/dashboard',
      name: 'Dashboard',
      component: () => import('../views/admin/DashBoard.vue'),
      beforeEnter: adminGuard,
    },
    {
      path: '/dashboard/users',
      name: 'User',
      component: () => import('../views/admin/UserPage.vue'),
      beforeEnter: adminGuard,
    },
    {
      path: '/dashboard/categories',
      name: 'Category',
      component: () => import('../views/admin/CategoryPage.vue'),
      beforeEnter: adminGuard,
    },
    {
      path: '/dashboard/rooms',
      name: 'Room',
      component: () => import('../views/admin/RoomPage.vue'),
      beforeEnter: adminGuard,
    },
    {
      path: '/dashboard/products',
      name: 'Product',
      component: () => import('../views/admin/ProductPage.vue'),
      beforeEnter: adminGuard,
    },
    {
      path: '/dashboard/orders',
      name: 'Order',
      component: () => import('../views/admin/OrderPage.vue'),
      beforeEnter: adminGuard,
    },
    {
      path: '/dashboard/checks',
      name: 'Check',
      component: () => import('../views/admin/CheckPage.vue'),
      beforeEnter: adminGuard,
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('../views/NotFound.vue'),
    },
  ],
  scrollBehavior(to, from, savedPosition) {
    // If the user is using browser navigation (back/forward), restore their position
    if (savedPosition) {
      return savedPosition
    }

    // For all other navigation, scroll to top with smooth behavior
    return {
      top: 0,
      behavior: 'smooth',
    }
  },
})

export default router
