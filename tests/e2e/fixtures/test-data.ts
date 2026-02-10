/**
 * Test Data Generator
 *
 * Generates unique, valid test data for crew and boat owner registration.
 * Uses timestamps and counters to ensure email uniqueness across test runs.
 */

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

export class TestDataGenerator {
  private static uniqueCounter = 0;

  /**
   * Generate unique email address for testing
   * Format: prefix.timestamp.counter@example.com
   */
  static generateUniqueEmail(prefix: string = 'test'): string {
    const timestamp = Date.now();
    const counter = ++this.uniqueCounter;
    return `${prefix}.${timestamp}.${counter}@example.com`;
  }

  /**
   * Generate valid password that meets all requirements:
   * - At least 8 characters
   * - One uppercase letter (A-Z)
   * - One lowercase letter (a-z)
   * - One number (0-9)
   */
  static generateValidPassword(): string {
    return 'TestPass123!';
  }

  /**
   * Generate complete crew registration data
   */
  static generateCrewData(): CrewRegistrationData {
    const uniqueId = Date.now();
    const password = this.generateValidPassword();

    return {
      firstName: `John${uniqueId}`,
      lastName: 'Doe',
      email: this.generateUniqueEmail('crew'),
      password: password,
      confirmPassword: password,
      membershipNumber: `NSC${uniqueId}`,
      experience: 'competent_crew',
      whatsappGroup: true,
    };
  }

  /**
   * Generate complete boat owner registration data
   */
  static generateBoatData(): BoatRegistrationData {
    const uniqueId = Date.now();
    const password = this.generateValidPassword();

    return {
      firstName: `Captain${uniqueId}`,
      lastName: 'Smith',
      email: this.generateUniqueEmail('boat'),
      phone: '(555) 123-4567',
      password: password,
      confirmPassword: password,
      boatName: `Sailaway${uniqueId}`,
      minCrew: '2',
      maxCrew: '4',
      requestFirstMate: false,
      whatsappGroup: true,
    };
  }

  /**
   * Generate crew data with custom overrides
   */
  static generateCustomCrewData(overrides: Partial<CrewRegistrationData>): CrewRegistrationData {
    return {
      ...this.generateCrewData(),
      ...overrides,
    };
  }

  /**
   * Generate boat data with custom overrides
   */
  static generateCustomBoatData(overrides: Partial<BoatRegistrationData>): BoatRegistrationData {
    return {
      ...this.generateBoatData(),
      ...overrides,
    };
  }
}
