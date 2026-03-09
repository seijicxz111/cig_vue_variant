<template>
  <div class="app-shell">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <RouterLink to="/" class="logo-link" :class="{ active: route.name === 'Home' }">
        <img src="/assets/osas2.png" alt="OSAS Logo" class="logo" onerror="this.style.display='none'" />
      </RouterLink>

      <nav class="nav-links">
        <RouterLink v-for="link in navLinks" :key="link.to"
          :to="link.to"
          class="nav-link"
          :class="{ active: route.path === link.to }"
        >
          <i :class="link.icon"></i>
          <span>{{ link.label }}</span>
        </RouterLink>
      </nav>

      <div class="sidebar-footer">
        <button class="logout-btn" @click="handleLogout">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- MAIN AREA -->
    <div class="main">
      <!-- TOPBAR -->
      <header class="topbar">
        <div class="topbar-left">
          <div id="cig">
            Office of Student Affairs and Services
            <p class="cig-subtitle">Pamantasan ng Lungsod ng San Pablo</p>
          </div>
        </div>
        <div class="topbar-right">
          <div class="notification-bell" @click="toggleNotifications">
            <span class="bell-icon">🔔</span>
            <span class="notification-badge">0</span>
          </div>
          <div class="user-label"><i class="fas fa-user-circle"></i> {{ auth.adminName || auth.adminEmail }}</div>
        </div>
      </header>

      <!-- NOTIFICATION PANEL -->
      <div class="notification-panel" :class="{ show: notifOpen }">
        <div class="notification-header">
          <h4>Notifications</h4>
          <button class="close-notification" @click="notifOpen = false">✕</button>
        </div>
        <div class="notification-list">
          <div class="notification-item">
            <p style="text-align:center;color:#999;">No new notifications</p>
          </div>
        </div>
      </div>

      <!-- PAGE CONTENT -->
      <div class="page-content">
        <RouterView />
      </div>

      <!-- FOOTER -->
      <footer class="footer">
        <div class="footer-content">
          <p>© 2026 Council of Internal Governance. All rights reserved.</p>
          <p class="footer-subtext">Designed for efficient governance operations</p>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter, RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()

const notifOpen = ref(false)
function toggleNotifications() { notifOpen.value = !notifOpen.value }

async function handleLogout() {
  auth.logout()
  router.push('/login')
}

const navLinks = [
  { to: '/',            label: 'Home',             icon: 'fas fa-home' },
  { to: '/dashboard',   label: 'Dashboard',        icon: 'fas fa-chart-line' },
  { to: '/submissions', label: 'Submissions',      icon: 'fas fa-file-alt' },
  { to: '/review',      label: 'Review & Approval',icon: 'fas fa-tasks' },
  { to: '/archive',     label: 'Document Archive', icon: 'fas fa-archive' },
  { to: '/create-user', label: 'Create User',      icon: 'fas fa-user-plus' },
]
</script>

<style scoped>
.app-shell {
  display: flex;
  min-height: 100vh;
}

/* ── SIDEBAR ── */
.sidebar {
  width: 240px;
  background: linear-gradient(180deg, #ffffff 0%, #f0fdf9 50%, #e8f5f0 100%);
  padding: 20px;
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: sticky;
  top: 0;
  overflow-y: auto;
  box-shadow: 2px 0 25px rgba(16,185,129,0.1);
  border-right: 1px solid rgba(16,185,129,0.1);
  z-index: 99;
}

.logo-link { display: block; padding: 8px; border-radius: 10px; transition: all 0.3s; margin-bottom: 20px; }
.logo-link.active { background: rgba(16,185,129,0.15); border-left: 3px solid #10b981; }
.logo { width: 100%; max-width: 180px; display: block; margin: 0 auto; }

.nav-links { display: flex; flex-direction: column; gap: 4px; flex: 1; }

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 15px;
  border-radius: 10px;
  text-decoration: none;
  color: #2d3748;
  font-size: 0.88em;
  font-weight: 500;
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.nav-link i { width: 22px; text-align: center; font-size: 1em; }
.nav-link:hover {
  background: rgba(16,185,129,0.12);
  color: #10b981;
  padding-left: 20px;
}
.nav-link.active {
  background: linear-gradient(90deg, #10b981, #059669);
  color: white;
  box-shadow: 0 6px 20px rgba(16,185,129,0.4);
}

.sidebar-footer {
  margin-top: auto;
  padding-top: 20px;
  border-top: 2px solid rgba(16,185,129,0.15);
}
.logout-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 15px;
  border-radius: 10px;
  border: 1.5px solid #ef4444;
  background: transparent;
  color: #ef4444;
  font-size: 0.9em;
  font-weight: 600;
  font-family: 'Poppins', sans-serif;
  cursor: pointer;
  transition: all 0.3s;
}
.logout-btn:hover {
  background: linear-gradient(90deg, #ef4444, #dc2626);
  color: white;
  border-color: #dc2626;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239,68,68,0.3);
}

/* ── MAIN ── */
.main { flex: 1; display: flex; flex-direction: column; min-width: 0; }

.topbar {
  background: #fcfbfc;
  padding: 18px 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 4px 15px rgba(0,0,0,0.08);
  border-bottom: 2px solid rgba(200,210,220,0.5);
  position: sticky;
  top: 0;
  z-index: 98;
}
.topbar-left { display: flex; align-items: center; }
.topbar-right { display: flex; align-items: center; gap: 20px; }

#cig { font-size: 1em; font-weight: 700; color: #2d3748; }
.cig-subtitle { font-size: 0.72em; font-weight: 500; color: #4a5568; margin-top: 3px; }
.user-label { font-size: 0.85em; font-weight: 600; color: #2d3748; }

.notification-bell { position: relative; cursor: pointer; }
.bell-icon { font-size: 1.4em; }
.notification-badge {
  position: absolute;
  top: -8px; right: -8px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  border-radius: 50%;
  width: 20px; height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7em;
  font-weight: 700;
}

.notification-panel {
  position: absolute;
  top: 65px; right: 30px;
  width: 340px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.15);
  display: none;
  flex-direction: column;
  z-index: 999;
  max-height: 400px;
  overflow: hidden;
}
.notification-panel.show { display: flex; }
.notification-header {
  padding: 16px 18px;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.notification-header h4 { margin: 0; font-size: 1em; color: #1a202c; }
.close-notification { background: none; border: none; cursor: pointer; font-size: 1em; color: #2d3748; }
.notification-list { overflow-y: auto; }
.notification-item { padding: 16px 18px; text-align: center; color: #9ca3af; font-size: 0.88em; }

/* ── PAGE CONTENT ── */
.page-content {
  flex: 1;
  padding: 30px 40px;
  background: linear-gradient(-45deg, #d1fae5 0%, #c1fada 15%, #d1fae5 30%, #c7f0dd 50%, #baf3d0 70%, #c1fada 85%, #d1fae5 100%);
  background-size: 300% 300%;
  animation: waveBackground 12s ease infinite;
}
@keyframes waveBackground {
  0%   { background-position: 0% 50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* ── FOOTER ── */
.footer {
  background: linear-gradient(135deg, #047857 0%, #059669 100%);
  color: white;
  text-align: center;
  padding: 14px 10px;
  border-top: 2px solid #10b981;
  font-size: 0.78em;
}
.footer p { margin: 4px 0; }
.footer-subtext { color: rgba(255,255,255,0.7); font-size: 0.9em; }

@media (max-width: 768px) {
  .sidebar { width: 60px; padding: 12px 8px; }
  .sidebar span { display: none; }
  .page-content { padding: 20px; }
}
</style>
