<template>
  <div>
    <!-- Welcome Section -->
    <div class="welcome-section">
      <div class="welcome-content">
        <h1>Welcome to OSAS</h1>
        <p class="welcome-subtitle">Office of Student Affairs and Services</p>
        <p class="welcome-desc">Manage submissions, reviews, and organizational governance with ease.</p>
      </div>
      <div class="welcome-stats">
        <div class="stat-item">
          <span class="stat-number">{{ stats.orgs }}</span>
          <span class="stat-label">Organizations</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ stats.submissions }}</span>
          <span class="stat-label">Submissions</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ stats.approvalRate }}%</span>
          <span class="stat-label">Approval Rate</span>
        </div>
      </div>
    </div>

    <!-- Announcements -->
    <div class="ann-board">
      <div class="ann-board-inner">
        <div class="ann-board-header">
          <div class="ann-header-left">
            <div class="ann-icon"><i class="fas fa-bell"></i></div>
            <div>
              <h3>Latest Announcements</h3>
              <span class="ann-sub">Important updates and notices</span>
            </div>
          </div>
          <button class="btn-action btn-view" @click="openAdd">
            <i class="fas fa-plus"></i> Add
          </button>
        </div>

        <div class="ann-list">
          <div v-if="announcements.length === 0" class="ann-empty">No announcements yet.</div>
          <div v-for="ann in announcements" :key="ann.announcement_id"
            class="ann-item" :class="{ pinned: ann.is_pinned }">
            <div v-if="ann.is_pinned" class="pin-label"><i class="fas fa-thumbtack"></i> Pinned</div>
            <div class="ann-item-header">
              <h4 class="ann-title">
                <span class="badge" :style="catStyle(ann.category)">{{ catLabel(ann.category) }}</span>
                <span class="badge" :style="priStyle(ann.priority)">{{ priLabel(ann.priority) }}</span>
                {{ ann.title }}
              </h4>
              <div class="ann-actions">
                <button class="ann-btn edit" @click="openEdit(ann)"><i class="fas fa-edit"></i></button>
                <button class="ann-btn del"  @click="confirmDelete(ann)"><i class="fas fa-trash"></i></button>
              </div>
            </div>
            <p class="ann-content">{{ ann.content }}</p>
            <div class="ann-meta">
              <small><i class="far fa-calendar-alt"></i> {{ formatDate(ann.created_at) }}</small>
              <small v-if="ann.audience" style="color:#1d4ed8;font-weight:600;">
                <i class="fas fa-users"></i> {{ ann.audience }}
              </small>
              <small v-else style="color:#aaa;"><i class="fas fa-globe"></i> All orgs</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mission/Vision/Values -->
    <div class="values-grid">
      <div class="value-card mission">
        <div class="card-img" style="background:linear-gradient(135deg,#1e90ff,#00bfff);">
          <div class="card-circle"><i class="fas fa-rocket" style="color:#1e90ff;font-size:44px;"></i></div>
        </div>
        <div class="card-label"><h3>MISSION</h3></div>
        <p>To strengthen the capability of organizations through collaboration and active participation in school governance.</p>
      </div>
      <div class="value-card vision">
        <div class="card-img" style="background:linear-gradient(135deg,#ff6b6b,#ff1744);">
          <div class="card-circle"><i class="fas fa-eye" style="color:#ff6b6b;font-size:44px;"></i></div>
        </div>
        <div class="card-label"><h3>VISION</h3></div>
        <p>A highly trusted organization committed to capacitating progressive communities.</p>
      </div>
      <div class="value-card values">
        <div class="card-img" style="background:linear-gradient(135deg,#ff9500,#ff6f00);">
          <div class="card-circle"><i class="fas fa-heart" style="color:#ff9500;font-size:44px;"></i></div>
        </div>
        <div class="card-label"><h3>VALUES</h3></div>
        <p><strong>SERVICE</strong> — Dedicated to serving our communities<br><strong>VOLUNTEERISM</strong> — Active participation and commitment</p>
      </div>
    </div>

    <!-- ADD/EDIT MODAL -->
    <div v-if="showModal" class="modal-backdrop" @click.self="showModal=false">
      <div class="modal-box">
        <div class="modal-header">
          <h3>{{ editingId ? 'Edit' : 'Add' }} Announcement</h3>
          <button @click="showModal=false" class="modal-close">✕</button>
        </div>
        <div class="modal-body">
          <label>Title</label>
          <input v-model="form.title" type="text" placeholder="Announcement title…" />
          <label>Content</label>
          <textarea v-model="form.content" rows="4" placeholder="Content…"></textarea>
          <div class="modal-row">
            <div>
              <label>Priority</label>
              <select v-model="form.priority">
                <option value="low">🔵 Low</option>
                <option value="high">🟡 High</option>
                <option value="urgent">🔴 Urgent</option>
              </select>
            </div>
            <div>
              <label>Category</label>
              <select v-model="form.category">
                <option value="general">📋 General</option>
                <option value="event">📅 Event</option>
                <option value="deadline">⏰ Deadline</option>
                <option value="policy">📜 Policy</option>
              </select>
            </div>
          </div>
          <label>Target Audience <span style="color:#888;font-weight:400;font-size:12px;">(blank = all)</span></label>
          <input v-model="form.audience" type="text" placeholder="e.g. CSC,SSG — blank for all" />
          <label>Expiry Date <span style="color:#888;font-weight:400;font-size:12px;">(optional)</span></label>
          <input v-model="form.expires_at" type="date" />
          <label class="pin-check">
            <input v-model="form.is_pinned" type="checkbox" /> 📌 Pin to top
          </label>
        </div>
        <div class="modal-footer">
          <button class="btn-action" style="background:#f0f0f0;color:#4a5568;" @click="showModal=false">Cancel</button>
          <button class="btn-action btn-view" @click="saveAnnouncement">
            <i class="fas fa-save"></i> Save
          </button>
        </div>
      </div>
    </div>

    <!-- DELETE CONFIRM -->
    <div v-if="showDelete" class="modal-backdrop" @click.self="showDelete=false">
      <div class="modal-box" style="max-width:380px;text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:12px;">🗑️</div>
        <h3 style="color:#c0392b;margin-bottom:8px;">Delete Announcement?</h3>
        <p style="color:#555;margin-bottom:20px;font-size:0.9em;">This cannot be undone.</p>
        <div style="display:flex;gap:10px;justify-content:center;">
          <button class="btn-action" style="background:#f0f0f0;color:#4a5568;" @click="showDelete=false">Cancel</button>
          <button class="btn-action btn-danger" @click="doDelete"><i class="fas fa-trash"></i> Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/api'

