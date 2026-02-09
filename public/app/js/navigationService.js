/**
 * Navigation Service
 * Provides common navigation update logic for authenticated users
 *
 * This module encapsulates the repetitive pattern of updating navigation
 * based on authentication state across multiple page modules.
 */

/**
 * Updates navigation UI when user is authenticated
 *
 * This handles:
 * - Changing nav-account to show Dashboard link
 * - Adding user greeting with first name
 * - Adding sign out button to navigation
 *
 * @param {Object} user - User object from AuthService.getCurrentUser()
 * @param {Function} signOut - Sign out function from AuthService
 * @returns {boolean} true if navigation was updated, false if user not provided
 *
 * @example
 * import { isSignedIn, getCurrentUser, signOut } from '../authService.js';
 * import { updateAuthenticatedNavigation } from '../navigationService.js';
 *
 * if (isSignedIn()) {
 *     const user = getCurrentUser();
 *     updateAuthenticatedNavigation(user, signOut);
 * }
 */
export function updateAuthenticatedNavigation(user, signOut) {
    if (!user) {
        console.warn('NavigationService: No user provided');
        return false;
    }

    const navAccount = document.getElementById('nav-account');
    const mainNav = document.getElementById('main-nav');

    if (!navAccount || !mainNav) {
        console.warn('NavigationService: Required DOM elements not found (nav-account, main-nav)');
        return false;
    }

    // Update nav-account to show Dashboard link
    navAccount.innerHTML = '<a href="dashboard.html">Dashboard</a>';

    // Add user greeting
    const userGreeting = document.createElement('li');
    userGreeting.innerHTML = '<span class="user-greeting">Hi, <span id="nav-username">' + user.profile.firstName; + '</span>!</span>';
    mainNav.appendChild(userGreeting);

    // Add sign out button
    const signOutLi = document.createElement('li');
    signOutLi.innerHTML = '<a href="#" class="sign-out-btn">Sign Out</a>';
    signOutLi.querySelector('a').addEventListener('click', (e) => {
        e.preventDefault();
        signOut();
    });
    mainNav.appendChild(signOutLi);

    return true;
}

/**
 * Adds admin link to navigation if user is an administrator
 *
 * This conditionally shows the "Admin" navigation link only to admin users.
 * Should be called after updateAuthenticatedNavigation().
 *
 * @param {Object} user - User object from AuthService.getCurrentUser()
 * @returns {boolean} true if admin link was added, false otherwise
 *
 * @example
 * import { updateAuthenticatedNavigation, addAdminLink } from '../navigationService.js';
 *
 * updateAuthenticatedNavigation(user, signOut);
 * addAdminLink(user);
 */
export function addAdminLink(user) {
    if (!user || !user.isAdmin) {
        return false;
    }

    const navAccount = document.getElementById('nav-account');
    if (!navAccount) {
        console.warn('NavigationService: nav-account element not found');
        return false;
    }

    // Insert Admin link after Dashboard link
    const adminLi = document.createElement('li');
    adminLi.id = 'nav-admin';
    adminLi.innerHTML = '<a href="admin.html">Admin</a>';

    // Find the parent ul and insert after nav-account
    const parentUl = navAccount.parentElement;
    const nextSibling = navAccount.nextElementSibling;
    if (nextSibling) {
        parentUl.insertBefore(adminLi, nextSibling);
    } else {
        parentUl.appendChild(adminLi);
    }

    return true;
}
