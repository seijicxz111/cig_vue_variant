<template>
  <div>
    <div class="page-header">
      <h2><i class="fas fa-chart-line"></i> Dashboard</h2>
    </div>

    <!-- Date Range Filter -->
    <div class="filter-bar">
      <div class="filter-btns">
        <button v-for="r in ranges" :key="r.val" class="range-btn" :class="{ active: range === r.val }" @click="range = r.val; loadData()">{{ r.label }}</button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="cards">
      <div class="card card-total">
        <div class="card-icon"><i class="fas fa-file-alt"></i></div>
        <div class="card-content"><h3>Total Submissions</h3><p class="card-number">{{ stats.total || 0 }}</p></div>
      </div>
      <div class="card card-pending">
        <div class="card-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="card-content"><h3>Pending</h3><p class="card-number">{{ stats.pending || 0 }}</p></div>
      </div>
      <div class="card card-approved">
        <div class="card-icon"><i class="fas fa-check-circle"></i></div>
        <div class="card-content"><h3>Approved</h3><p class="card-number">{{ stats.approved || 0 }}</p></div>
      </div>
      <div class="card card-rejected">
        <div class="card-icon"><i class="fas fa-ban"></i></div>
        <div class="card-content"><h3>Rejected</h3><p class="card-number">{{ stats.rejected || 0 }}</p></div>
      </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-row">
      <div class="chart-card">
        <h4><i class="fas fa-chart-line"></i> Monthly Submission Trend</h4>
        <canvas ref="trendCanvas"></canvas>
      </div>
      <div class="chart-card">
        <h4><i class="fas fa-chart-pie"></i> Status Distribution</h4>
        <canvas ref="donutCanvas"></canvas>
      </div>
    </div>

    <!-- Chart Row 2: Org Bar -->
    <div class="chart-card" style="margin-bottom:28px;">
      <h4><i class="fas fa-bars"></i> Submissions per Organization</h4>
      <canvas ref="orgCanvas" style="max-height:260px;"></canvas>
    </div>

    <!-- Charts Row 3 -->
    <div class="charts-row" style="margin-bottom:28px;">
      <div class="chart-card">
        <h4><i class="fas fa-chart-line"></i> Approved vs Rejected Trend</h4>
        <canvas ref="approvalCanvas"></canvas>
      </div>
      <div class="insights-panel">
        <h4><i class="fas fa-lightbulb"></i> Smart Insights</h4>
        <div class="insight-row"><span>Most Active Org</span><strong>{{ insights.most_active || 'N/A' }}</strong></div>
        <div class="insight-row"><span>Oldest Pending</span><strong>{{ insights.oldest_pending ? insights.oldest_pending + ' days' : 'N/A' }}</strong></div>
        <div class="insight-row"><span>Highest Month</span><strong>{{ insights.highest_month || 'N/A' }}</strong></div>
        <div class="insight-row"><span>Lowest Month</span><strong>{{ insights.lowest_month || 'N/A' }}</strong></div>
        <div class="insight-row"><span>Approval Rate</span><strong>{{ stats.approval_rate ? stats.approval_rate + '%' : 'N/A' }}</strong></div>
      </div>
    </div>

    <!-- Recent Submissions -->
    <div class="recent-section">
      <div class="recent-header"><i class="fas fa-history"></i> Recent Submissions</div>
      <div class="table-container">
        <table>
          <thead>
            <tr><th>Ref No</th><th>Organization</th><th>Title</th><th>Status</th><th>Date</th></tr>
          </thead>
          <tbody>
            <tr v-for="(s, i) in recent" :key="s.submission_id">
              <td class="ref-number">{{ pad(i + 1) }}</td>
              <td>{{ s.org_name || 'N/A' }}</td>
              <td>{{ s.title }}</td>
              <td><span :class="['status-badge', s.status]">{{ formatStatus(s.status) }}</span></td>
              <td>{{ formatDate(s.submitted_at) }}</td>
            </tr>
            <tr v-if="!recent.length" class="empty-row"><td colspan="5">No submissions found</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { Chart, registerables } from 'chart.js'
