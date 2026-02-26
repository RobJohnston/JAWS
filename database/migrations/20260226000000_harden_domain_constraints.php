<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class HardenDomainConstraints extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS boats_validate_insert
            BEFORE INSERT ON boats
            BEGIN
                SELECT RAISE(ABORT, 'boats.min_berths must be >= 0')
                WHERE NEW.min_berths < 0;

                SELECT RAISE(ABORT, 'boats.max_berths must be >= min_berths')
                WHERE NEW.max_berths < NEW.min_berths;

                SELECT RAISE(ABORT, 'boats.assistance_required must be Yes or No')
                WHERE NEW.assistance_required NOT IN ('Yes', 'No');

                SELECT RAISE(ABORT, 'boats.social_preference must be Yes or No')
                WHERE NEW.social_preference NOT IN ('Yes', 'No');
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS boats_validate_update
            BEFORE UPDATE ON boats
            BEGIN
                SELECT RAISE(ABORT, 'boats.min_berths must be >= 0')
                WHERE NEW.min_berths < 0;

                SELECT RAISE(ABORT, 'boats.max_berths must be >= min_berths')
                WHERE NEW.max_berths < NEW.min_berths;

                SELECT RAISE(ABORT, 'boats.assistance_required must be Yes or No')
                WHERE NEW.assistance_required NOT IN ('Yes', 'No');

                SELECT RAISE(ABORT, 'boats.social_preference must be Yes or No')
                WHERE NEW.social_preference NOT IN ('Yes', 'No');
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS crews_validate_insert
            BEFORE INSERT ON crews
            BEGIN
                SELECT RAISE(ABORT, 'crews.skill must be 0, 1, or 2')
                WHERE NEW.skill NOT IN (0, 1, 2);

                SELECT RAISE(ABORT, 'crews.social_preference must be Yes or No')
                WHERE NEW.social_preference NOT IN ('Yes', 'No');
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS crews_validate_update
            BEFORE UPDATE ON crews
            BEGIN
                SELECT RAISE(ABORT, 'crews.skill must be 0, 1, or 2')
                WHERE NEW.skill NOT IN (0, 1, 2);

                SELECT RAISE(ABORT, 'crews.social_preference must be Yes or No')
                WHERE NEW.social_preference NOT IN ('Yes', 'No');
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS events_validate_insert
            BEFORE INSERT ON events
            BEGIN
                SELECT RAISE(ABORT, 'events.status must be upcoming, in_progress, completed, or cancelled')
                WHERE NEW.status NOT IN ('upcoming', 'in_progress', 'completed', 'cancelled');
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS events_validate_update
            BEFORE UPDATE ON events
            BEGIN
                SELECT RAISE(ABORT, 'events.status must be upcoming, in_progress, completed, or cancelled')
                WHERE NEW.status NOT IN ('upcoming', 'in_progress', 'completed', 'cancelled');
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS users_validate_insert
            BEFORE INSERT ON users
            BEGIN
                SELECT RAISE(ABORT, 'users.account_type must be crew or boat_owner')
                WHERE NEW.account_type NOT IN ('crew', 'boat_owner');

                SELECT RAISE(ABORT, 'users.is_admin must be 0, 1, or NULL')
                WHERE NEW.is_admin IS NOT NULL AND NEW.is_admin NOT IN (0, 1);
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS users_validate_update
            BEFORE UPDATE ON users
            BEGIN
                SELECT RAISE(ABORT, 'users.account_type must be crew or boat_owner')
                WHERE NEW.account_type NOT IN ('crew', 'boat_owner');

                SELECT RAISE(ABORT, 'users.is_admin must be 0, 1, or NULL')
                WHERE NEW.is_admin IS NOT NULL AND NEW.is_admin NOT IN (0, 1);
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS crew_availability_validate_insert
            BEFORE INSERT ON crew_availability
            BEGIN
                SELECT RAISE(ABORT, 'crew_availability.status must be 0, 1, 2, or 3')
                WHERE NEW.status NOT IN (0, 1, 2, 3);
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS crew_availability_validate_update
            BEFORE UPDATE ON crew_availability
            BEGIN
                SELECT RAISE(ABORT, 'crew_availability.status must be 0, 1, 2, or 3')
                WHERE NEW.status NOT IN (0, 1, 2, 3);
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS boat_availability_validate_insert
            BEFORE INSERT ON boat_availability
            BEGIN
                SELECT RAISE(ABORT, 'boat_availability.berths must be >= 0')
                WHERE NEW.berths < 0;
            END"
        );

        $this->execute(
            "CREATE TRIGGER IF NOT EXISTS boat_availability_validate_update
            BEFORE UPDATE ON boat_availability
            BEGIN
                SELECT RAISE(ABORT, 'boat_availability.berths must be >= 0')
                WHERE NEW.berths < 0;
            END"
        );
    }

    public function down(): void
    {
        $this->execute('DROP TRIGGER IF EXISTS boats_validate_insert');
        $this->execute('DROP TRIGGER IF EXISTS boats_validate_update');
        $this->execute('DROP TRIGGER IF EXISTS crews_validate_insert');
        $this->execute('DROP TRIGGER IF EXISTS crews_validate_update');
        $this->execute('DROP TRIGGER IF EXISTS events_validate_insert');
        $this->execute('DROP TRIGGER IF EXISTS events_validate_update');
        $this->execute('DROP TRIGGER IF EXISTS users_validate_insert');
        $this->execute('DROP TRIGGER IF EXISTS users_validate_update');
        $this->execute('DROP TRIGGER IF EXISTS crew_availability_validate_insert');
        $this->execute('DROP TRIGGER IF EXISTS crew_availability_validate_update');
        $this->execute('DROP TRIGGER IF EXISTS boat_availability_validate_insert');
        $this->execute('DROP TRIGGER IF EXISTS boat_availability_validate_update');
    }
}
