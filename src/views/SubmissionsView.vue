<template>
  <div>
    <div class="page-header">
      <h2><i class="fas fa-file-alt"></i> Submissions</h2>
    </div>

    <div class="search-filter-container">
      <div class="search-filter-form">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input v-model="search" type="text" placeholder="Search submissions…" class="search-input" @input="onSearch" />
        </div>
      </div>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th><i class="fas fa-hashtag"></i> Ref No</th>
            <th><i class="fas fa-building"></i> Organization</th>
            <th><i class="fas fa-file-alt"></i> Title &amp; Type</th>
            <th><i class="fas fa-tag"></i> Status</th>
            <th><i class="fas fa-user"></i> Submitted By</th>
            <th><i class="fas fa-calendar"></i> Date &amp; Time</th>
            <th><i class="fas fa-cog"></i> Action</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="filtered.length">
            <tr v-for="(sub, i) in filtered" :key="sub.submission_id">
              <td class="ref-number">#{{ pad(i + 1) }}</td>
              <td>{{ sub.org_name || 'N/A' }}</td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <i :class="['fas', fileIcon(sub)]" :style="{ color: fileColor(sub), fontSize:'1.3rem', flexShrink:0 }"></i>
                  <div>
                    <strong>{{ sub.title }}</strong>
                    <div style="margin-top:3px;">
                      <span class="file-badge" :style="{ background: fileColor(sub) }">{{ fileExt(sub).toUpperCase() }}</span>
                    </div>
                  </div>
                </div>
              </td>
              <td><span :class="['status-badge', sub.status]"><i class="fas fa-circle" style="font-size:7px;"></i> {{ formatStatus(sub.status) }}</span></td>
              <td>{{ sub.full_name || 'N/A' }}</td>
              <td>
                <div>{{ formatDate(sub.submitted_at) }}</div>
                <small style="color:#888;font-size:.78rem;"><i class="far fa-clock"></i> {{ formatTime(sub.submitted_at) }}</small>
              </td>
              <td>
                <div class="action-buttons">
                  <button class="btn-action btn-view" @click="preview(sub)">
                    <i class="fas fa-eye"></i> Preview
                  </button>
                </div>
              </td>
            </tr>
          </template>
          <tr v-else class="empty-row"><td colspan="7">No submissions found</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Preview Modal -->
    <PreviewModal
      v-model="previewOpen"
      :submission-id="previewSub?.submission_id"
      :ext="previewExt"
      :title="previewSub?.title"
    >
      <template #actions>
        <div style="display:flex;gap:8px;flex-shrink:0;">
          <button class="modal-action-btn approve" @click="updateStatus(previewSub,'approved')">
            <i class="fas fa-check"></i> Approve
          </button>
          <button class="modal-action-btn reject" @click="updateStatus(previewSub,'rejected')">
            <i class="fas fa-times"></i> Reject
          </button>
        </div>
      </template>
    </PreviewModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import PreviewModal from '@/components/PreviewModal.vue'

const submissions = ref([])
const search      = ref('')
const previewOpen = ref(false)
const previewSub  = ref(null)

const filtered = computed(() => {
  const q = search.value.toLowerCase()
  return submissions.value.filter(s =>
    !q || s.title?.toLowerCase().includes(q) || s.org_name?.toLowerCase().includes(q)
  )
})

const previewExt = computed(() => {
  if (!previewSub.value?.file_name) return ''
  return previewSub.value.file_name.split('.').pop().toLowerCase()
})

onMounted(async () => {
  try {
    const { data } = await api.get('/api/submissions.php?action=getAll')
    submissions.value = data || []
  } catch {}
})

function onSearch() {}
function pad(n) { return String(n).padStart(3, '0') }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : '—' }
function formatTime(d) { return d ? new Date(d).toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' }) : '' }
function formatStatus(s) { return s ? s.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()) : '' }

const EXT_ICON  = { pdf:'fa-file-pdf', docx:'fa-file-word', doc:'fa-file-word', xlsx:'fa-file-excel', xls:'fa-file-excel' }
const EXT_COLOR = { pdf:'#e74c3c', docx:'#2980b9', doc:'#2980b9', xlsx:'#27ae60', xls:'#27ae60' }
function fileExt(s)   { return (s.file_name||'').split('.').pop().toLowerCase() }
function fileIcon(s)  { return EXT_ICON[fileExt(s)]  || 'fa-file-alt' }
function fileColor(s) { return EXT_COLOR[fileExt(s)] || '#7f8c8d' }

function preview(sub) { previewSub.value = sub; previewOpen.value = true }

async function updateStatus(sub, newStatus) {
  if (!sub) return
  try {
    const fd = new FormData()
    // submissions.php expects action='approve' or action='reject'
    fd.append('action', newStatus === 'approved' ? 'approve' : 'reject')
    fd.append('submission_id', sub.submission_id)
    fd.append('reason', '')
    const { data } = await api.post('/api/submissions.php', fd)
    if (data?.success) {
      sub.status = newStatus
      submissions.value = submissions.value.filter(s => s.submission_id !== sub.submission_id)
      previewOpen.value = false
    }
  } catch {}
}
</script>

<style scoped>
.file-badge { color: white; font-size: 0.66rem; font-weight: 700; padding: 2px 7px; border-radius: 4px; letter-spacing: 0.04em; }
.modal-action-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 16px; border: none; border-radius: 8px;
  cursor: pointer; font-size: 0.83em; font-weight: 700;
  font-family: 'Poppins', sans-serif; transition: all 0.2s;
  color: white;
}
.modal-action-btn.approve { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 3px 10px rgba(16,185,129,0.3); }
.modal-action-btn.reject  { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 3px 10px rgba(239,68,68,0.3); }
.modal-action-btn:hover { transform: translateY(-1px); }
</style>
