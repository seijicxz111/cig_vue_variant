<template>
  <div>
    <div class="page-header">
      <h2><i class="fas fa-user-plus"></i> Create User Account</h2>
    </div>

    <div v-if="alert.msg" :class="['success-alert', alert.type === 'error' ? 'error-alert' : '']" style="margin-bottom:16px;">
      <i :class="['fas', alert.type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle']"></i>
      <span v-html="alert.msg"></span>
    </div>

    <div class="error-alert" style="margin-bottom:16px;">
      <i class="fas fa-shield-alt"></i>
      <span><strong>Security Notice:</strong> Restrict access to this page after creating accounts.</span>
    </div>

    <button class="btn-action btn-view" style="margin-bottom:20px;" @click="showCreate=true">
      <i class="fas fa-user-plus"></i> Create User &amp; Organization
    </button>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th><i class="fas fa-hashtag"></i> ID</th>
            <th><i class="fas fa-user"></i> Full Name</th>
            <th><i class="fas fa-at"></i> Username</th>
            <th><i class="fas fa-envelope"></i> Email</th>
            <th><i class="fas fa-user-tag"></i> Role</th>
            <th><i class="fas fa-info-circle"></i> Status</th>
            <th><i class="fas fa-calendar-alt"></i> Created</th>
            <th><i class="fas fa-cog"></i> Actions</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="users.length">
            <tr v-for="u in users" :key="u.user_id">
              <td class="ref-number">#{{ u.user_id }}</td>
              <td><strong>{{ u.full_name }}</strong></td>
              <td>{{ u.username }}</td>
              <td>{{ u.email }}</td>
              <td><span :class="['role-badge', u.role]">{{ capitalize(u.role) }}</span></td>
              <td><span :class="['status-badge', u.status]"><i class="fas fa-circle" style="font-size:7px;"></i> {{ capitalize(u.status) }}</span></td>
              <td>{{ formatDate(u.created_at) }}</td>
              <td>
                <div class="action-buttons">
                  <button v-if="u.status === 'active'" class="btn-action btn-warning" @click="confirm('deactivate', u)">
                    <i class="fas fa-ban"></i> Deactivate
                  </button>
                  <button v-else class="btn-action btn-view" @click="confirm('activate', u)">
                    <i class="fas fa-check"></i> Activate
                  </button>
                  <button class="btn-action btn-danger" @click="confirm('delete', u)">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </div>
              </td>
            </tr>
          </template>
          <tr v-else class="empty-row"><td colspan="8">No users found</td></tr>
        </tbody>
      </table>
    </div>

    <!-- CREATE MODAL -->
    <div v-if="showCreate" class="modal-backdrop" @click.self="showCreate=false">
      <div class="modal-box">
        <div class="modal-header">
          <div><h3><i class="fas fa-user-plus"></i> Create User &amp; Organization</h3><p>Fill in the details below</p></div>
          <button @click="showCreate=false" class="modal-close">✕</button>
        </div>
        <div class="modal-body">
          <div class="section-label"><i class="fas fa-link"></i> Shared Information</div>
          <div class="form-row">
            <div><label>Full Name <span>*</span></label><input v-model="form.full_name" type="text" placeholder="Maria Santos" /></div>
            <div><label>Email <span>*</span></label><input v-model="form.email" type="email" placeholder="maria@cig.edu.ph" /></div>
          </div>
          <div><label>Phone</label><input v-model="form.phone" type="tel" placeholder="+63-123-456-7890" /></div>

          <div class="section-label"><i class="fas fa-user"></i> User Account</div>
          <div class="form-row">
            <div><label>Username <span>*</span></label><input v-model="form.username" type="text" placeholder="mariasantos" /></div>
            <div><label>Role <span>*</span></label>
              <select v-model="form.role">
                <option value="user">User (Organization Member)</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div><label>Password <span>*</span></label><input v-model="form.password" type="password" placeholder="Min. 8 characters" /></div>
            <div><label>Confirm Password <span>*</span></label><input v-model="form.confirm" type="password" placeholder="Repeat password" /></div>
          </div>

          <div class="section-label"><i class="fas fa-building"></i> Organization</div>
          <div class="form-row">
            <div><label>Org Name <span>*</span></label><input v-model="form.org_name" type="text" placeholder="Student Government Association" /></div>
            <div><label>Code <span>*</span></label><input v-model="form.org_code" type="text" placeholder="SGA" /></div>
          </div>
          <div><label>Description</label><textarea v-model="form.description" rows="2" placeholder="Brief description…"></textarea></div>
        </div>
        <div class="modal-footer">
          <button class="btn-action" style="background:#f0f0f0;color:#4a5568;" @click="showCreate=false">Cancel</button>
          <button class="btn-action btn-view" @click="createUser">
            <i class="fas fa-user-plus"></i> Create
          </button>
        </div>
      </div>
    </div>

    <!-- CONFIRM MODAL -->
    <div v-if="confirmModal.show" class="modal-backdrop" @click.self="confirmModal.show=false">
      <div class="modal-box" style="max-width:400px;text-align:center;">
        <div style="font-size:3rem;margin:24px 0 12px;">{{ confirmModal.icon }}</div>
        <h3 :style="{ color: confirmModal.action === 'delete' ? '#c0392b' : '#1a202c', marginBottom:'8px' }">{{ confirmModal.title }}</h3>
        <p style="color:#555;margin-bottom:24px;font-size:0.9em;" v-html="confirmModal.msg"></p>
        <div style="display:flex;gap:10px;justify-content:center;padding:0 24px 24px;">
          <button class="btn-action" style="background:#f0f0f0;color:#4a5568;" @click="confirmModal.show=false">Cancel</button>
          <button class="btn-action" :class="confirmModal.btnClass" @click="doAction">{{ confirmModal.label }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/api'

const users      = ref([])
const showCreate = ref(false)
const alert      = reactive({ msg: '', type: '' })

const form = reactive({ full_name:'', email:'', phone:'', username:'', role:'user', password:'', confirm:'', org_name:'', org_code:'', description:'' })

const confirmModal = reactive({ show:false, action:'', user:null, icon:'', title:'', msg:'', label:'', btnClass:'' })

onMounted(loadUsers)

async function loadUsers() {
  try {
    const { data } = await api.get('/api/create_user.php?action=list&type=orgs')
    users.value = data?.users || []
  } catch {}
}

async function createUser() {
  alert.msg = ''; alert.type = ''
  const errors = []
  if (!form.full_name) errors.push('Full name required.')
  if (!form.email) errors.push('Email required.')
  if (!form.username) errors.push('Username required.')
  if (!form.password) errors.push('Password required.')
  if (form.password !== form.confirm) errors.push('Passwords do not match.')
  if (form.password.length > 0 && form.password.length < 8) errors.push('Password must be ≥ 8 characters.')
  if (!form.org_name) errors.push('Organization name required.')
  if (!form.org_code) errors.push('Organization code required.')
  if (errors.length) { alert.msg = errors.join('<br>'); alert.type = 'error'; return }

  try {
    const { data } = await api.post('/api/create_user.php?action=create', form)
    if (data?.success) {
      alert.msg  = `User <strong>${form.full_name}</strong> created successfully!`
      alert.type = 'success'
      showCreate.value = false
      Object.assign(form, { full_name:'', email:'', phone:'', username:'', role:'user', password:'', confirm:'', org_name:'', org_code:'', description:'' })
      loadUsers()
    } else {
      alert.msg = data?.message || 'Failed to create user.'; alert.type = 'error'
    }
  } catch { alert.msg = 'Server error.'; alert.type = 'error' }
}

const CONFIGS = {
  delete:     { icon:'🗑️', title:'Delete User',        btnClass:'btn-action btn-danger',   label:'Yes, Delete',  msg: n => `Permanently delete <strong>${n}</strong>? This cannot be undone.` },
  deactivate: { icon:'🔒', title:'Deactivate Account', btnClass:'btn-action btn-warning',  label:'Deactivate',   msg: n => `Deactivating <strong>${n}</strong> will prevent login.` },
  activate:   { icon:'✅', title:'Activate Account',   btnClass:'btn-action btn-view',     label:'Activate',     msg: n => `Restore login access for <strong>${n}</strong>?` },
}

function confirm(action, user) {
  const cfg = CONFIGS[action]
  Object.assign(confirmModal, { show:true, action, user, icon:cfg.icon, title:cfg.title, msg:cfg.msg(user.full_name), label:cfg.label, btnClass:cfg.btnClass })
}

async function doAction() {
  const { action, user } = confirmModal
  try {
    await api.post('/api/user_action.php', { action, user_id: user.user_id })
    if (action === 'delete') users.value = users.value.filter(u => u.user_id !== user.user_id)
    else { const u = users.value.find(u => u.user_id === user.user_id); if (u) u.status = action === 'activate' ? 'active' : 'inactive' }
  } catch {}
  confirmModal.show = false
}

function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : '—' }
function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }
</script>