const stats = reactive({ orgs: 0, submissions: 0, approvalRate: 0 })
const announcements = ref([])
const showModal  = ref(false)
const showDelete = ref(false)
const editingId  = ref(null)
const deleteTarget = ref(null)

const form = reactive({ title: '', content: '', priority: 'low', category: 'general', audience: '', expires_at: '', is_pinned: false })

onMounted(async () => {
  try {
    const { data } = await api.get('/api/home_stats.php')
    if (data) { stats.orgs = data.orgs || 0; stats.submissions = data.submissions || 0; stats.approvalRate = data.approval_rate || 0 }
  } catch {}
  try {
    const { data } = await api.get('/api/get_announcements.php')
    if (data?.announcements) announcements.value = data.announcements
  } catch {}
})

function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : '' }

const PRIORITY = { urgent:{label:'Urgent',color:'#c0392b',bg:'#fde8e8'}, high:{label:'High',color:'#b7770d',bg:'#fff3cd'}, low:{label:'Low',color:'#555',bg:'#f0f0f0'} }
const CATEGORY = { event:{label:'Event',color:'#1d4ed8',bg:'#dbeafe'}, deadline:{label:'Deadline',color:'#b91c1c',bg:'#fee2e2'}, policy:{label:'Policy',color:'#6d28d9',bg:'#ede9fe'}, general:{label:'General',color:'#065f46',bg:'#d1fae5'} }

