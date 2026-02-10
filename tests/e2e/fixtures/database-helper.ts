/**
 * Database Helper
 *
 * Provides utilities for database management during E2E tests.
 * Handles test data cleanup to ensure test isolation.
 */

import { exec } from 'child_process';
import { promisify } from 'util';
import * as path from 'path';

const execAsync = promisify(exec);

export class DatabaseHelper {
  private static dbPath = path.resolve(__dirname, '../../../database/jaws.db');

  /**
   * Clean up test users from the database
   * Deletes all users, crews, and boats with @example.com email addresses
   *
   * This is faster than full database reset and preserves other test data
   */
  static async cleanupTestUsers(): Promise<void> {
    try {
      // Delete test users from all relevant tables
      // Note: Foreign key constraints with CASCADE will automatically delete related records
      const cleanupQueries = [
        "DELETE FROM crew_availability WHERE crew_id IN (SELECT id FROM crews WHERE email LIKE '%@example.com');",
        "DELETE FROM crew_history WHERE crew_id IN (SELECT id FROM crews WHERE email LIKE '%@example.com');",
        "DELETE FROM crew_whitelist WHERE crew_id IN (SELECT id FROM crews WHERE email LIKE '%@example.com');",
        "DELETE FROM crews WHERE email LIKE '%@example.com';",

        "DELETE FROM boat_availability WHERE boat_id IN (SELECT id FROM boats WHERE owner_email LIKE '%@example.com');",
        "DELETE FROM boat_history WHERE boat_id IN (SELECT id FROM boats WHERE owner_email LIKE '%@example.com');",
        "DELETE FROM boats WHERE owner_email LIKE '%@example.com';",

        "DELETE FROM users WHERE email LIKE '%@example.com';"
      ];

      const command = `sqlite3 "${this.dbPath}" "${cleanupQueries.join(' ')}"`;
      await execAsync(command);

      console.log('[DB] Test users cleaned up successfully');
    } catch (error) {
      console.error('[DB] Failed to cleanup test users:', error);
      throw error;
    }
  }

  /**
   * Reset database to clean state using Phinx migrations
   * WARNING: This is slower than cleanup. Only use when full reset is needed.
   */
  static async resetDatabase(): Promise<void> {
    try {
      console.log('[DB] Resetting database with Phinx migrations...');

      // Run migrations
      await execAsync('php vendor/bin/phinx migrate -e development');

      // Optionally seed test data
      await execAsync('php vendor/bin/phinx seed:run -e development');

      console.log('[DB] Database reset successfully');
    } catch (error) {
      console.error('[DB] Failed to reset database:', error);
      throw error;
    }
  }

  /**
   * Verify test user exists in database
   * Useful for debugging test failures
   */
  static async verifyUserExists(email: string): Promise<boolean> {
    try {
      const query = `SELECT COUNT(*) as count FROM users WHERE email = '${email}';`;
      const command = `sqlite3 "${this.dbPath}" "${query}"`;
      const { stdout } = await execAsync(command);
      const count = parseInt(stdout.trim(), 10);
      return count > 0;
    } catch (error) {
      console.error('[DB] Failed to verify user:', error);
      return false;
    }
  }

  /**
   * Get user ID by email
   * Useful for debugging and verification
   */
  static async getUserId(email: string): Promise<number | null> {
    try {
      const query = `SELECT id FROM users WHERE email = '${email}';`;
      const command = `sqlite3 "${this.dbPath}" "${query}"`;
      const { stdout } = await execAsync(command);
      const id = parseInt(stdout.trim(), 10);
      return isNaN(id) ? null : id;
    } catch (error) {
      console.error('[DB] Failed to get user ID:', error);
      return null;
    }
  }
}
