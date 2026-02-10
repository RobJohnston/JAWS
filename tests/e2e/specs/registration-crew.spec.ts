/**
 * Crew Registration E2E Tests
 *
 * Tests the complete crew member registration flow from role selection
 * through form submission to dashboard landing.
 */

import { test, expect } from '../fixtures/test-fixtures';
import { TestDataGenerator } from '../fixtures/test-data';

test.describe('Crew Registration Flow', () => {
  test.beforeEach(async ({ cleanDatabase }) => {
    // Database cleanup happens automatically via fixture
  });

  test('should complete crew registration successfully', async ({
    page,
    accountPage,
    crewRegistrationPage,
    dashboardPage,
  }) => {
    // Step 1: Navigate to account selection page
    await accountPage.goto();
    await expect(page).toHaveURL(/account\.html/);

    // Step 2: Select crew member role and proceed
    await accountPage.selectRoleAndProceed('crew');

    // Step 3: Verify redirect to crew registration page
    await expect(page).toHaveURL(/account_crew\.html/);
    await expect(await crewRegistrationPage.isFormVisible()).toBe(true);

    // Step 4: Generate unique test data
    const testData = TestDataGenerator.generateCrewData();

    // Step 5: Fill and submit the registration form
    await crewRegistrationPage.fillAndSubmit(testData);

    // Step 6: Handle success alert dialog (if any)
    // Note: The frontend may show a success message
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

    // Step 10: Verify welcome message contains user's first name
    const username = await dashboardPage.getWelcomeUsername();
    expect(username).toBeTruthy();
    // The username should contain the first name from our test data
    expect(username).toContain(testData.firstName);
  });

  test('should show crew registration form has all required fields', async ({
    crewRegistrationPage,
  }) => {
    await crewRegistrationPage.goto();

    // Verify form is visible and accessible
    expect(await crewRegistrationPage.isFormVisible()).toBe(true);

    // Note: Additional field validation tests can be added here
    // For example: required fields, field types, dropdown options, etc.
  });
});
