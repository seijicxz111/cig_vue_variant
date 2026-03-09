<template>
  <div>
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
      <h2><i class="fas fa-tasks"></i> Organization Management &amp; Reviews</h2>
      <div v-if="view === 'folders'" class="search-input-wrapper" style="max-width:300px;">
        <i class="fas fa-search search-icon"></i>
        <input v-model="orgSearch" type="text" placeholder="Search organizations…" class="search-input" />
      </div>
    </div>

    <!-- SUCCESS -->
    <div v-if="successMsg" class="success-alert"><i class="fas fa-check-circle"></i> {{ successMsg }}</div>

    <!-- ── FOLDERS VIEW ── -->
    <div v-if="view === 'folders'" class="folders-grid">
      <a v-for="org in filteredOrgs" :key="org.org_id" class="folder-card" @click="selectOrg(org)">
        <div class="folder-icon"><i class="fas fa-folder"></i></div>
        <p class="folder-name">{{ org.org_name }}</p>
        <p class="folder-code">{{ org.org_code }}</p>
        <span class="stat-badge"><i class="fas fa-file-alt"></i> {{ org.pending || 0 }} pending</span>
        <div class="hover-arrow"><i class="fas fa-chevron-right"></i></div>
      </a>
      <div v-for="n in emptySlots" :key="'e'+n" class="folder-card folder-empty">
        <div class="folder-icon"><i class="fas fa-folder"></i></div>
        <p class="folder-name">Empty Slot</p>
        <p class="folder-code">--</p>
      </div>
    </div>

    <!-- ── ORG TABLE VIEW ── -->
    <template v-if="view === 'org'">
      <div class="breadcrumb">
        <a @click="view='folders'"><i class="fas fa-folder-open"></i> Organizations</a>
        <i class="fas fa-chevron-right"></i>
        <span><i class="fas fa-building"></i> {{ currentOrg?.org_name }}</span>
      </div>
      <div class="org-header">
        <div class="org-icon"><i class="fas fa-building"></i></div>
        <div class="org-info">
          <div class="org-name-row">
            <h3>{{ currentOrg?.org_name }}</h3>
            <span class="org-badge">ACTIVE</span>
          </div>
          <span class="org-code-tag"><i class="fas fa-code"></i> {{ currentOrg?.org_code }}</span>
        </div>
        <button class="btn-action btn-download" @click="showOrgInfo=true"><i class="fas fa-info-circle"></i> Org Info</button>
      </div>
      <div class="search-filter-container">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input v-model="subSearch" type="text" placeholder="Search submissions…" class="search-input" />
        </div>
      </div>
      <div class="table-container">
        <table>
          <thead><tr>
            <th><i class="fas fa-hashtag"></i> Ref #</th>
            <th><i class="fas fa-file-alt"></i> Title</th>
            <th><i class="fas fa-tag"></i> Status</th>
            <th><i class="fas fa-user"></i> Submitted By</th>
            <th><i class="fas fa-calendar"></i> Date</th>
            <th><i class="fas fa-cog"></i> Action</th>
          </tr></thead>
          <tbody>
            <template v-if="filteredSubs.length">
              <tr v-for="(sub,i) in filteredSubs" :key="sub.submission_id">
                <td class="ref-number">#{{ pad(i+1) }}</td>
                <td><strong>{{ sub.title }}</strong></td>
                <td><span :class="['status-badge', sub.status]">{{ formatStatus(sub.status) }}</span></td>
                <td>{{ sub.full_name || 'N/A' }}</td>
                <td>{{ formatDate(sub.submitted_at) }}</td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-action btn-view" @click="selectSubmission(sub)">
                      <i class="fas fa-eye"></i> Review
                    </button>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-else class="empty-row"><td colspan="6">No submissions found</td></tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ── SUBMISSION DETAIL VIEW ── -->
    <template v-if="view === 'detail' && currentSub">
      <div class="breadcrumb">
        <a @click="view='folders'"><i class="fas fa-folder-open"></i> Organizations</a>
        <i class="fas fa-chevron-right"></i>
        <a @click="view='org'"><i class="fas fa-building"></i> {{ currentOrg?.org_name }}</a>
        <i class="fas fa-chevron-right"></i>
        <span><i class="fas fa-file"></i> {{ currentSub.title }}</span>
      </div>

      <div class="detail-card">
        <div class="detail-header">
          <h3>{{ currentSub.title }}</h3>
          <span :class="['status-badge', currentSub.status]">{{ formatStatus(currentSub.status) }}</span>
        </div>
        <div class="meta-grid">
          <div><label>Organization</label><p>{{ currentSub.org_name }}</p></div>
          <div><label>Submitted By</label><p>{{ currentSub.full_name }}</p></div>
          <div><label>Submitted At</label><p>{{ formatDate(currentSub.submitted_at) }}</p></div>
        </div>
        <h4 style="margin:0 0 8px;color:#1a202c;">Description</h4>
        <p style="color:#4a5568;line-height:1.6;padding:12px;background:#f7fafc;border-radius:6px;border-left:3px solid #10b981;">{{ currentSub.description || 'No description.' }}</p>
      </div>

      <!-- Actions -->
      <div v-if="['pending','in_review'].includes(currentSub.status)" class="actions-card">
        <h3><i class="fas fa-check-double"></i> Review Actions</h3>
        <p>Approve or reject this submission. Feedback is optional.</p>
        <textarea v-model="feedback" rows="3" placeholder="Optional feedback…" class="feedback-area"></textarea>
        <div class="actions-btns">
          <button class="btn-action btn-view" @click="doReview('approved')"><i class="fas fa-check"></i> Approve</button>
          <button class="btn-action" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:white;" @click="doReview('rejected')"><i class="fas fa-times"></i> Reject</button>
          <button class="btn-action" style="background:#f0f0f0;color:#4a5568;" @click="view='org'"><i class="fas fa-arrow-left"></i> Back</button>
        </div>
      </div>
      <div v-else class="actions-card">
        <span :style="currentSub.status==='approved' ? 'background:#d1fae5;color:#065f46;padding:10px 18px;border-radius:10px;font-weight:700;font-size:0.9em;' : 'background:#fee2e2;color:#991b1b;padding:10px 18px;border-radius:10px;font-weight:700;font-size:0.9em;'">
          <i :class="['fas', currentSub.status==='approved' ? 'fa-check-circle' : 'fa-ban']"></i>
          This submission has been {{ formatStatus(currentSub.status) }}
        </span>
        <button class="btn-action" style="background:#f0f0f0;color:#4a5568;" @click="view='org'"><i class="fas fa-arrow-left"></i> Back</button>
      </div>
    </template>

    <!-- Org Info Modal -->
    <div v-if="showOrgInfo" class="modal-backdrop" @click.self="showOrgInfo=false">
      <div class="modal-box">
        <div class="modal-header"><h3><i class="fas fa-building"></i> Organization Information</h3><button @click="showOrgInfo=false" class="modal-close">✕</button></div>
        <div class="modal-body info-grid">
          <div><label>Name</label><p>{{ currentOrg?.org_name }}</p></div>
          <div><label>Code</label><p>{{ currentOrg?.org_code }}</p></div>
          <div><label>Status</label><p>{{ currentOrg?.status || 'active' }}</p></div>
          <div><label>Email</label><p>{{ currentOrg?.email || 'N/A' }}</p></div>
        </div>
        <div class="modal-footer"><button class="btn-action" style="background:#f0f0f0;color:#4a5568;" @click="showOrgInfo=false">Close</button></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'

