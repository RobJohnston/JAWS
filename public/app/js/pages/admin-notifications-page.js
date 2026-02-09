/**
 * Admin Notifications Page
 * Send email notifications and calendar invites to participants
 */

import { requireAuth, getCurrentUser, signOut } from '../authService.js';
import { updateAuthenticatedNavigation, addAdminLink } from '../navigationService.js';
import { initHamburgerMenu } from '../hamburger.js';
import * as eventService from '../eventService.js';
import * as adminService from '../adminService.js';
import { showToast } from '../toast.js';

let allEvents = [];
let currentEventData = null;

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
        allEvents = await eventService.getEvents();
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
    const previewBtn = document.getElementById('preview-btn');

    // Clear existing options (keep the first placeholder)
    select.innerHTML = '<option value="">-- Select an event --</option>';

    // Add event options
    allEvents.forEach(event => {
        const option = document.createElement('option');
        option.value = event.event_id;
        option.textContent = `${event.event_id} (${new Date(event.event_date).toLocaleDateString()})`;
        select.appendChild(option);
    });

    // Enable preview button when event selected
    select.addEventListener('change', () => {
        previewBtn.disabled = !select.value;
    });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    const previewBtn = document.getElementById('preview-btn');
    const sendBtn = document.getElementById('send-btn');
    const eventSelect = document.getElementById('event-select');
    const confirmModal = document.getElementById('confirm-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    const confirmBtn = document.getElementById('confirm-btn');

    // Load preview
    previewBtn.addEventListener('click', async () => {
        const eventId = eventSelect.value;
        if (!eventId) return;

        await loadPreview(eventId);
    });

    // Send notifications (show confirmation modal)
    sendBtn.addEventListener('click', () => {
        showConfirmationModal();
    });

    // Cancel modal
    cancelBtn.addEventListener('click', () => {
        hideConfirmationModal();
    });

    // Confirm send
    confirmBtn.addEventListener('click', async () => {
        hideConfirmationModal();
        await sendNotifications();
    });

    // Close modal on backdrop click
    confirmModal.addEventListener('click', (e) => {
        if (e.target === confirmModal) {
            hideConfirmationModal();
        }
    });
}

/**
 * Load preview for selected event
 */
async function loadPreview(eventId) {
    const previewBtn = document.getElementById('preview-btn');
    const emptyState = document.getElementById('empty-state');

    try {
        // Show loading state
        previewBtn.classList.add('loading');
        previewBtn.disabled = true;

        // Fetch event with flotilla
        currentEventData = await eventService.getEventById(eventId);

        // Hide empty state
        emptyState.style.display = 'none';

        // Render preview
        renderPreview(currentEventData);

        showToast('Preview loaded successfully', 'success');
    } catch (error) {
        console.error('Failed to load preview:', error);
        showToast(error.message || 'Failed to load preview', 'error');
    } finally {
        // Remove loading state
        previewBtn.classList.remove('loading');
        previewBtn.disabled = false;
    }
}

/**
 * Render preview
 */
function renderPreview(eventData) {
    const previewSection = document.getElementById('preview-section');
    const optionsSection = document.getElementById('options-section');
    const eventDetails = document.getElementById('event-details');
    const participantCount = document.getElementById('participant-count');
    const participantList = document.getElementById('participant-list');

    // Show sections
    previewSection.classList.remove('hidden');
    optionsSection.style.display = 'block';

    // Render event details
    const eventDate = new Date(eventData.event_date);
    eventDetails.innerHTML = `
        <strong>${eventData.event_id}</strong><br>
        ${eventDate.toLocaleDateString()} at ${eventData.start_time}
    `;

    // Get participants from flotilla
    const participants = [];
    if (eventData.flotilla && eventData.flotilla.assignments) {
        eventData.flotilla.assignments.forEach(assignment => {
            participants.push(`${assignment.boat_name} (${assignment.crew.length} crew)`);
        });
    }

    // Update participant count
    participantCount.textContent = participants.length;

    // Render participant list
    if (participants.length > 0) {
        participantList.innerHTML = participants.map(p => `<li>${p}</li>`).join('');
    } else {
        participantList.innerHTML = '<li>No participants assigned yet</li>';
    }
}

/**
 * Show confirmation modal
 */
function showConfirmationModal() {
    const modal = document.getElementById('confirm-modal');
    const confirmMessage = document.getElementById('confirm-message');
    const participantCount = document.getElementById('participant-count');

    const count = parseInt(participantCount.textContent) || 0;
    confirmMessage.textContent = `Are you sure you want to send notifications to ${count} participants?`;

    modal.classList.remove('hidden');
}

/**
 * Hide confirmation modal
 */
function hideConfirmationModal() {
    const modal = document.getElementById('confirm-modal');
    modal.classList.add('hidden');
}

/**
 * Send notifications
 */
async function sendNotifications() {
    const sendBtn = document.getElementById('send-btn');
    const eventSelect = document.getElementById('event-select');
    const includeCalendar = document.getElementById('include-calendar').checked;

    const eventId = eventSelect.value;
    if (!eventId) return;

    try {
        // Show loading state
        sendBtn.classList.add('loading');
        sendBtn.disabled = true;

        // Send notifications
        const result = await adminService.sendNotifications(eventId, includeCalendar);

        showToast(`Successfully sent ${result.emails_sent || 0} notifications`, 'success');
    } catch (error) {
        console.error('Failed to send notifications:', error);
        showToast(error.message || 'Failed to send notifications', 'error');
    } finally {
        // Remove loading state
        sendBtn.classList.remove('loading');
        sendBtn.disabled = false;
    }
}
