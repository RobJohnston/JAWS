/**
 * Custom Test Fixtures
 *
 * Extends Playwright's base test with custom fixtures for:
 * - Page Object instances
 * - Database cleanup
 * - Common test utilities
 */

import { test as base } from '@playwright/test';
import { AccountPage } from '../pages/AccountPage';
import { CrewRegistrationPage } from '../pages/CrewRegistrationPage';
import { BoatRegistrationPage } from '../pages/BoatRegistrationPage';
import { DashboardPage } from '../pages/DashboardPage';
import { DatabaseHelper } from './database-helper';

type CustomFixtures = {
  accountPage: AccountPage;
  crewRegistrationPage: CrewRegistrationPage;
  boatRegistrationPage: BoatRegistrationPage;
  dashboardPage: DashboardPage;
  cleanDatabase: void;
};

/**
 * Extended test with custom fixtures
 *
 * Usage:
 * import { test, expect } from '../fixtures/test-fixtures';
 *
 * test('my test', async ({ accountPage, crewRegistrationPage, cleanDatabase }) => {
 *   // Page objects are automatically instantiated
 *   // Database is automatically cleaned before and after the test
 * });
 */
export const test = base.extend<CustomFixtures>({
  // Account selection page fixture
  accountPage: async ({ page }, use) => {
    await use(new AccountPage(page));
  },

  // Crew registration page fixture
  crewRegistrationPage: async ({ page }, use) => {
    await use(new CrewRegistrationPage(page));
  },

  // Boat registration page fixture
  boatRegistrationPage: async ({ page }, use) => {
    await use(new BoatRegistrationPage(page));
  },

  // Dashboard page fixture
  dashboardPage: async ({ page }, use) => {
    await use(new DashboardPage(page));
  },

  // Database cleanup fixture
  // Runs before and after each test to ensure isolation
  cleanDatabase: [
    async ({}, use) => {
      // Setup: Clean database before test
      await DatabaseHelper.cleanupTestUsers();

      // Run the test
      await use();

      // Teardown: Clean database after test
      await DatabaseHelper.cleanupTestUsers();
    },
    { scope: 'test' }
  ],
});

// Re-export expect for convenience
export { expect } from '@playwright/test';
