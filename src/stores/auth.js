import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const adminEmail = ref(localStorage.getItem('admin_email') || null)
  const adminName  = ref(localStorage.getItem('admin_name')  || null)

  const isLoggedIn = computed(() => !!adminEmail.value)

  async function login(email, password) {
    const { data } = await api.post('/api/auth.php', { email, password })
    if (data.success) {
      adminEmail.value = data.email || email
      adminName.value  = data.full_name || ''
      localStorage.setItem('admin_email', adminEmail.value)
      localStorage.setItem('admin_name',  adminName.value)
    }
    return data
  }

  async function logout() {
    try { await api.get('/api/auth.php?action=logout') } catch {}
    adminEmail.value = null
    adminName.value  = null
    localStorage.removeItem('admin_email')
    localStorage.removeItem('admin_name')
  }

  return { adminEmail, adminName, isLoggedIn, login, logout }
})

