/**
 * Admin Dashboard Page
 * System-wide health and status overview
 */

import { requireAuth, getCurrentUser, signOut } from '../authService.js';
import { updateAuthenticatedNavigation, addAdminLink } from '../navigationService.js';
import { initHamburgerMenu } from '../hamburger.js';
import * as adminService from '../adminService.js';
import { showToast } from '../toast.js';

// Initialize page
document.addEventListener('DOMContentLoaded', async () => {
    initHamburgerMenu();
    requireAuth();

    const user = await getCurrentUser();
    if (!user) {
        window.location.href = 'signin.html';
        return;
    }

    if (!user.isAdmin) {
        console.warn('Access denied: User is not an admin');
        window.location.href = 'dashboard.html';
        return;
    }

    updateAuthenticatedNavigation(user, signOut);
    addAdminLink(user);

    await loadDashboard();
});

/**
 * Load and render all dashboard panels
 */
async function loadDashboard() {
    try {
        const data = await adminService.getDashboard();
        renderAlerts(data.alerts);
        renderSeason(data.season);
        renderEvents(data.events);
        renderFleet(data.fleet);
        renderCrew(data.crew);
        renderUsers(data.users);
    } catch (error) {
        console.error('Failed to load dashboard:', error);
        showToast(error.message || 'Failed to load dashboard', 'error');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Alerts Banner
// ─────────────────────────────────────────────────────────────────────────────

function renderAlerts(alerts) {
    const banner = document.getElementById('alerts-banner');
    if (!alerts || alerts.length === 0) {
        banner.style.display = 'none';
        return;
    }

    banner.style.display = 'block';
    banner.innerHTML = alerts.map(alert => {
        const icon = alert.level === 'warning' ? '⚠️' : 'ℹ️';
        return `
            <div class="alert-item ${alert.level}">
                <span class="alert-item-icon">${icon}</span>
                <span>${escapeHtml(alert.message)}</span>
            </div>
        `;
    }).join('');
}

// ─────────────────────────────────────────────────────────────────────────────
// Season Status
// ─────────────────────────────────────────────────────────────────────────────

function renderSeason(season) {
    const el = document.getElementById('season-status');
    const sourceClass = season.is_simulated ? 'simulated' : 'production';
    const sourceLabel = season.is_simulated ? 'Simulated' : 'Production';

    const effectiveDate = season.is_simulated && season.simulated_date
        ? season.simulated_date.split('T')[0]
        : 'Live';

    el.innerHTML = `
        <div class="season-stat">
            <span class="season-stat-label">Season Year</span>
            <span class="season-stat-value">${season.year}</span>
        </div>
        <div class="season-stat">
            <span class="season-stat-label">Time Source</span>
            <span class="season-stat-value">
                <span class="time-source-badge ${sourceClass}">${sourceLabel}</span>
            </span>
        </div>
        <div class="season-stat">
            <span class="season-stat-label">Effective Date</span>
            <span class="season-stat-value">${escapeHtml(effectiveDate)}</span>
        </div>
        <div class="season-stat">
            <span class="season-stat-label">Blackout Window</span>
            <span class="season-stat-value">${escapeHtml(season.blackout_from || '—')} – ${escapeHtml(season.blackout_to || '—')}</span>
        </div>
    `;
}

// ─────────────────────────────────────────────────────────────────────────────
// Upcoming Events Grid
// ─────────────────────────────────────────────────────────────────────────────

const SCENARIO_TEXT = {
    perfect_fit:     'Perfect Match',
    too_few_crews:   'Too Few Crews',
    too_many_crews:  'Too Many Crews',
};

function renderEvents(events) {
    const grid = document.getElementById('events-grid');
    if (!events || events.length === 0) {
        grid.innerHTML = '<p style="color:var(--text-gray)">No upcoming events.</p>';
        return;
    }

    const visible = events.slice(0, 3);
    const hidden  = events.slice(3);

    grid.innerHTML = visible.map(e => buildEventCard(e)).join('');

    if (hidden.length === 0) return;

    const section = grid.closest('.dashboard-section');

    const overflow = document.createElement('div');
    overflow.id = 'events-overflow';
    overflow.className = 'events-grid';
    overflow.style.display = 'none';
    overflow.style.marginTop = '1rem';
    overflow.innerHTML = hidden.map(e => buildEventCard(e)).join('');

    const toggleWrap = document.createElement('div');
    toggleWrap.style.marginTop = '1rem';
    const btn = document.createElement('button');
    btn.className = 'btn btn-secondary';
    btn.style.fontSize = '0.9rem';
    btn.textContent = `Show ${hidden.length} more event${hidden.length !== 1 ? 's' : ''} \u2193`;
    btn.addEventListener('click', () => {
        const expanded = overflow.style.display !== 'none';
        overflow.style.display = expanded ? 'none' : 'grid';
        btn.textContent = expanded
            ? `Show ${hidden.length} more event${hidden.length !== 1 ? 's' : ''} \u2193`
            : `Show fewer events \u2191`;
    });
    toggleWrap.appendChild(btn);

    section.appendChild(overflow);
    section.appendChild(toggleWrap);
}

function buildEventCard(event) {
    const isNext = event.is_next_event;
    const cardClass = isNext ? 'event-card is-next' : 'event-card';
    const nextBadge = isNext ? '<span class="next-event-badge">Next Event</span>' : '';

    const dateStr = event.event_date
        ? new Date(event.event_date + 'T12:00:00').toLocaleDateString()
        : event.event_id;

    const scenarioText = SCENARIO_TEXT[event.capacity.scenario] || event.capacity.scenario;
    const surplus = event.capacity.surplus_deficit;
    const surplusStr = surplus > 0 ? `+${surplus}` : `${surplus}`;

    const sc = event.crew_status_counts;
    const notifHtml = buildNotifBadges(event.notifications);
    const flotillaHtml = buildFlotillaAge(event.flotilla_generated_at);
    const waitlistCount = (event.waitlist?.boats ?? 0) + (event.waitlist?.crews ?? 0);

    return `
        <div class="${cardClass}">
            <div class="event-card-header">
                <span class="event-card-date">${escapeHtml(dateStr)}</span>
                ${nextBadge}
            </div>
            <div class="event-card-meta">
                <span class="scenario-badge ${event.capacity.scenario}">${escapeHtml(scenarioText)}</span>
                <span style="font-size:0.8rem;color:var(--text-gray)">${surplusStr} berth surplus/deficit</span>
            </div>
            <div class="event-stat-row">
                <div class="stat-tile">
                    <span class="stat-tile-value">${event.capacity.total_berths}</span>
                    <span class="stat-tile-label">Berths</span>
                </div>
                <div class="stat-tile">
                    <span class="stat-tile-value">${sc.available + sc.guaranteed}</span>
                    <span class="stat-tile-label">Crews</span>
                </div>
                <div class="stat-tile">
                    <span class="stat-tile-value">${sc.guaranteed}</span>
                    <span class="stat-tile-label">Guaranteed</span>
                </div>
                <div class="stat-tile">
                    <span class="stat-tile-value">${sc.withdrawn}</span>
                    <span class="stat-tile-label">Withdrawn</span>
                </div>
                ${waitlistCount > 0 ? `
                <div class="stat-tile">
                    <span class="stat-tile-value">${waitlistCount}</span>
                    <span class="stat-tile-label">Waitlisted</span>
                </div>` : ''}
            </div>
            <div class="notif-row">${notifHtml}</div>
            <div class="flotilla-age">${flotillaHtml}</div>
            <a href="admin-matching.html?event=${encodeURIComponent(event.event_id)}" class="event-card-link">View Matching &rarr;</a>
        </div>
    `;
}

function buildNotifBadges(notifications) {
    const reminderClass = notifications.reminder_sent ? 'sent' : 'pending';
    const reminderLabel = notifications.reminder_sent ? 'Reminder Sent' : 'Reminder Pending';
    const crewListClass = notifications.crew_list_sent ? 'sent' : 'pending';
    const crewListLabel = notifications.crew_list_sent ? 'Crew List Sent' : 'Crew List Pending';

    return `
        <span class="notif-badge ${reminderClass}">${reminderLabel}</span>
        <span class="notif-badge ${crewListClass}">${crewListLabel}</span>
    `;
}

function buildFlotillaAge(generatedAt) {
    if (!generatedAt) return 'Flotilla: Not generated';
    const generated = new Date(generatedAt);
    const now = new Date();
    const diffMs = now - generated;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    let ago;
    if (diffMins < 2) {
        ago = 'just now';
    } else if (diffMins < 60) {
        ago = `${diffMins} minutes ago`;
    } else if (diffHours < 24) {
        ago = `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
    } else {
        ago = `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
    }

    return `Last generated ${ago}`;
}

// ─────────────────────────────────────────────────────────────────────────────
// Fleet Panel
// ─────────────────────────────────────────────────────────────────────────────

function renderFleet(fleet) {
    const el = document.getElementById('fleet-panel');
    const flex = fleet.flex_distribution ?? { flex: 0 };

    const notableHtml = fleet.notable.length > 0
        ? `
            <table class="notable-table" style="margin-top:0.5rem">
                <thead><tr><th>Boat</th><th>Absences</th><th>Assistance</th></tr></thead>
                <tbody>
                    ${fleet.notable.map(b => `
                        <tr>
                            <td>${escapeHtml(b.display_name)}</td>
                            <td>${b.rank_absence}</td>
                            <td>${b.assistance_required ? 'Required' : '—'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `
        : '<p style="font-size:0.85rem;color:var(--text-gray);margin-top:0.5rem">None</p>';

    el.innerHTML = `
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.75rem">Fleet</h3>

        <div class="scope-label">Next Event</div>
        <div class="stat-grid" style="margin-bottom:0">
            <div class="stat-grid-item">
                <span class="stat-grid-value">${fleet.boats_active_next_event}</span>
                <span class="stat-grid-label">Active</span>
            </div>
        </div>

        <div class="scope-divider"></div>

        <div class="scope-label">Season</div>
        <div class="stat-grid" style="margin-bottom:0.75rem">
            <div class="stat-grid-item">
                <span class="stat-grid-value">${fleet.total_boats}</span>
                <span class="stat-grid-label">Total Boats</span>
            </div>
            <div class="stat-grid-item">
                <span class="stat-grid-value">${flex.flex}</span>
                <span class="stat-grid-label">Flex</span>
            </div>
        </div>
        <div class="notable-section">
            <div class="scope-divider"></div>
            <div class="scope-label">Notable</div>
            ${notableHtml}
        </div>
    `;
}

// ─────────────────────────────────────────────────────────────────────────────
// Crew Panel
// ─────────────────────────────────────────────────────────────────────────────

function renderCrew(crew) {
    const el = document.getElementById('crew-panel');
    const totalCrews = crew.total_crews || 1; // avoid div-by-zero

    const skillBars   = buildBars(crew.skill_distribution, totalCrews, ['novice', 'intermediate', 'advanced']);
    const commitBars  = buildBars(crew.commitment_distribution, totalCrews, ['unavailable', 'penalty', 'available', 'assigned']);

    const commitmentLabel = (rank) => rank === 1 ? 'Penalty' : '—';

    const notableHtml = crew.notable.length > 0
        ? `
            <table class="notable-table" style="margin-top:0.5rem">
                <thead><tr><th>Crew</th><th>Absences</th><th>Commitment</th></tr></thead>
                <tbody>
                    ${crew.notable.map(c => `
                        <tr>
                            <td>${escapeHtml(c.display_name)}</td>
                            <td>${c.rank_absence}</td>
                            <td>${escapeHtml(commitmentLabel(c.rank_commitment))}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `
        : '<p style="font-size:0.85rem;color:var(--text-gray);margin-top:0.5rem">None</p>';

    el.innerHTML = `
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.75rem">Crew (${crew.total_crews} total)</h3>

        <div class="scope-label">Next Event</div>
        <div style="margin-bottom:0">
            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-gray);margin-bottom:0.4rem">Commitment</div>
            ${commitBars}
        </div>

        <div class="scope-divider"></div>

        <div class="scope-label">Season</div>
        <div style="margin-bottom:0.75rem">
            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-gray);margin-bottom:0.4rem">Skill</div>
            ${skillBars}
        </div>
        <div class="notable-section">
            <div class="scope-divider"></div>
            <div class="scope-label">Notable</div>
            ${notableHtml}
        </div>
    `;
}

function buildBars(distribution, total, keys) {
    return keys.map(key => {
        const count = distribution[key] || 0;
        const pct = total > 0 ? Math.round((count / total) * 100) : 0;
        return `
            <div class="skill-bar-row">
                <span class="skill-bar-label">${key}</span>
                <div class="skill-bar-track">
                    <div class="skill-bar-fill ${key}" style="width:${pct}%"></div>
                </div>
                <span class="skill-bar-count">${count}</span>
            </div>
        `;
    }).join('');
}

// ─────────────────────────────────────────────────────────────────────────────
// Users Panel
// ─────────────────────────────────────────────────────────────────────────────

function renderUsers(users) {
    const el = document.getElementById('users-panel');
    el.innerHTML = `
        <div class="stat-grid-item">
            <span class="stat-grid-value">${users.total}</span>
            <span class="stat-grid-label">Total Users</span>
        </div>
        <div class="stat-grid-item">
            <span class="stat-grid-value">${users.crew_accounts}</span>
            <span class="stat-grid-label">Crew Accounts</span>
        </div>
        <div class="stat-grid-item">
            <span class="stat-grid-value">${users.boat_owner_accounts}</span>
            <span class="stat-grid-label">Boat Owner Accounts</span>
        </div>
        <div class="stat-grid-item">
            <span class="stat-grid-value">${users.admin_count}</span>
            <span class="stat-grid-label">Admins</span>
        </div>
    `;
}

// ─────────────────────────────────────────────────────────────────────────────
// Utility
// ─────────────────────────────────────────────────────────────────────────────

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
