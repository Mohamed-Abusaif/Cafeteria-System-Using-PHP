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
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('../views/NotFound.vue'),
    }
  ],
})

export default router
