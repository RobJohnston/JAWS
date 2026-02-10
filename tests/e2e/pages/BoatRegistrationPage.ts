/**
 * Boat Registration Page
 *
 * Page object for the boat owner registration form (/app/account_boat.html)
 * Handles boat owner and boat details sign-up
 */

import { BasePage } from './BasePage';
import { Locator } from '@playwright/test';

export interface BoatRegistrationData {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  password: string;
  confirmPassword: string;
  boatName: string;
  minCrew: '1' | '2' | '3' | '4';
  maxCrew: '2' | '3' | '4' | '5' | '6';
  requestFirstMate?: boolean;
  whatsappGroup?: boolean;
}

export class BoatRegistrationPage extends BasePage {
  // Owner information selectors
  private readonly firstNameInput: Locator;
  private readonly lastNameInput: Locator;
  private readonly emailInput: Locator;
  private readonly phoneInput: Locator;
  private readonly passwordInput: Locator;
  private readonly confirmPasswordInput: Locator;

  // Boat information selectors
  private readonly boatNameInput: Locator;
  private readonly minCrewSelect: Locator;
  private readonly maxCrewSelect: Locator;
  private readonly requestFirstMateCheckbox: Locator;
  private readonly whatsappCheckbox: Locator;

  private readonly submitButton: Locator;

  constructor(page: any) {
    super(page);

    // Owner fields
    this.firstNameInput = this.page.locator('input#first_name');
    this.lastNameInput = this.page.locator('input#last_name');
    this.emailInput = this.page.locator('input#email');
    this.phoneInput = this.page.locator('input#phone');
    this.passwordInput = this.page.locator('input#password');
    this.confirmPasswordInput = this.page.locator('input#confirm_password');

    // Boat fields
    this.boatNameInput = this.page.locator('input#boat_name');
    this.minCrewSelect = this.page.locator('select#min_crew');
    this.maxCrewSelect = this.page.locator('select#max_crew');
    this.requestFirstMateCheckbox = this.page.locator('input#request_first_mate');
    this.whatsappCheckbox = this.page.locator('input#whatsapp_group');

    this.submitButton = this.page.locator('button[type="submit"]');
  }

  /**
   * Navigate to boat owner registration page
   */
  async goto(): Promise<void> {
    await super.goto('/app/account_boat.html');
  }

  /**
   * Fill the entire registration form with provided data
   */
  async fillRegistrationForm(data: BoatRegistrationData): Promise<void> {
    // Owner information
    await this.firstNameInput.fill(data.firstName);
    await this.lastNameInput.fill(data.lastName);
    await this.emailInput.fill(data.email);
    await this.phoneInput.fill(data.phone);
    await this.passwordInput.fill(data.password);
    await this.confirmPasswordInput.fill(data.confirmPassword);

    // Boat information
    await this.boatNameInput.fill(data.boatName);
    await this.minCrewSelect.selectOption(data.minCrew);
    await this.maxCrewSelect.selectOption(data.maxCrew);

    if (data.requestFirstMate) {
      await this.requestFirstMateCheckbox.check();
    }

    if (data.whatsappGroup) {
      await this.whatsappCheckbox.check();
    }
  }

  /**
   * Submit the registration form
   */
  async submitForm(): Promise<void> {
    await this.submitButton.click();
  }

  /**
   * Fill form and submit in one action
   */
  async fillAndSubmit(data: BoatRegistrationData): Promise<void> {
    await this.fillRegistrationForm(data);
    await this.submitForm();
  }

  /**
   * Get validation error message from browser
   */
  async getValidationError(): Promise<string> {
    const invalidField = this.page.locator(':invalid').first();
    return invalidField.evaluate((el: HTMLInputElement) => el.validationMessage);
  }

  /**
   * Verify form is on the page
   */
  async isFormVisible(): Promise<boolean> {
    return await this.submitButton.isVisible();
  }
}
