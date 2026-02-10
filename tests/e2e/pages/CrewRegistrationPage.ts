/**
 * Crew Registration Page
 *
 * Page object for the crew registration form (/app/account_crew.html)
 * Handles crew member sign-up with all form fields
 */

import { BasePage } from './BasePage';
import { Locator } from '@playwright/test';

export interface CrewRegistrationData {
  firstName: string;
  lastName: string;
  email: string;
  password: string;
  confirmPassword: string;
  membershipNumber?: string;
  experience: 'none' | 'competent_crew' | 'competent_first_mate';
  whatsappGroup?: boolean;
}

export class CrewRegistrationPage extends BasePage {
  // Form field selectors
  private readonly firstNameInput: Locator;
  private readonly lastNameInput: Locator;
  private readonly emailInput: Locator;
  private readonly passwordInput: Locator;
  private readonly confirmPasswordInput: Locator;
  private readonly membershipNumberInput: Locator;
  private readonly experienceSelect: Locator;
  private readonly whatsappCheckbox: Locator;
  private readonly submitButton: Locator;

  constructor(page: any) {
    super(page);
    this.firstNameInput = this.page.locator('input#first_name');
    this.lastNameInput = this.page.locator('input#last_name');
    this.emailInput = this.page.locator('input#email');
    this.passwordInput = this.page.locator('input#password');
    this.confirmPasswordInput = this.page.locator('input#confirm_password');
    this.membershipNumberInput = this.page.locator('input#membership_number');
    this.experienceSelect = this.page.locator('select#experience');
    this.whatsappCheckbox = this.page.locator('input#whatsapp_group');
    this.submitButton = this.page.locator('button[type="submit"]');
  }

  /**
   * Navigate to crew registration page
   */
  async goto(): Promise<void> {
    await super.goto('/app/account_crew.html');
  }

  /**
   * Fill the entire registration form with provided data
   */
  async fillRegistrationForm(data: CrewRegistrationData): Promise<void> {
    await this.firstNameInput.fill(data.firstName);
    await this.lastNameInput.fill(data.lastName);
    await this.emailInput.fill(data.email);
    await this.passwordInput.fill(data.password);
    await this.confirmPasswordInput.fill(data.confirmPassword);

    if (data.membershipNumber) {
      await this.membershipNumberInput.fill(data.membershipNumber);
    }

    await this.experienceSelect.selectOption(data.experience);

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
  async fillAndSubmit(data: CrewRegistrationData): Promise<void> {
    await this.fillRegistrationForm(data);
    await this.submitForm();
  }

  /**
   * Get validation error message from browser
   * Useful for testing HTML5 validation
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