import api from '@/api'

Chart.register(...registerables)

const range  = ref('30')
const ranges = [{ val:'7', label:'Last 7 Days' }, { val:'30', label:'Last 30 Days' }, { val:'12', label:'Last 12 Months' }]

const stats    = reactive({})
const insights = reactive({})
const recent   = ref([])

const trendCanvas    = ref(null)
const donutCanvas    = ref(null)
const orgCanvas      = ref(null)
const approvalCanvas = ref(null)

let charts = {}

function destroyCharts() { Object.values(charts).forEach(c => c?.destroy()); charts = {} }

function pad(n) { return String(n).padStart(3, '0') }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : '—' }
function formatStatus(s) { return s ? s.replace('_',' ').replace(/\b\w/g, c => c.toUpperCase()) : '' }

async function loadData() {
  try {
    const { data } = await api.get(`/api/dashboard.php?range=${range.value}`)
    if (!data) return

    Object.assign(stats, data.stats || {})
    Object.assign(insights, data.insights || {})
    recent.value = data.recent || []

    destroyCharts()

    const green = '#10b981', red = '#ef4444', blue = '#3b82f6', yellow = '#f59e0b'

    if (trendCanvas.value && data.monthly_trend?.length) {
      charts.trend = new Chart(trendCanvas.value, {
        type: 'line',
        data: {
          labels: data.monthly_trend.map(d => d.month),
          datasets: [{ label:'Submissions', data: data.monthly_trend.map(d => d.count), borderColor: green, backgroundColor:'rgba(16,185,129,0.1)', borderWidth:3, tension:0.4, fill:true, pointRadius:5, pointBackgroundColor:green }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ labels:{ usePointStyle:true } } }, scales:{ y:{ beginAtZero:true }, x:{ grid:{ display:false } } } }
      })
    }

    if (donutCanvas.value && data.status_distribution?.length) {
      const colorMap = { approved:green, in_review:blue, pending:yellow, rejected:red }
      charts.donut = new Chart(donutCanvas.value, {
        type: 'doughnut',
        data: {
          labels: data.status_distribution.map(d => formatStatus(d.status)),
          datasets: [{ data: data.status_distribution.map(d => d.count), backgroundColor: data.status_distribution.map(d => colorMap[d.status]||'#9ca3af'), borderColor:'#fff', borderWidth:2 }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
      })
    }

    if (orgCanvas.value && data.org_submissions?.length) {
      charts.org = new Chart(orgCanvas.value, {
        type: 'bar',
        data: {
          labels: data.org_submissions.map(d => d.org_name),
          datasets: [{ label:'Submissions', data: data.org_submissions.map(d => d.count), backgroundColor:green, borderRadius:8 }]
        },
        options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales:{ x:{beginAtZero:true}, y:{grid:{display:false}} } }
      })
    }

    if (approvalCanvas.value && data.approval_trend?.length) {
      charts.approval = new Chart(approvalCanvas.value, {
        type: 'line',
        data: {
          labels: data.approval_trend.map(d => d.month),
          datasets: [
            { label:'Approved', data: data.approval_trend.map(d => d.approved), borderColor:green, backgroundColor:'rgba(16,185,129,0.1)', borderWidth:3, tension:0.4, fill:true },
            { label:'Rejected', data: data.approval_trend.map(d => d.rejected), borderColor:red,   backgroundColor:'rgba(239,68,68,0.1)',    borderWidth:3, tension:0.4, fill:true }
          ]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ labels:{ usePointStyle:true } } }, scales:{ y:{ beginAtZero:true }, x:{ grid:{ display:false } } } }
      })
    }
  } catch (e) { console.warn('Dashboard load error:', e) }
}

onMounted(loadData)
</script>