const priStyle  = (p) => { const s = PRIORITY[p]||PRIORITY.low;   return { background:s.bg, color:s.color } }
const priLabel  = (p) => (PRIORITY[p]||PRIORITY.low).label
const catStyle  = (c) => { const s = CATEGORY[c]||CATEGORY.general; return { background:s.bg, color:s.color } }
const catLabel  = (c) => (CATEGORY[c]||CATEGORY.general).label

function resetForm() { Object.assign(form, { title:'', content:'', priority:'low', category:'general', audience:'', expires_at:'', is_pinned:false }); editingId.value = null }

function openAdd() { resetForm(); showModal.value = true }
function openEdit(ann) {
  editingId.value = ann.announcement_id
  Object.assign(form, { title:ann.title, content:ann.content, priority:ann.priority||'low', category:ann.category||'general', audience:ann.audience||'', expires_at:ann.expires_at||'', is_pinned:!!ann.is_pinned })
  showModal.value = true
}
function confirmDelete(ann) { deleteTarget.value = ann; showDelete.value = true }

async function saveAnnouncement() {
  if (!form.title || !form.content) return
  try {
    const fd = new FormData()
    fd.append('title',    form.title)
    fd.append('content',  form.content)
    fd.append('priority', form.priority)
    fd.append('category', form.category)
    fd.append('audience', form.audience || '')
    fd.append('expires_at', form.expires_at || '')
    fd.append('is_pinned', form.is_pinned ? '1' : '0')
    if (editingId.value) fd.append('announcement_id', editingId.value)

    const { data } = await api.post('/api/save_announcement.php', fd)
    if (data?.success) {
      if (editingId.value) {
        const idx = announcements.value.findIndex(a => a.announcement_id == editingId.value)
        if (idx !== -1) announcements.value[idx] = {
          ...announcements.value[idx],
          title: data.title, content: data.content,
          priority: data.priority, category: data.category,
          audience: data.audience, expires_at: data.expires_at,
          is_pinned: data.is_pinned
        }
      } else {
        announcements.value.unshift({
          announcement_id: data.id,
          title: data.title, content: data.content,
          priority: data.priority, category: data.category,
          audience: data.audience, expires_at: data.expires_at,
          is_pinned: data.is_pinned,
          created_at: data.created_at || new Date().toISOString()
        })
      }
      showModal.value = false
    }
  } catch {}
}

async function doDelete() {
  try {
    const fd = new FormData()
    fd.append('action', 'delete')
    fd.append('announcement_id', deleteTarget.value.announcement_id)
    await api.post('/api/save_announcement.php', fd)
    announcements.value = announcements.value.filter(a => a.announcement_id !== deleteTarget.value.announcement_id)
  } catch {}
  showDelete.value = false
}
</script>

