<template>
  <div>
    <div class="page-header">
      <h2><i class="fas fa-archive"></i> Archive — Rejected Submissions</h2>
    </div>

    <div class="search-filter-container">
      <div class="search-filter-form">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input v-model="search" type="text" placeholder="Search by title, submitter, or organization…" class="search-input" />
        </div>
        <select v-model="orgFilter" class="filter-select">
          <option value="">All Organizations</option>
          <option v-for="o in orgs" :key="o.org_id" :value="o.org_id">{{ o.org_name }}</option>
        </select>
      </div>
    </div>

    <div>
      <h3 class="section-title"><i class="fas fa-trash"></i> Rejected Submissions ({{ filtered.length }})</h3>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th><i class="fas fa-hashtag"></i> Ref No</th>
              <th><i class="fas fa-file-alt"></i> Title</th>
              <th><i class="fas fa-building"></i> Organization</th>
              <th><i class="fas fa-user"></i> Submitted By</th>
              <th><i class="fas fa-calendar"></i> Submission Date</th>
              <th><i class="fas fa-ban"></i> Rejected Date</th>
              <th><i class="fas fa-cog"></i> Action</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="filtered.length">
              <tr v-for="(sub, i) in filtered" :key="sub.submission_id">
                <td class="ref-number">#{{ pad(i + 1) }}</td>
                <td><strong>{{ sub.title }}</strong></td>
                <td>{{ sub.org_name || 'N/A' }}</td>
                <td>{{ sub.full_name || 'N/A' }}</td>
                <td>{{ formatDate(sub.submitted_at) }}</td>
                <td>{{ formatDate(sub.updated_at) }}</td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-action btn-view" @click="preview(sub)">
                      <i class="fas fa-eye"></i> Preview
                    </button>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-else class="empty-row"><td colspan="7">No rejected submissions found.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <PreviewModal
      v-model="previewOpen"
      :submission-id="previewSub?.submission_id"
      :ext="previewExt"
      :title="previewSub?.title"
    >
      <template #badge>
        <span style="background:rgba(255,255,255,.2);padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;white-space:nowrap;">REJECTED</span>
      </template>
    </PreviewModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import PreviewModal from '@/components/PreviewModal.vue'

const submissions = ref([])
const orgs        = ref([])
const search      = ref('')
const orgFilter   = ref('')
const previewOpen = ref(false)
const previewSub  = ref(null)

const filtered = computed(() => submissions.value.filter(s => {
  const q = search.value.toLowerCase()
  const matchSearch = !q || s.title?.toLowerCase().includes(q) || s.full_name?.toLowerCase().includes(q) || s.org_name?.toLowerCase().includes(q)
  const matchOrg = !orgFilter.value || s.org_id == orgFilter.value
  return matchSearch && matchOrg
}))

const previewExt = computed(() => (previewSub.value?.file_name || '').split('.').pop().toLowerCase())

onMounted(async () => {
  try {
    const { data } = await api.get('/api/archive.php')
    submissions.value = data?.submissions || []
    orgs.value = data?.orgs || []
  } catch {}
})

function pad(n) { return String(n).padStart(3, '0') }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : '—' }
function preview(sub) { previewSub.value = sub; previewOpen.value = true }
</script>

<style scoped>
.section-title { font-size: 17px; font-weight: 700; color: #047857; margin: 0 0 18px; display: flex; align-items: center; gap: 10px; }
</style>