const view        = ref('folders')
const orgs        = ref([])
const submissions = ref([])
const orgSearch   = ref('')
const subSearch   = ref('')
const currentOrg  = ref(null)
const currentSub  = ref(null)
const feedback    = ref('')
const showOrgInfo = ref(false)
const successMsg  = ref('')

const MAX_FOLDERS = 49
const emptySlots  = computed(() => Math.max(0, MAX_FOLDERS - orgs.value.length))

const filteredOrgs = computed(() => {
  const q = orgSearch.value.toLowerCase()
  return orgs.value.filter(o => !q || o.org_name?.toLowerCase().includes(q) || o.org_code?.toLowerCase().includes(q))
})

const filteredSubs = computed(() => {
  const q = subSearch.value.toLowerCase()
  return submissions.value.filter(s => !q || s.title?.toLowerCase().includes(q) || s.full_name?.toLowerCase().includes(q))
})

onMounted(async () => {
  try {
    const { data } = await api.get('/api/review_submission.php?action=folders')
    orgs.value = data?.folders || []
  } catch {}
})

async function selectOrg(org) {
  currentOrg.value = org
  try {
    const { data } = await api.get(`/api/review_submission.php?action=by_org&org_id=${org.org_id}`)
    submissions.value = data?.submissions || []
  } catch {}
  view.value = 'org'
}