<style scoped>
/* Welcome */
.welcome-section { background: linear-gradient(135deg, #047857 0%, #10b981 60%, #059669 100%); border-radius: 16px; padding: 36px 40px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; gap: 20px; box-shadow: 0 8px 32px rgba(16,185,129,0.25); color: white; flex-wrap: wrap; }
.welcome-content h1 { font-size: 2em; font-weight: 800; margin: 0 0 4px; }
.welcome-subtitle { font-size: 1em; opacity: 0.9; margin: 0 0 10px; }
.welcome-desc { font-size: 0.88em; opacity: 0.8; margin: 0; max-width: 420px; line-height: 1.6; }
.welcome-stats { display: flex; gap: 30px; flex-wrap: wrap; }
.stat-item { text-align: center; }
.stat-number { display: block; font-size: 2.2em; font-weight: 800; }
.stat-label { font-size: 0.75em; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; }

/* Announcements */
.ann-board { background: rgba(255,255,255,0.95); border-radius: 16px; padding: 28px; margin-bottom: 30px; box-shadow: 0 8px 24px rgba(16,185,129,0.1); }
.ann-board-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.ann-header-left { display: flex; align-items: center; gap: 14px; }
.ann-icon { width: 44px; height: 44px; background: linear-gradient(135deg, #10b981, #047857); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2em; }
.ann-board-header h3 { margin: 0; font-size: 1.1em; font-weight: 700; color: #1a202c; }
.ann-sub { font-size: 0.8em; color: #718096; }
.ann-list { display: flex; flex-direction: column; gap: 10px; }
.ann-empty { color: #9ca3af; text-align: center; padding: 20px; font-size: 0.9em; }

.ann-item { background: #fff; border: 1px solid #e8f0ec; border-radius: 10px; padding: 14px 16px; transition: box-shadow 0.2s; }
.ann-item:hover { box-shadow: 0 3px 12px rgba(0,0,0,0.07); }
.ann-item.pinned { border-left: 4px solid #f59e0b; background: #fffdf5; }
.pin-label { font-size: 0.72rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 2px 10px; border-radius: 20px; margin-bottom: 8px; display: inline-flex; align-items: center; gap: 5px; }
.ann-item-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px; }
.ann-title { margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e3a3a; display: flex; flex-wrap: wrap; align-items: center; gap: 6px; flex: 1; line-height: 1.5; }
.badge { font-size: 0.68rem; font-weight: 700; padding: 2px 9px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap; }
.ann-actions { display: flex; gap: 6px; flex-shrink: 0; }
.ann-btn { border: none; border-radius: 6px; padding: 5px 9px; cursor: pointer; font-size: 13px; transition: opacity 0.2s, transform 0.1s; }
.ann-btn:hover { opacity: 0.8; transform: translateY(-1px); }
.ann-btn.edit { background: #e3f2eb; color: #2d6a4f; }
.ann-btn.del  { background: #fde8e8; color: #c0392b; }
.ann-content { margin: 0 0 8px; color: #4a5568; font-size: 0.88rem; line-height: 1.6; }
.ann-meta { display: flex; flex-wrap: wrap; gap: 10px; padding-top: 8px; border-top: 1px solid #f0f0f0; }
.ann-meta small { color: #888; font-size: 0.77rem; display: inline-flex; align-items: center; gap: 4px; }

/* Values */
.values-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 28px; margin-top: 10px; }
.value-card { background: white; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; }
.value-card:hover { transform: translateY(-8px); box-shadow: 0 16px 40px rgba(0,0,0,0.18); }
.card-img { height: 200px; display: flex; align-items: center; justify-content: center; }
.card-circle { width: 120px; height: 120px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 28px rgba(0,0,0,0.15); }
.card-label { padding: 18px 20px 10px; text-align: center; border-bottom: 2px solid #f0f0f0; }
.card-label h3 { margin: 0; font-size: 1.5em; font-weight: 800; color: #1a202c; letter-spacing: 1px; }
.value-card p { padding: 18px 20px; margin: 0; color: #4a5568; font-size: 0.88em; line-height: 1.7; text-align: center; }

/* Modal */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.modal-box { background: white; border-radius: 14px; width: 90%; max-width: 540px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.25); }
.modal-header { background: linear-gradient(135deg, #047857, #10b981); color: white; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; font-size: 1.1em; font-weight: 700; }
.modal-close { background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1em; }
.modal-body { padding: 22px 24px; display: flex; flex-direction: column; gap: 8px; max-height: 62vh; overflow-y: auto; }
.modal-body label { font-weight: 600; font-size: 0.88em; color: #2d3748; }
.modal-body input, .modal-body textarea, .modal-body select { padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.9em; font-family: 'Poppins', sans-serif; width: 100%; }
.modal-body input:focus, .modal-body textarea:focus, .modal-body select:focus { outline: none; border-color: #10b981; }
.modal-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.pin-check { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.pin-check input { width: auto; }
.modal-footer { padding: 16px 24px; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; gap: 10px; }
</style>
