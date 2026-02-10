/**
 * Base Page
 *
 * Abstract base class for all page objects.
 * Provides common utilities and helper methods.
 */

import { Page } from '@playwright/test';

export abstract class BasePage {
  constructor(protected page: Page) {}

  /**
   * Navigate to a path relative to baseURL
   */
  async goto(path: string): Promise<void> {
    await this.page.goto(path);
  }

  /**
   * Wait for navigation to a specific URL pattern
   */
  async waitForNavigation(urlPattern: string | RegExp): Promise<void> {
    await this.page.waitForURL(urlPattern);
  }

  /**
   * Get JWT token from sessionStorage
   * The token is stored with key 'nsc_auth_token' by tokenService.js
   */
  async getSessionToken(): Promise<string | null> {
    return this.page.evaluate(() => sessionStorage.getItem('nsc_auth_token'));
  }

  /**
   * Check if currently on a specific page
   */
  async isOnPage(url: string): Promise<boolean> {
    return this.page.url().includes(url);
  }

  /**
   * Get current page URL
   */
  async getCurrentUrl(): Promise<string> {
    return this.page.url();
  }

  /**
   * Wait for page to be fully loaded
   */
  async waitForPageLoad(): Promise<void> {
    await this.page.waitForLoadState('networkidle');
  }
}
