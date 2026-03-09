# CIG Admin Dashboard — Vue 3

## Stack
- **Vue 3** + Composition API
- **Vite** (build tool)
- **Vue Router 4** (SPA routing)
- **Pinia** (auth state)
- **Chart.js 4** (dashboard charts)
- **Axios** (API calls)

## Project Structure
```
src/
├── main.js              # App entry
├── App.vue              # Root component
├── router/index.js      # All routes
├── stores/auth.js       # Pinia auth store
├── api/index.js         # Axios instance
├── assets/css/global.css
├── components/
│   ├── AppLayout.vue    # Sidebar + topbar + footer wrapper
│   └── PreviewModal.vue # Reusable file preview modal
└── views/
    ├── LoginView.vue
    ├── HomeView.vue        ← index.php
    ├── DashboardView.vue   ← dashboard.php
    ├── SubmissionsView.vue ← submissions.php
    ├── ReviewView.vue      ← review.php
    ├── ArchiveView.vue     ← archive.php
    └── CreateUserView.vue  ← create_user.php

api/   ← Drop these PHP files into cig_superadmin/api/
```

## Setup

### 1. Install dependencies
```bash
npm install
```

### 2. Configure API proxy
`vite.config.js` already proxies `/api` → `http://localhost/cig_superadmin`

### 3. Copy API files
Copy everything from `api/` into `cig_superadmin/api/` on your PHP server.

Your existing `file_preview.php` and `docx_to_pdf.php` in `cig_superadmin/pages/` remain unchanged.

### 4. Run dev server
```bash
npm run dev
```

### 5. Build for production
```bash
npm run build
# Output: dist/ — deploy this as static files
```

## Pages → Routes
| Old PHP | Vue Route |
|---------|-----------|
| `index.php` | `/` |
| `dashboard.php` | `/dashboard` |
| `submissions.php` | `/submissions` |
| `review.php` | `/review` |
| `archive.php` | `/archive` |
| `create_user.php` | `/create-user` |
| `login.php` | `/login` |