<style scoped>
/* Filter bar */
.filter-bar { background:white; border-radius:12px; padding:16px; margin-bottom:28px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 8px rgba(0,0,0,0.08); flex-wrap:wrap; gap:12px; }
.filter-btns { display:flex; gap:10px; flex-wrap:wrap; }
.range-btn { background:none; border:1.5px solid #e2e8f0; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; transition:all 0.3s; color:#2d3748; }
.range-btn:hover, .range-btn.active { background:linear-gradient(135deg,#10b981,#059669); color:white; border-color:#10b981; }

/* KPI Cards — original layout */
.cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:24px; margin-bottom:40px; }
.card { background:white; padding:28px; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.08),inset 0 1px 0 rgba(255,255,255,0.8); text-align:left; transition:all 0.35s cubic-bezier(0.34,1.56,0.64,1); cursor:pointer; border:1.5px solid #f0f0f0; position:relative; overflow:hidden; display:flex; align-items:flex-start; gap:16px; }
.card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg,#10b981,#059669); }
.card::after { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent); transition:left 0.6s; z-index:0; }
.card:hover::after { left:100%; }
.card:hover { transform:translateY(-6px); box-shadow:0 12px 32px rgba(16,185,129,0.15),inset 0 1px 0 rgba(255,255,255,0.8); border-color:rgba(16,185,129,0.2); }
.card-icon { width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0; }
.card-content { flex:1; position:relative; z-index:1; }
.card-content h3 { color:#4a5568; font-size:0.9em; font-weight:600; margin:0 0 8px; text-transform:uppercase; letter-spacing:0.5px; }
.card-number { font-size:2.2em; font-weight:800; margin:0; line-height:1; color:#1a202c; }

.card-total::before { background:linear-gradient(90deg,#10b981,#059669); }
.card-total .card-icon { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#10b981; }
.card-total .card-number { background:linear-gradient(135deg,#10b981,#059669); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }

.card-pending::before { background:linear-gradient(90deg,#f59e0b,#d97706); }
.card-pending .card-icon { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#f59e0b; }
.card-pending .card-number { color:#b45309; }

.card-approved::before { background:linear-gradient(90deg,#8b5cf6,#7c3aed); }
.card-approved .card-icon { background:linear-gradient(135deg,#ede9fe,#ddd6fe); color:#8b5cf6; }
.card-approved .card-number { background:linear-gradient(135deg,#8b5cf6,#7c3aed); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }

.card-rejected::before { background:linear-gradient(90deg,#ef4444,#dc2626); }
.card-rejected .card-icon { background:linear-gradient(135deg,#fee2e2,#fecaca); color:#ef4444; }
.card-rejected .card-number { background:linear-gradient(135deg,#ef4444,#dc2626); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }

/* Charts */
.charts-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:28px; }
.chart-card { background:white; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
.chart-card h4 { font-size:16px; font-weight:600; color:#1a202c; margin:0 0 20px; display:flex; align-items:center; gap:8px; }
.chart-card h4 i { color:#10b981; }
.chart-card canvas { max-height:240px; }

/* Insights */
.insights-panel { background:white; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
.insights-panel h4 { font-size:16px; font-weight:600; color:#1a202c; margin:0 0 20px; }
.insight-row { padding:16px 0; border-bottom:1px solid #f0f0f0; display:flex; flex-direction:column; gap:6px; }
.insight-row:last-child { border-bottom:none; }
.insight-row span { font-size:12px; color:#718096; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; }
.insight-row strong { font-size:18px; font-weight:700; color:#1a202c; }

/* Recent */
.recent-section { background:white; padding:30px; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border:1px solid #f0f0f0; }
.recent-header { font-size:16px; font-weight:600; color:#1a202c; margin-bottom:20px; padding-bottom:12px; border-bottom:2px solid #10b981; display:flex; align-items:center; gap:8px; }

@media (max-width:768px) { .charts-row { grid-template-columns:1fr; } .cards { grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); } }
</style>
