/**
 * Admin Service
 * Handles all admin-related API calls
 */

import * as apiService from './apiService.js';
import { buildApiUrl, API_CONFIG } from './config.js';

/**
 * Get matching data for an event (capacity analysis)
 * @param {string} eventId - Event identifier
 * @returns {Promise<Object>} Matching data with available boats, crews, and capacity summary
 */
export async function getMatchingData(eventId) {
    try {
        const url = buildApiUrl(API_CONFIG.ENDPOINTS.ADMIN_MATCHING, { eventId });
        const response = await apiService.get(url);

        if (!response.success) {
            throw new Error(response.message || 'Failed to load matching data');
        }

        return response.data;
    } catch (error) {
        console.error('AdminService: Failed to get matching data:', error);
        throw error;
    }
}

/**
 * Send email notifications for an event
 * @param {string} eventId - Event identifier
 * @param {boolean} includeCalendar - Whether to include calendar invites
 * @returns {Promise<Object>} Result with count of emails sent
 */
export async function sendNotifications(eventId, includeCalendar = true) {
    try {
        const url = buildApiUrl(API_CONFIG.ENDPOINTS.ADMIN_NOTIFICATIONS, { eventId });
        const response = await apiService.post(url, {
            include_calendar: includeCalendar
        });

        if (!response.success) {
            throw new Error(response.message || 'Failed to send notifications');
        }

        return response.data;
    } catch (error) {
        console.error('AdminService: Failed to send notifications:', error);
        throw error;
    }
}

/**
 * Get current season configuration
 * @returns {Promise<Object>} Season configuration data
 */
export async function getSeasonConfig() {
    try {
        const url = buildApiUrl(API_CONFIG.ENDPOINTS.ADMIN_CONFIG);
        const response = await apiService.get(url);

        if (!response.success) {
            throw new Error(response.message || 'Failed to load season configuration');
        }

        return response.data;
    } catch (error) {
        console.error('AdminService: Failed to get season config:', error);
        throw error;
    }
}

/**
 * Update season configuration
 * @param {Object} configData - Configuration data to update
 * @returns {Promise<Object>} Updated configuration
 */
export async function updateSeasonConfig(configData) {
    try {
        const url = buildApiUrl(API_CONFIG.ENDPOINTS.ADMIN_CONFIG);
        const response = await apiService.patch(url, configData);

        if (!response.success) {
            throw new Error(response.message || 'Failed to update season configuration');
        }

        return response.data;
    } catch (error) {
        console.error('AdminService: Failed to update season config:', error);
        throw error;
    }
}
