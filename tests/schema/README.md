# Test Schema Files

This directory contains SQL schema files used by integration tests for setting up in-memory SQLite databases.

## Files

- `001_initial_schema.sql` - Initial database schema (boats, crews, events, availability, history, etc.)
- `002_add_users_authentication.sql` - User authentication tables (users table)

## Purpose

Integration tests use in-memory SQLite databases (`sqlite::memory:`) and need to set up the schema before running tests. These SQL files are executed directly using PDO to create the necessary tables and indexes.

## Why SQL files instead of Phinx migrations?

While the application uses Phinx migrations for managing the production database schema, running Phinx migrations programmatically on in-memory test databases proved unreliable due to:

1. Complexity of configuring Phinx's migration manager for in-memory connections
2. Phinx's table builder methods having inconsistent behavior with in-memory SQLite
3. Additional overhead and dependencies that slow down test execution

Direct SQL execution provides:
- Fast, reliable schema setup for each test
- Simple, straightforward implementation
- No external dependencies beyond PDO
- Consistent behavior across different environments

## Maintenance

These schema files are based on the Phinx migrations in `database/migrations/`. When the database schema changes via new Phinx migrations, these test schema files should be updated to match.

To update these files:

1. Run Phinx migrations on a file-based database
2. Export the schema using `sqlite3 database/jaws.db .schema > tests/schema/schema.sql`
3. Split into appropriate files (initial schema and user authentication)
4. Test with integration tests to ensure compatibility

## Usage in Tests

Tests load these files and execute the SQL statements:

```php
private function runMigrations(): void
{
    $schemaFile = __DIR__ . '/../../../../schema/001_initial_schema.sql';
    $userSchemaFile = __DIR__ . '/../../../../schema/002_add_users_authentication.sql';

    foreach ([$schemaFile, $userSchemaFile] as $file) {
        if (file_exists($file)) {
            $schema = file_get_contents($file);
            $this->executeSqlStatements($schema);
        }
    }
}
```
