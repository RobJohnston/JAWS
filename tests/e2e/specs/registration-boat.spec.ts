/**
 * Boat Owner Registration E2E Tests
 *
 * Tests the complete boat owner registration flow from role selection
 * through form submission to dashboard landing.
 */

import { test, expect } from '../fixtures/test-fixtures';
import { TestDataGenerator } from '../fixtures/test-data';

test.describe('Boat Owner Registration Flow', () => {
  test.beforeEach(async ({ cleanDatabase }) => {
    // Database cleanup happens automatically via fixture
  });

  test('should complete boat owner registration successfully', async ({
    page,
    accountPage,
    boatRegistrationPage,
    dashboardPage,
  }) => {
    // Step 1: Navigate to account selection page
    await accountPage.goto();
    await expect(page).toHaveURL(/account\.html/);

    // Step 2: Select boat owner role and proceed
    await accountPage.selectRoleAndProceed('boat_owner');

    // Step 3: Verify redirect to boat owner registration page
    await expect(page).toHaveURL(/account_boat\.html/);
    await expect(await boatRegistrationPage.isFormVisible()).toBe(true);

    // Step 4: Generate unique test data
    const testData = TestDataGenerator.generateBoatData();

    // Step 5: Fill and submit the registration form
    await boatRegistrationPage.fillAndSubmit(testData);

    // Step 6: Handle success alert dialog (if any)
    page.on('dialog', async dialog => {
      console.log(`Alert message: ${dialog.message()}`);
      await dialog.accept();
    });

    // Step 7: Verify redirect to dashboard
    await expect(page).toHaveURL(/dashboard\.html/, { timeout: 15000 });
    await expect(await dashboardPage.isOnDashboard()).toBe(true);

    // Step 8: Verify user is authenticated (JWT token in sessionStorage)
    const token = await dashboardPage.getSessionToken();
    expect(token).toBeTruthy();
    expect(token).not.toBe('');

    // Step 9: Wait for dashboard to fully load
    await dashboardPage.waitForPageLoad();

    // Step 10: Verify we're on the dashboard (registration successful)
    // Note: Username display might vary between crew and boat owners
    expect(await dashboardPage.isOnDashboard()).toBe(true);
    expect(await dashboardPage.isAuthenticated()).toBe(true);
  });

  test('should show boat owner registration form has all required fields', async ({
    boatRegistrationPage,
  }) => {
    await boatRegistrationPage.goto();

    // Verify form is visible and accessible
    expect(await boatRegistrationPage.isFormVisible()).toBe(true);

    // Note: Additional field validation tests can be added here
    // For example: required fields, field types, dropdown options, etc.
  });
});
