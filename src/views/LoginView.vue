<template>
  <div class="login-page">
    <div class="login-background">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
    </div>

    <div class="login-card">
      <div class="login-header">
        <div class="logos">
          <img src="/assets/osas2.png" alt="OSAS" class="logo-img" onerror="this.style.opacity='.3'" />
          <img src="/assets/cigorig.png" alt="CIG" class="logo-img logo-center" onerror="this.style.opacity='.3'" />
          <img src="/assets/plsplogo.png" alt="PLSP" class="logo-img" onerror="this.style.opacity='.3'" />
        </div>
        <h2>Council of Internal Governance</h2>
        <p class="subtitle">Office of Student Affairs and Services</p>
      </div>

      <div class="login-body">
        <h3>Welcome Back!</h3>
        <p class="desc">Sign in to your account to continue</p>

        <div v-if="error" class="error-msg">{{ error }}</div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input id="email" v-model="email" type="email" placeholder="admin@cig.edu.ph" required />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" v-model="password" type="password" placeholder="Enter your password"
            required @keyup.enter="handleLogin" />
        </div>

        <button class="btn-login" :disabled="loading" @click="handleLogin">
          <i v-if="loading" class="fas fa-spinner fa-spin"></i>
          <span>{{ loading ? 'Signing in…' : 'Log In' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth   = useAuthStore()

const email    = ref('')
const password = ref('')
const error    = ref('')
const loading  = ref(false)

// Default hardcoded admin (mirrors login.php)
const ADMIN_EMAIL = 'cig@admin.com'
const ADMIN_PASS  = 'admincig123'

async function handleLogin() {
  error.value = ''
  if (!email.value || !password.value) { error.value = 'Please enter both email and password.'; return }
  loading.value = true
  // Simple credential check matching login.php
  if (email.value === ADMIN_EMAIL && password.value === ADMIN_PASS) {
    auth.adminEmail = email.value
    localStorage.setItem('admin_email', email.value)
    router.push('/')
  } else {
    error.value = 'Invalid admin credentials.'
  }
  loading.value = false
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #047857 0%, #10b981 25%, #0d6e4f 50%, #10b981 75%, #0d6e4f 100%);
  background-size: 300% 300%;
  animation: dynamicGradient 12s ease infinite;
  overflow: hidden;
  position: relative;
}
@keyframes dynamicGradient { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }

.login-background { position:fixed; inset:0; pointer-events:none; z-index:0; overflow:hidden; }
.blob { position:absolute; filter:blur(80px); opacity:0.3; border-radius:40% 60% 70% 30% / 40% 50% 60% 50%; }
.blob-1 { width:300px; height:300px; background:linear-gradient(135deg,#047857,#10b981); top:-100px; right:-50px; animation:float 7s ease-in-out infinite; opacity:0.4; }
.blob-2 { width:300px; height:300px; background:linear-gradient(135deg,#059669,#0d6e4f); bottom:-100px; left:-50px; animation:float 8s ease-in-out infinite reverse; opacity:0.4; }
@keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(30px)} }

.login-card {
  background:
    radial-gradient(ellipse 800px 200px at 20% 30%, rgba(102,126,234,0.15) 0%, transparent 50%),
    radial-gradient(ellipse 900px 250px at 80% 10%, rgba(240,147,251,0.15) 0%, transparent 50%),
    radial-gradient(ellipse 700px 180px at 50% 80%, rgba(74,252,254,0.15) 0%, transparent 50%),
    linear-gradient(135deg, rgba(255,255,255,0.5) 0%, rgba(248,249,250,0.4) 100%);
  border:1px solid rgba(255,255,255,0.3);
  border-radius:20px;
  padding:40px;
  width:90%;
  max-width:480px;
  box-shadow:inset 0 1px 0 rgba(255,255,255,0.4), 0 20px 50px rgba(102,126,234,0.2), 0 0 100px rgba(240,147,251,0.1);
  backdrop-filter:blur(20px);
  -webkit-backdrop-filter:blur(20px);
  position:relative;
  z-index:1;
  animation:slideUp 0.6s ease-out;
  overflow:hidden;
}
@keyframes slideUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }

.login-header { text-align:center; margin-bottom:28px; }
.logos { display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:16px; }
.logo-img { height:48px; object-fit:contain; background:rgba(255,255,255,0.9); border-radius:8px; padding:4px; }
.logo-center { height:60px; }
.logos .logo-img:first-child, .logos .logo-img:last-child { margin-top:12px; }
.login-header h2 { font-size:1.5em; font-weight:800; background:linear-gradient(135deg,#047857,#10b981,#059669); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; margin:0 0 4px; }
.login-header .subtitle { font-size:0.85em; color:#4b5664; margin:0; }

.login-body { }
.login-body h3 { text-align:center; font-size:1.3em; font-weight:700; color:#1a202c; margin:0 0 4px; }
.desc { text-align:center; color:#4b5664; font-size:0.88em; margin:0 0 22px; }

.error-msg { background:#ffebee; color:#d32f2f; padding:10px 14px; border-radius:8px; font-size:0.88em; margin-bottom:16px; text-align:center; }

.form-group { margin-bottom:16px; }
.form-group label { display:block; font-weight:600; font-size:0.88em; color:#2d3748; margin-bottom:6px; }
.form-group input { width:100%; padding:12px 14px; background:rgba(255,255,255,0.8); border:1.5px solid rgba(16,185,129,0.2); border-radius:10px; color:#1a202c; font-size:0.95em; font-family:'Poppins',sans-serif; transition:all 0.3s; }
.form-group input:focus { outline:none; border-color:#10b981; background:rgba(255,255,255,0.95); box-shadow:0 0 0 4px rgba(16,185,129,0.15),0 4px 20px rgba(16,185,129,0.2); transform:translateY(-2px); }

.btn-login { width:100%; padding:13px; margin-top:8px; background:linear-gradient(135deg,#10b981,#059669); color:white; border:none; border-radius:10px; font-size:0.97em; font-weight:700; font-family:'Poppins',sans-serif; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1); box-shadow:0 6px 20px rgba(16,185,129,0.3),inset 0 1px 0 rgba(255,255,255,0.2); position:relative; overflow:hidden; }
.btn-login::before { content:''; position:absolute; top:50%; left:50%; width:0; height:0; border-radius:50%; background:rgba(255,255,255,0.3); transform:translate(-50%,-50%); transition:width .6s,height .6s; }
.btn-login:hover::before { width:300px; height:300px; }
.btn-login:hover:not(:disabled) { transform:translateY(-4px); box-shadow:0 10px 30px rgba(16,185,129,0.4); }
.btn-login:disabled { opacity:0.7; cursor:not-allowed; }
</style>
