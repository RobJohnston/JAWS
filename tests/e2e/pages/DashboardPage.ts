/**
 * Dashboard Page
 *
 * Page object for the main dashboard (/app/dashboard.html)
 * Used for verification after successful registration/login
 */

import { BasePage } from './BasePage';
import { Locator, expect } from '@playwright/test';

export class DashboardPage extends BasePage {
  // Main dashboard elements
  private readonly heroUsername: Locator;
  private readonly accountBadge: Locator;
  private readonly heroContent: Locator;

  constructor(page: any) {
    super(page);
    this.heroUsername = this.page.locator('#hero-username');
    this.accountBadge = this.page.locator('#account-badge');
    this.heroContent = this.page.locator('.hero-content');
  }

  /**
   * Navigate to dashboard page
   */
  async goto(): Promise<void> {
    await super.goto('/app/dashboard.html');
  }

  /**
   * Wait for dashboard to fully load
   * Waits for network to be idle and hero content to be visible
   */
  async waitForPageLoad(): Promise<void> {
    await this.page.waitForLoadState('networkidle');
    await this.heroContent.waitFor({ state: 'visible', timeout: 10000 });
  }

  /**
   * Get the welcome username text from the hero section
   * Returns the text content of the username element
   * Waits for the element to have non-empty text content
   */
  async getWelcomeUsername(): Promise<string | null> {
    try {
      // Wait for the element to be visible first
      await this.heroUsername.waitFor({ state: 'visible', timeout: 10000 });

      // Wait for the element to have non-empty text content
      // The dashboard JavaScript populates this asynchronously after page load
      await this.page.waitForFunction(
        () => {
          const element = document.querySelector('#hero-username');
          return element && element.textContent && element.textContent.trim().length > 0;
        },
        { timeout: 10000 }
      );

      return await this.heroUsername.textContent();
    } catch (error) {
      // If element doesn't exist or timeout, return null
      console.error('Failed to get welcome username:', error);
      return null;
    }
  }

  /**
   * Get the account badge text (e.g., "Crew Member", "Boat Owner")
   */
  async getAccountBadge(): Promise<string | null> {
    try {
      await this.accountBadge.waitFor({ state: 'visible', timeout: 5000 });
      return await this.accountBadge.textContent();
    } catch {
      return null;
    }
  }

  /**
   * Verify user is on the dashboard page
   */
  async isOnDashboard(): Promise<boolean> {
    return this.page.url().includes('dashboard.html');
  }

  /**
   * Verify user is authenticated by checking for JWT token
   */
  async isAuthenticated(): Promise<boolean> {
    const token = await this.getSessionToken();
    return token !== null && token.length > 0;
  }

  /**
   * Handle success alert dialog
   * Useful for accepting "Welcome" or "Registration successful" messages
   */
  setupAlertHandler(expectedMessage?: string): void {
    this.page.once('dialog', async dialog => {
      if (expectedMessage) {
        expect(dialog.message()).toContain(expectedMessage);
      }
      await dialog.accept();
    });
  }
}