function selectSubmission(sub) { currentSub.value = sub; feedback.value = ''; view.value = 'detail' }

async function doReview(status) {
  try {
    const fd = new FormData()
    // submissions.php expects 'approve' or 'reject'
    fd.append('action', status === 'approved' ? 'approve' : 'reject')
    fd.append('submission_id', currentSub.value.submission_id)
    fd.append('reason', feedback.value || '')
    const { data } = await api.post('/api/submissions.php', fd)
    if (data?.success) {
      currentSub.value.status = status
      const idx = submissions.value.findIndex(s => s.submission_id === currentSub.value.submission_id)
      if (idx !== -1) submissions.value.splice(idx, 1)
      // update folder badge count
      const orgIdx = orgs.value.findIndex(o => o.org_id === currentOrg.value.org_id)
      if (orgIdx !== -1 && orgs.value[orgIdx].pending > 0) orgs.value[orgIdx].pending--
      successMsg.value = `Submission ${status} successfully!`
      setTimeout(() => successMsg.value = '', 3000)
      view.value = 'org'
    }
  } catch {}
}

function pad(n) { return String(n).padStart(3, '0') }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : '—' }
function formatStatus(s) { return s ? s.replace('_',' ').replace(/\b\w/g, c => c.toUpperCase()) : '' }
</script>