<style scoped>
.role-badge { display:inline-block; padding:5px 12px; border-radius:20px; font-size:0.78em; font-weight:600; text-transform:uppercase; letter-spacing:0.4px; }
.role-badge.admin { background:#ede9fe; color:#6d28d9; }
.role-badge.user  { background:#d1fae5; color:#047857; }

.section-label { font-size:0.78em; font-weight:700; text-transform:uppercase; letter-spacing:0.7px; color:#047857; background:linear-gradient(90deg,rgba(16,185,129,0.1),transparent); border-left:3px solid #10b981; padding:5px 10px; border-radius:0 6px 6px 0; margin:14px 0 10px; display:flex; align-items:center; gap:6px; }

.modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
.modal-box { background:white; border-radius:14px; width:90%; max-width:600px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.25); max-height:92vh; display:flex; flex-direction:column; }
.modal-header { background:linear-gradient(135deg,#047857,#10b981); color:white; padding:20px 24px; display:flex; justify-content:space-between; align-items:flex-start; flex-shrink:0; }
.modal-header h3 { margin:0 0 4px; font-size:1.05em; font-weight:700; }
.modal-header p { margin:0; font-size:0.8em; opacity:0.85; }
.modal-close { background:rgba(255,255,255,.2); border:none; color:white; width:32px; height:32px; border-radius:50%; cursor:pointer; font-size:1em; flex-shrink:0; }
.modal-body { padding:22px 24px; overflow-y:auto; display:flex; flex-direction:column; gap:10px; }
.modal-body label { font-weight:600; font-size:0.85em; color:#2d3748; display:block; margin-bottom:4px; }
.modal-body label span { color:#dc3545; }
.modal-body input, .modal-body select, .modal-body textarea { width:100%; padding:10px 12px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:0.9em; font-family:'Poppins',sans-serif; }
.modal-body input:focus, .modal-body select:focus, .modal-body textarea:focus { outline:none; border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,0.1); }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.modal-footer { padding:16px 24px; border-top:1px solid #f0f0f0; display:flex; justify-content:flex-end; gap:10px; flex-shrink:0; }
</style>
