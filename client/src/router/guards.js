const checkAuthentication = async () => {
  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/auth/login.controller.php`,
      {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
      }
    )

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)
    const resData = await response.json()
    return resData?.data || null
  } catch (error) {
    return null
  }
}

let cachedUserData = null
let lastFetchTime = 0
const CACHE_DURATION = 5 * 60 * 1000 // 5 minutes cache

const baseGuard = async (to, from, next, roleCheck = null) => {
  const now = Date.now()
  if (!cachedUserData || now - lastFetchTime > CACHE_DURATION) {
    cachedUserData = await checkAuthentication()
    lastFetchTime = now
  }

  if (!cachedUserData) {
    return next('/login')
  }
  if (roleCheck && !roleCheck(cachedUserData.role)) {
    return next('/')
  }
  return next()
}

export const authGuard = async (to, from, next) => {
  return baseGuard(to, from, next)
}
export const userGuard = async (to, from, next) => {
  return baseGuard(to, from, next, (role) => role === 'User')
}
export const adminGuard = async (to, from, next) => {
  return baseGuard(to, from, next, (role) => role === 'Admin')
}
