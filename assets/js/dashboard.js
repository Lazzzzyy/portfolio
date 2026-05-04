'use strict';

const DASH_API = './api/dashboard.php';

// ── DOM refs ────────────────────────────────────────────────────────────────

const statProjects = document.getElementById('dashStatProjects');
const statSkills   = document.getElementById('dashStatSkills');
const statMessages = document.getElementById('dashStatMessages');
const statPosts    = document.getElementById('dashStatPosts');
const statVisitors = document.getElementById('dashStatVisitors');
const refreshBtn   = document.getElementById('dashRefreshBtn');

const recentMsgsBody = document.querySelector('#dash-recents .dash-chart-card:first-child tbody');
const recentProjBody = document.querySelector('#dash-recents .dash-chart-card:last-child tbody');

// ── Fetch and update ─────────────────────────────────────────────────────────

async function refreshDashboard(showSpinner) {
  if (showSpinner && refreshBtn) {
    refreshBtn.classList.add('dash-refresh-btn--spinning');
    refreshBtn.disabled = true;
  }

  try {
    const res  = await fetch(DASH_API);
    const data = await res.json();
    if (!data.success) return;

    const s = data.stats;
    if (statProjects) statProjects.textContent = s.projects;
    if (statSkills)   statSkills.textContent   = s.skills;
    if (statMessages) statMessages.textContent = s.messages;
    if (statPosts)    statPosts.textContent     = s.posts;
    if (statVisitors) statVisitors.textContent  = s.visitors;

    if (recentMsgsBody && data.recentMessages) {
      if (data.recentMessages.length === 0) {
        recentMsgsBody.innerHTML = '<tr><td colspan="3" class="dash-table-empty">No messages yet</td></tr>';
      } else {
        recentMsgsBody.innerHTML = data.recentMessages.map(m => `
          <tr>
            <td>${escHtml(m.name)}</td>
            <td>${escHtml(m.subject)}</td>
            <td>${escHtml(m.date)}</td>
          </tr>`).join('');
      }
    }

    if (recentProjBody && data.recentProjects) {
      if (data.recentProjects.length === 0) {
        recentProjBody.innerHTML = '<tr><td colspan="3" class="dash-table-empty">No projects yet</td></tr>';
      } else {
        recentProjBody.innerHTML = data.recentProjects.map(p => `
          <tr>
            <td>${escHtml(p.title)}</td>
            <td>${escHtml(p.status.charAt(0).toUpperCase() + p.status.slice(1))}</td>
            <td>${escHtml(p.date)}</td>
          </tr>`).join('');
      }
    }
  } catch (_) {
  } finally {
    if (refreshBtn) {
      refreshBtn.classList.remove('dash-refresh-btn--spinning');
      refreshBtn.disabled = false;
    }
  }
}

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// ── Refresh button ───────────────────────────────────────────────────────────

if (refreshBtn) {
  refreshBtn.addEventListener('click', () => refreshDashboard(true));
}

// ── Auto-refresh when tab regains focus ──────────────────────────────────────

document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') {
    refreshDashboard(false);
  }
});

// ── Initial load ─────────────────────────────────────────────────────────────

refreshDashboard(false);
