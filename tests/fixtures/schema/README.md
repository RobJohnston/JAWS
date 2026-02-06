# Test Schema Fixtures

This directory contains SQL schema files used by integration tests.

## Files

- `001_initial_schema.sql` - Initial database schema (boats, crews, events, availability, history, etc.)
- `002_add_users_authentication.sql` - User authentication tables (users table)

## Purpose

Integration tests use in-memory SQLite databases (`sqlite::memory:`) and need to set up the schema before running tests. These SQL files are executed directly using PDO to create the necessary tables and indexes.

## Why Not Use Phinx Migrations?

Phinx programmatic usage proved complex for in-memory testing. Direct SQL execution is a simpler, more reliable approach for test database setup.

## Origin

These files are copies of the original SQL migrations that were used before the project migrated to Phinx. They have been preserved here specifically for testing purposes and are kept in sync with the actual Phinx migration schema.

## Usage

Tests load these files and execute the SQL statements:

```php
$schemaFile = __DIR__ . '/../../../../fixtures/schema/001_initial_schema.sql';
$userSchemaFile = __DIR__ . '/../../../../fixtures/schema/002_add_users_authentication.sql';

foreach ([$schemaFile, $userSchemaFile] as $file) {
    if (file_exists($file)) {
        $schema = file_get_contents($file);
        $this->executeSqlStatements($schema);
    }
}
```

## Maintenance

If the database schema changes (via Phinx migrations), these test fixtures should be updated to match the new schema to ensure tests continue to work correctly.
