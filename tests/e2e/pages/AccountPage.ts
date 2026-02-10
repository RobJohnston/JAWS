/**
 * Account Page
 *
 * Page object for the account selection page (/app/account.html)
 * Users choose between crew member or boat owner roles
 */

import { BasePage } from './BasePage';
import { Locator } from '@playwright/test';

export class AccountPage extends BasePage {
  // Selectors
  private readonly crewRadio: Locator;
  private readonly boatOwnerRadio: Locator;
  private readonly crewLabel: Locator;
  private readonly boatOwnerLabel: Locator;
  private readonly submitButton: Locator;

  constructor(page: any) {
    super(page);
    this.crewRadio = this.page.locator('input#crew');
    this.boatOwnerRadio = this.page.locator('input#boat_owner');
    this.crewLabel = this.page.locator('label[for="crew"]');
    this.boatOwnerLabel = this.page.locator('label[for="boat_owner"]');
    this.submitButton = this.page.locator('button[type="submit"]');
  }

  /**
   * Navigate to account selection page
   */
  async goto(): Promise<void> {
    await super.goto('/app/account.html');
  }

  /**
   * Select crew member role
   * Clicks the label since the radio input is visually hidden
   */
  async selectCrewRole(): Promise<void> {
    await this.crewLabel.click();
  }

  /**
   * Select boat owner role
   * Clicks the label since the radio input is visually hidden
   */
  async selectBoatOwnerRole(): Promise<void> {
    await this.boatOwnerLabel.click();
  }

  /**
   * Click the "Let's Go!" submit button
   */
  async clickSubmit(): Promise<void> {
    await this.submitButton.click();
  }

  /**
   * Select a role and proceed to the appropriate registration page
   *
   * @param role - 'crew' or 'boat_owner'
   */
  async selectRoleAndProceed(role: 'crew' | 'boat_owner'): Promise<void> {
    if (role === 'crew') {
      await this.selectCrewRole();
    } else {
      await this.selectBoatOwnerRole();
    }
    await this.clickSubmit();
  }

  /**
   * Verify crew role is selected
   */
  async isCrewRoleSelected(): Promise<boolean> {
    return await this.crewRadio.isChecked();
  }

  /**
   * Verify boat owner role is selected
   */
  async isBoatOwnerRoleSelected(): Promise<boolean> {
    return await this.boatOwnerRadio.isChecked();
  }
}