<style scoped>
/* Folders */
.folders-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:20px; margin-top:24px; }
.folder-card { display:flex; flex-direction:column; align-items:center; gap:10px; padding:22px; background:linear-gradient(135deg,#ffffff,#f8fafb); border:2px solid rgba(16,185,129,0.15); border-radius:14px; cursor:pointer; transition:all 0.3s; text-decoration:none; color:inherit; box-shadow:0 4px 12px rgba(16,185,129,0.08); }
.folder-card:hover { transform:translateY(-8px); border-color:rgba(16,185,129,0.4); box-shadow:0 12px 32px rgba(16,185,129,0.2); background:#f0fdf4; }
.folder-card.folder-empty { opacity:0.35; pointer-events:none; }
.folder-icon { font-size:44px; color:#10b981; }
.folder-name { margin:0; font-size:0.95em; font-weight:700; color:#1a202c; text-align:center; line-height:1.3; }
.folder-code { margin:0; font-size:0.8em; color:#718096; font-weight:600; }
.stat-badge { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#065f46; font-size:0.8em; font-weight:700; padding:6px 12px; border-radius:8px; display:flex; align-items:center; gap:5px; }
.hover-arrow { opacity:0; font-size:1.1em; color:#10b981; transition:all 0.3s; }
.folder-card:hover .hover-arrow { opacity:1; }

/* Org header */
.org-header { display:flex; align-items:center; gap:20px; padding:24px 28px; background:rgba(255,255,255,0.98); border-radius:16px; box-shadow:0 8px 32px rgba(16,185,129,0.12); border:1.5px solid rgba(16,185,129,0.15); margin-bottom:24px; position:relative; overflow:hidden; flex-wrap:wrap; }
.org-header::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg,#10b981,#059669); }
.org-icon { width:64px; height:64px; background:linear-gradient(135deg,#d1fae5,#a7f3d0); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:28px; color:#10b981; flex-shrink:0; }
.org-info { flex:1; }
.org-name-row { display:flex; align-items:center; gap:10px; margin-bottom:6px; flex-wrap:wrap; }
.org-name-row h3 { margin:0; font-size:1.4em; font-weight:800; color:#1a202c; }
.org-badge { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#065f46; font-size:0.72em; font-weight:700; padding:4px 10px; border-radius:8px; }
.org-code-tag { font-size:0.88em; color:#718096; display:flex; align-items:center; gap:5px; }

/* Breadcrumb */
.breadcrumb { display:flex; align-items:center; gap:10px; margin-bottom:22px; font-size:0.93em; flex-wrap:wrap; }
.breadcrumb a { color:#10b981; font-weight:600; cursor:pointer; padding:5px 9px; border-radius:6px; transition:background 0.2s; display:flex; align-items:center; gap:5px; }
.breadcrumb a:hover { background:rgba(16,185,129,0.1); }
.breadcrumb span { color:#4a5568; display:flex; align-items:center; gap:5px; font-weight:500; }

/* Detail */
.detail-card { background:white; border-radius:12px; padding:28px; margin-bottom:24px; border:1.5px solid #e2e8f0; box-shadow:0 4px 16px rgba(0,0,0,0.06); }
.detail-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:22px; padding-bottom:18px; border-bottom:2px solid #f0f0f0; flex-wrap:wrap; }
.detail-header h3 { margin:0; font-size:1.4em; font-weight:800; color:#1a202c; }
.meta-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-bottom:22px; }
.meta-grid label { font-weight:700; color:#10b981; font-size:0.8em; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:6px; }
.meta-grid p { margin:0; color:#2d3748; padding:9px 12px; background:#f7fafc; border-radius:6px; border-left:3px solid #10b981; }

.actions-card { background:linear-gradient(135deg,#d1fae5,#a7f3d0); border-radius:12px; padding:24px; border:1.5px solid rgba(16,185,129,0.3); }
.actions-card h3 { margin:0 0 6px; color:#065f46; font-size:1.1em; }
.actions-card > p { margin:0 0 14px; color:#047857; font-size:0.9em; }
.feedback-area { width:100%; padding:10px 12px; border:1.5px solid #a7f3d0; border-radius:8px; font-size:0.9em; font-family:'Poppins',sans-serif; resize:vertical; margin-bottom:14px; }
.feedback-area:focus { outline:none; border-color:#10b981; }
.actions-btns { display:flex; gap:10px; flex-wrap:wrap; }

/* Modal */
.modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
.modal-box { background:white; border-radius:14px; width:90%; max-width:520px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.25); }
.modal-header { background:linear-gradient(135deg,#047857,#10b981); color:white; padding:20px 24px; display:flex; justify-content:space-between; align-items:center; }
.modal-header h3 { margin:0; font-size:1.05em; font-weight:700; display:flex; align-items:center; gap:10px; }
.modal-close { background:rgba(255,255,255,.2); border:none; color:white; width:30px; height:30px; border-radius:50%; cursor:pointer; font-size:0.95em; }
.modal-body { padding:24px; }
.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.info-grid label { font-weight:700; color:#047857; font-size:0.8em; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:6px; }
.info-grid p { margin:0; padding:9px 12px; background:#f7fafc; border-radius:6px; border-left:3px solid #10b981; color:#2d3748; }
.modal-footer { padding:16px 24px; border-top:1px solid #f0f0f0; display:flex; justify-content:flex-end; }
</style>
