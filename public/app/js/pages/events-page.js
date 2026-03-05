/**
 * Events Page Module
 * Handles events page personalization and dynamic event rendering
 */

import { isSignedIn, getCurrentUser, signOut } from '../authService.js';
import { updateAuthenticatedNavigation, addAdminLink } from '../navigationService.js';
import * as CalendarService from '../calendarService.js';

// Update navigation based on auth state
if (await isSignedIn()) {
    const user = await getCurrentUser();
    updateAuthenticatedNavigation(user, signOut);
    addAdminLink(user);

    // Send signed-in users to their dashboard, not the guest join page
    document.querySelectorAll('a[href="account.html"]').forEach(a => {
        a.href = 'dashboard.html';
    });
}

/**
 * Render a single event with its flotilla information
 * @param {Object} event - Event object with flotilla and timing data
 * @param {boolean} authenticated - Whether the current user is signed in
 * @returns {DocumentFragment} DOM fragment containing the event HTML
 */
function renderEvent(event, authenticated) {
    const fragment = document.createDocumentFragment();
    const status = CalendarService.getEventStatus(event);

    // --- Date heading ---
    const scheduleDateDiv = document.createElement('div');
    scheduleDateDiv.className = `schedule-date${status === 'today' ? ' is-today' : ''}`;

    const timeEl = document.createElement('time');
    timeEl.setAttribute('datetime', event.date ?? event.eventId);
    timeEl.textContent = CalendarService.formatDisplayDate(event.date ?? event.eventId);
    scheduleDateDiv.appendChild(timeEl);

    // Show event times if available
    if (event.startTime && event.finishTime) {
        const timesSpan = document.createElement('span');
        timesSpan.className = 'event-times';
        timesSpan.textContent = ` · ${CalendarService.formatTime(event.startTime)}–${CalendarService.formatTime(event.finishTime)}`;
        scheduleDateDiv.appendChild(timesSpan);
    }

    fragment.appendChild(scheduleDateDiv);

    const hasAssignments = CalendarService.hasCrewedBoats(event);
    const hasWaitlist = CalendarService.hasWaitlist(event);

    // --- No assignments yet state ---
    if (!hasAssignments && !hasWaitlist) {
        const noAssign = document.createElement('div');
        noAssign.className = 'no-assignments';
        noAssign.textContent = 'No assignments yet — check back closer to the event.';
        fragment.appendChild(noAssign);
    }

    // --- Crewed boats ---
    if (hasAssignments) {
        event.flotilla.crewedBoats.forEach(assignment => {
            const boatDiv = document.createElement('div');
            boatDiv.className = 'boat-assignment';

            // Boat name
            const boatTag = document.createElement('span');
            boatTag.className = 'crew-tag crew-tag--boat';
            boatTag.textContent = `⛵ ${assignment.boat.displayName}`;
            boatDiv.appendChild(boatTag);

            // Crew list
            const crewListDiv = document.createElement('div');
            crewListDiv.className = 'crew-list';
            crewListDiv.setAttribute('aria-label', `Crew aboard ${assignment.boat.displayName}`);

            assignment.crews.forEach(crew => {
                const crewTag = document.createElement('span');
                crewTag.className = 'crew-tag';
                crewTag.textContent = crew.displayName;
                crewListDiv.appendChild(crewTag);
            });

            boatDiv.appendChild(crewListDiv);
            fragment.appendChild(boatDiv);
        });
    }

    // --- Waitlist ---
    if (hasWaitlist) {
        const waitlistDiv = document.createElement('div');
        waitlistDiv.className = 'waitlist';

        const waitlistHeading = document.createElement('h4');
        waitlistHeading.textContent = 'Waitlist';
        waitlistDiv.appendChild(waitlistHeading);

        // Waitlisted boats
        const waitlistedBoats = CalendarService.getWaitlistedBoats(event);
        if (waitlistedBoats.length > 0) {
            const boatList = document.createElement('div');
            boatList.className = 'crew-list';
            boatList.setAttribute('aria-label', 'Waitlisted boats');

            waitlistedBoats.forEach(boat => {
                const tag = document.createElement('span');
                tag.className = 'crew-tag crew-tag--boat';
                tag.textContent = `⛵ ${boat.displayName}`;
                boatList.appendChild(tag);
            });
            waitlistDiv.appendChild(boatList);
        }

        // Waitlisted crews
        const waitlistedCrews = CalendarService.getWaitlistedCrews(event);
        if (waitlistedCrews.length > 0) {
            const crewList = document.createElement('div');
            crewList.className = 'crew-list';
            crewList.setAttribute('aria-label', 'Waitlisted crew');

            waitlistedCrews.forEach(crew => {
                const tag = document.createElement('span');
                tag.className = 'crew-tag';
                tag.textContent = crew.displayName;
                crewList.appendChild(tag);
            });
            waitlistDiv.appendChild(crewList);
        }

        fragment.appendChild(waitlistDiv);
    }

    // --- Register CTA (upcoming events only) ---
    if (status === 'upcoming') {
        const ctaDiv = document.createElement('div');
        ctaDiv.className = 'register-cta';
        const ctaLink = document.createElement('a');
        ctaLink.href = authenticated ? 'dashboard.html' : 'signin.html';
        ctaLink.textContent = authenticated ? 'Update your availability →' : 'Register for this event →';
        ctaDiv.appendChild(ctaLink);
        fragment.appendChild(ctaDiv);
    }

    return fragment;
}

/**
 * Render all events to the events container
 */
async function renderAllEvents() {
    const container = document.getElementById('events-container');

    if (!container) {
        console.error('Events container not found');
        return;
    }

    // Show loading state
    container.innerHTML = '<div class="loading-state">Loading events...</div>';

    const authenticated = await isSignedIn();

    try {
        const events = await CalendarService.getEventsWithFlotilla();

        container.innerHTML = '';

        if (!events || events.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">📅</div>
                    <p><strong>No events scheduled</strong></p>
                    <p>Check back later for upcoming sailing events!</p>
                </div>
            `;
            return;
        }

        events.forEach(event => {
            container.appendChild(renderEvent(event, authenticated));
        });

    } catch (error) {
        console.error('Failed to load events:', error);

        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';

        const strong = document.createElement('strong');
        strong.textContent = 'Unable to load events';
        errorDiv.appendChild(strong);

        const p1 = document.createElement('p');
        p1.textContent = 'There was a problem loading the event schedule. Please try refreshing the page.';
        errorDiv.appendChild(p1);

        const p2 = document.createElement('p');
        p2.style.cssText = 'margin-top: 0.5rem; font-size: 0.9em; opacity: 0.8;';
        p2.textContent = `Error: ${error.message}`;
        errorDiv.appendChild(p2);

        container.innerHTML = '';
        container.appendChild(errorDiv);
    }
}

// Module scripts are deferred — DOM is already ready at this point
renderAllEvents();
