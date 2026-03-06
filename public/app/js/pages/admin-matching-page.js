/**
 * Admin Matching Page
 * Event matching analysis and capacity reporting
 */

import { requireAuth, getCurrentUser, signOut } from '../authService.js';
import { updateAuthenticatedNavigation, addAdminLink } from '../navigationService.js';
import { initHamburgerMenu } from '../hamburger.js';
import * as eventService from '../eventService.js';
import * as adminService from '../adminService.js';
import { showToast } from '../toast.js';

let allEvents = [];

// Initialize page
document.addEventListener('DOMContentLoaded', async () => {
    // Initialize hamburger menu
    initHamburgerMenu();

    // Require authentication
    requireAuth();

    // Get current user
    const user = await getCurrentUser();
    if (!user) {
        window.location.href = 'signin.html';
        return;
    }

    // Check admin privileges
    if (!user.isAdmin) {
        console.warn('Access denied: User is not an admin');
        window.location.href = 'dashboard.html';
        return;
    }

    // Update navigation
    updateAuthenticatedNavigation(user, signOut);
    addAdminLink(user);

    // Load events
    await loadEvents();

    // Setup event listeners
    setupEventListeners();
});

/**
 * Load all events
 */
async function loadEvents() {
    try {
        allEvents = await eventService.getAllEvents();
        populateEventSelect();
    } catch (error) {
        console.error('Failed to load events:', error);
        showToast('Failed to load events', 'error');
    }
}

/**
 * Populate event dropdown
 */
function populateEventSelect() {
    const select = document.getElementById('event-select');
    const loadBtn = document.getElementById('load-btn');

    // Clear existing options (keep the first placeholder)
    select.innerHTML = '<option value="">-- Select an event --</option>';

    // Add event options
    allEvents.forEach(event => {
        const option = document.createElement('option');
        option.value = event.eventId;
        // Parse date as local date by appending time component
        const localDate = new Date(event.date + 'T12:00:00');
        option.textContent = `${event.eventId} (${localDate.toLocaleDateString()})`;
        select.appendChild(option);
    });

    // Enable load button when event selected
    select.addEventListener('change', () => {
        loadBtn.disabled = !select.value;
    });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    const loadBtn = document.getElementById('load-btn');
    const eventSelect = document.getElementById('event-select');

    loadBtn.addEventListener('click', async () => {
        const eventId = eventSelect.value;
        if (!eventId) return;

        await loadMatchingData(eventId);
    });
}

/**
 * Load matching data for selected event
 */
async function loadMatchingData(eventId) {
    const loadBtn = document.getElementById('load-btn');
    const emptyState = document.getElementById('empty-state');

    try {
        // Show loading state
        loadBtn.classList.add('loading');
        loadBtn.disabled = true;

        // Fetch matching data
        const data = await adminService.getMatchingData(eventId);

        // Hide empty state
        emptyState.style.display = 'none';

        // Render capacity summary
        renderCapacitySummary(data.capacity);

        // Render boats table
        renderBoatsTable(data.available_boats);

        // Render crews table
        renderCrewsTable(data.available_crews);

        showToast('Matching data loaded successfully', 'success');
    } catch (error) {
        console.error('Failed to load matching data:', error);
        showToast(error.message || 'Failed to load matching data', 'error');
    } finally {
        // Remove loading state
        loadBtn.classList.remove('loading');
        loadBtn.disabled = false;
    }
}

/**
 * Render capacity summary
 */
function renderCapacitySummary(capacity) {
    const summarySection = document.getElementById('capacity-summary');
    const totalBerthsEl = document.getElementById('total-berths');
    const totalCrewsEl = document.getElementById('total-crews');
    const surplusDeficitEl = document.getElementById('surplus-deficit');
    const scenarioBadge = document.getElementById('scenario-badge');
    const scenarioDescription = document.getElementById('scenario-description');

    // Show section
    summarySection.style.display = 'block';

    // Update values
    totalBerthsEl.textContent = capacity.total_berths;
    totalCrewsEl.textContent = capacity.total_crews;
    const sd = capacity.surplus_deficit;
    surplusDeficitEl.textContent = sd > 0 ? `+${sd}` : sd;
    surplusDeficitEl.className = 'capacity-value ' + (sd === 0 ? 'surplus' : sd > 0 ? 'warning' : 'deficit');

    // Update scenario badge
    const scenarioClassMap = {
        'perfect_fit': 'perfect',
        'too_few_crews': 'few',
        'too_many_crews': 'many'
    };
    const scenarioText = {
        'perfect_fit': 'Perfect Match',
        'too_few_crews': 'Too Few Crews',
        'too_many_crews': 'Too Many Crews'
    };
    const scenarioDescriptions = {
        'perfect_fit': 'All berths will be filled exactly.',
        'too_few_crews': 'Not enough crews — some boats will have empty berths.',
        'too_many_crews': 'More crews than berths — some crews will be waitlisted.'
    };
    scenarioBadge.className = `scenario-badge ${scenarioClassMap[capacity.scenario] || ''}`;
    scenarioBadge.textContent = scenarioText[capacity.scenario] || capacity.scenario;
    scenarioDescription.textContent = scenarioDescriptions[capacity.scenario] || '';
}

/**
 * Render boats table
 */
function renderBoatsTable(boats) {
    const section = document.getElementById('boats-section');
    const container = document.getElementById('boats-table-container');

    if (!boats || boats.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';
    document.querySelector('#boats-section h2').textContent = `Available Boats (${boats.length})`;

    const table = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Boat Name</th>
                    <th>Berths Offered</th>
                    <th>Capacity Range</th>
                    <th>Assistance</th>
                </tr>
            </thead>
            <tbody>
                ${boats.map(boat => `
                    <tr>
                        <td><strong>${boat.display_name}</strong></td>
                        <td>${boat.berths}</td>
                        <td>${boat.min_berths}-${boat.max_berths}</td>
                        <td>
                            ${boat.requires_assistance ? '<span class="assistance-badge">Assistance Required</span>' : '—'}
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;

    container.innerHTML = table;
}

/**
 * Render crews table
 */
function renderCrewsTable(crews) {
    const section = document.getElementById('crews-section');
    const container = document.getElementById('crews-table-container');

    if (!crews || crews.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';
    document.querySelector('#crews-section h2').textContent = `Available Crews (${crews.length})`;

    const skillLevelText = {
        0: 'novice',
        1: 'intermediate',
        2: 'advanced'
    };

    const availabilityText = {
        0: 'Unavailable',
        1: 'Available',
        2: 'Guaranteed',
        3: 'Withdrawn'
    };

    const table = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Crew Name</th>
                    <th>Skill Level</th>
                    <th>Availability Status</th>
                </tr>
            </thead>
            <tbody>
                ${crews.map(crew => {
                    const skillClass = skillLevelText[crew.skill] || 'novice';
                    return `
                        <tr>
                            <td><strong>${crew.display_name}</strong></td>
                            <td><span class="skill-badge ${skillClass}">${skillClass}</span></td>
                            <td>${availabilityText[crew.availability] || 'Unknown'}</td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
    `;

    container.innerHTML = table;
}
