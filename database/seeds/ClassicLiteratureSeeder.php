<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Classic Literature Seeder
 *
 * Five fictional boats and 19 crew drawn from classic maritime literature.
 *
 * Boats (minBerths=7, maxBerths=18 combined):
 *   - Hispaniola    (Treasure Island)       min=1, max=4, assistance_required='Yes'
 *   - HMS Bounty    (Mutiny on the Bounty)  min=1, max=4
 *   - Swallow       (Swallows & Amazons)    min=1, max=3
 *   - Amazon        (Swallows & Amazons)    min=1, max=2
 *   - Pequod        (Moby-Dick)             min=3, max=5
 *
 * Crew (19):
 *   Treasure Island:      Silver, Hawkins, Trelawney, Livesey, Gunn
 *   Mutiny on the Bounty: Christian, Adams, McCoy, Young, Quintal
 *   Swallows & Amazons:   Susan, Titty, Roger, Peggy
 *   Moby-Dick:            Starbuck, Queequeg, Stubb, Flask, Ishmael
 *
 * Capacity cases (tiered availability):
 *   Events  1-3  (May 29–Jun 12):  5 crew  < minBerths=7  → Case 1 (boats waitlisted)
 *   Events  4-14 (Jun 19–Aug 29): 19 crew  > maxBerths=18 → Case 2 (1 crew waitlisted)
 *   Events 15-18 (Sep 4–Sep 25):  12 crew, 7≤12≤18        → Case 3 (perfect fit)
 *
 * Assignment rules exercised:
 *   ASSIST    — Hispaniola has assistance_required='Yes'; skill-2 crew: Silver, Christian, Starbuck, Queequeg
 *   WHITELIST — Susan→hispaniola+swallow; Titty,Roger→swallow; Peggy→amazon
 *   PARTNER   — Trelawney + Livesey
 *   HIGH_SKILL/LOW_SKILL — 5 boats, 3 skill levels
 *   REPEAT    — Silver: 3× history on hispaniola; Quintal: 3× unassigned
 *
 * rank_membership=1 (non-members): Hawkins, Gunn, Quintal, Ishmael
 *
 * Usage:
 *   vendor/bin/phinx seed:run -s ClassicLiteratureSeeder
 *
 * Test credentials: All users have password "Password123"
 */
class ClassicLiteratureSeeder extends AbstractSeed
{
    public function run(): void
    {
        // ====================================================================
        // Clear existing data (for idempotency)
        // ====================================================================
        $this->execute('DELETE FROM flotillas');
        $this->execute('DELETE FROM crew_whitelist');
        $this->execute('DELETE FROM crew_history');
        $this->execute('DELETE FROM boat_history');
        $this->execute('DELETE FROM crew_availability');
        $this->execute('DELETE FROM boat_availability');
        $this->execute('DELETE FROM crews');
        $this->execute('DELETE FROM boats');
        $this->execute('DELETE FROM users');
        $this->execute('DELETE FROM events');

        // ====================================================================
        // Seed past events — required before crew_history (FK constraint)
        // Three events from the 2025 season provide REPEAT rule history data
        // ====================================================================
        $this->execute("
            INSERT INTO events (event_id, event_date, start_time, finish_time, status)
            VALUES
                ('Fri Sep 26 2025', '2025-09-26', '12:45:00', '17:00:00', 'past'),
                ('Fri Oct 3 2025',  '2025-10-03', '12:45:00', '17:00:00', 'past'),
                ('Fri Oct 10 2025', '2025-10-10', '12:45:00', '17:00:00', 'past')
        ");

        // ====================================================================
        // Seed upcoming events — Full 2026 Season (18 events)
        // ====================================================================
        $events = [
            ['event_id' => 'Fri May 29', 'event_date' => '2026-05-29', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Jun 5',  'event_date' => '2026-06-05', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Jun 12', 'event_date' => '2026-06-12', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Jun 19', 'event_date' => '2026-06-19', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Sat Jun 27', 'event_date' => '2026-06-27', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Jul 3',  'event_date' => '2026-07-03', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Jul 10', 'event_date' => '2026-07-10', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Sat Jul 18', 'event_date' => '2026-07-18', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Jul 24', 'event_date' => '2026-07-24', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Jul 31', 'event_date' => '2026-07-31', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Sat Aug 8',  'event_date' => '2026-08-08', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Aug 14', 'event_date' => '2026-08-14', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Aug 21', 'event_date' => '2026-08-21', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Sat Aug 29', 'event_date' => '2026-08-29', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Sep 4',  'event_date' => '2026-09-04', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Sep 11', 'event_date' => '2026-09-11', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Sep 18', 'event_date' => '2026-09-18', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
            ['event_id' => 'Fri Sep 25', 'event_date' => '2026-09-25', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
        ];

        $this->table('events')->insert($events)->saveData();

        // ====================================================================
        // Seed users — 5 boat owners + 19 crew members
        // ====================================================================
        $passwordHash = password_hash('Password123', PASSWORD_DEFAULT);

        $users = [
            // Boat owners
            ['email' => 'captain.smollett@example.com',   'password_hash' => $passwordHash, 'account_type' => 'boat_owner', 'is_admin' => 1],
            ['email' => 'william.bligh@example.com',      'password_hash' => $passwordHash, 'account_type' => 'boat_owner', 'is_admin' => 0],
            ['email' => 'john.walker@example.com',        'password_hash' => $passwordHash, 'account_type' => 'boat_owner', 'is_admin' => 0],
            ['email' => 'nancy.blackett@example.com',     'password_hash' => $passwordHash, 'account_type' => 'boat_owner', 'is_admin' => 0],
            ['email' => 'captain.ahab@example.com',       'password_hash' => $passwordHash, 'account_type' => 'boat_owner', 'is_admin' => 0],
            // Crew — Treasure Island
            ['email' => 'long.john.silver@example.com',   'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'jim.hawkins@example.com',        'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'squire.trelawney@example.com',   'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'dr.livesey@example.com',         'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'ben.gunn@example.com',           'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            // Crew — Mutiny on the Bounty
            ['email' => 'fletcher.christian@example.com', 'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'john.adams@example.com',         'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'william.mccoy@example.com',      'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'ned.young@example.com',          'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'matthew.quintal@example.com',    'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            // Crew — Swallows & Amazons
            ['email' => 'susan.walker@example.com',       'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'titty.walker@example.com',       'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'roger.walker@example.com',       'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'peggy.blackett@example.com',     'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            // Crew — Moby-Dick
            ['email' => 'starbuck@example.com',           'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'queequeg@example.com',           'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'stubb@example.com',              'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'flask@example.com',              'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'ishmael@example.com',            'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
        ];

        $this->table('users')->insert($users)->saveData();

        // ====================================================================
        // Seed boats — 5 vessels
        //
        // Hispaniola: assistance_required='Yes' activates the ASSIST rule.
        //   Skill-2 crew (Silver, Christian, Starbuck, Queequeg) are candidates.
        //
        // Pequod: min_berths=3 ensures it contributes meaningfully to minBerths=7.
        //   Combined: minBerths=7, maxBerths=18
        // ====================================================================
        $this->execute("
            INSERT INTO boats (key, display_name, owner_first_name, owner_last_name, owner_mobile,
                               min_berths, max_berths, assistance_required, social_preference,
                               rank_flexibility, rank_absence, owner_user_id)
            VALUES
                ('hispaniola', 'Hispaniola', 'Alexander', 'Smollett', '555-HISPANIOLA',
                 1, 4, 'Yes', 'No', 1, 0,
                 (SELECT id FROM users WHERE email = 'captain.smollett@example.com')),

                ('hms-bounty', 'HMS Bounty', 'William', 'Bligh', '555-BOUNTY',
                 1, 4, 'No', 'No', 1, 0,
                 (SELECT id FROM users WHERE email = 'william.bligh@example.com')),

                ('swallow', 'Swallow', 'John', 'Walker', '555-SWALLOW',
                 1, 3, 'No', 'No', 1, 0,
                 (SELECT id FROM users WHERE email = 'john.walker@example.com')),

                ('amazon', 'Amazon', 'Nancy', 'Blackett', '555-AMAZON',
                 1, 2, 'No', 'No', 1, 0,
                 (SELECT id FROM users WHERE email = 'nancy.blackett@example.com')),

                ('pequod', 'Pequod', 'Captain', 'Ahab', '555-PEQUOD',
                 3, 5, 'No', 'No', 1, 0,
                 (SELECT id FROM users WHERE email = 'captain.ahab@example.com'))
        ");

        // ====================================================================
        // Seed crew — 19 members across 4 source works
        //
        // rank_membership=1 (non-NSC-member) for: Hawkins, Gunn, Quintal, Ishmael
        // PARTNER pair: Trelawney (partner_key='dr-livesey') + Livesey (partner_key='squire-trelawney')
        // Skill distribution: skill-0 × 4, skill-1 × 10, skill-2 × 5
        // ====================================================================
        $this->execute("
            INSERT INTO crews (key, display_name, first_name, last_name, partner_key, mobile,
                               skill, membership_number, rank_membership, user_id)
            VALUES
                -- Treasure Island
                ('long-john-silver',  'Long John Silver',  'John',     'Silver',    NULL,              '555-PARROT',   2, 'NSC400', 0, (SELECT id FROM users WHERE email = 'long.john.silver@example.com')),
                ('jim-hawkins',       'Jim Hawkins',       'Jim',      'Hawkins',   NULL,              '555-CABIN',    0, NULL,     1, (SELECT id FROM users WHERE email = 'jim.hawkins@example.com')),
                ('squire-trelawney',  'Squire Trelawney',  'John',     'Trelawney', 'dr-livesey',      '555-SQUIRE',   1, 'NSC401', 0, (SELECT id FROM users WHERE email = 'squire.trelawney@example.com')),
                ('dr-livesey',        'Dr. Livesey',       'David',    'Livesey',   'squire-trelawney','555-DOCTOR',   1, 'NSC402', 0, (SELECT id FROM users WHERE email = 'dr.livesey@example.com')),
                ('ben-gunn',          'Ben Gunn',          'Ben',      'Gunn',      NULL,              '555-MAROONED', 1, NULL,     1, (SELECT id FROM users WHERE email = 'ben.gunn@example.com')),

                -- Mutiny on the Bounty
                ('fletcher-christian','Fletcher Christian','Fletcher', 'Christian', NULL,              '555-MUTINY',   2, 'NSC403', 0, (SELECT id FROM users WHERE email = 'fletcher.christian@example.com')),
                ('john-adams',        'John Adams',        'John',     'Adams',     NULL,              '555-PITCAIRN', 1, 'NSC404', 0, (SELECT id FROM users WHERE email = 'john.adams@example.com')),
                ('william-mccoy',     'William McCoy',     'William',  'McCoy',     NULL,              '555-MCCOY',    1, 'NSC405', 0, (SELECT id FROM users WHERE email = 'william.mccoy@example.com')),
                ('ned-young',         'Ned Young',         'Ned',      'Young',     NULL,              '555-YOUNG',    1, 'NSC406', 0, (SELECT id FROM users WHERE email = 'ned.young@example.com')),
                ('matthew-quintal',   'Matthew Quintal',   'Matthew',  'Quintal',   NULL,              '555-QUINTAL',  0, NULL,     1, (SELECT id FROM users WHERE email = 'matthew.quintal@example.com')),

                -- Swallows & Amazons
                ('susan-walker',      'Susan Walker',      'Susan',    'Walker',    NULL,              '555-SUSAN',    1, 'NSC407', 0, (SELECT id FROM users WHERE email = 'susan.walker@example.com')),
                ('titty-walker',      'Titty Walker',      'Titty',    'Walker',    NULL,              '555-TITTY',    1, 'NSC408', 0, (SELECT id FROM users WHERE email = 'titty.walker@example.com')),
                ('roger-walker',      'Roger Walker',      'Roger',    'Walker',    NULL,              '555-ROGER',    0, 'NSC409', 0, (SELECT id FROM users WHERE email = 'roger.walker@example.com')),
                ('peggy-blackett',    'Peggy Blackett',    'Peggy',    'Blackett',  NULL,              '555-PEGGY',    1, 'NSC410', 0, (SELECT id FROM users WHERE email = 'peggy.blackett@example.com')),

                -- Moby-Dick
                ('starbuck',          'Starbuck',          'Starbuck', '',          NULL,              '555-STARBUCK', 2, 'NSC411', 0, (SELECT id FROM users WHERE email = 'starbuck@example.com')),
                ('queequeg',          'Queequeg',          'Queequeg', '',          NULL,              '555-QUEEQUEG', 2, 'NSC412', 0, (SELECT id FROM users WHERE email = 'queequeg@example.com')),
                ('stubb',             'Stubb',             'Stubb',    '',          NULL,              '555-STUBB',    1, 'NSC413', 0, (SELECT id FROM users WHERE email = 'stubb@example.com')),
                ('flask',             'Flask',             'Flask',    '',          NULL,              '555-FLASK',    1, 'NSC414', 0, (SELECT id FROM users WHERE email = 'flask@example.com')),
                ('ishmael',           'Ishmael',           'Ishmael',  '',          NULL,              '555-ISHMAEL',  0, NULL,     1, (SELECT id FROM users WHERE email = 'ishmael@example.com'))
        ");

        // ====================================================================
        // Seed boat availability — all 5 boats × all 18 upcoming events
        // ====================================================================
        $this->execute("
            INSERT INTO boat_availability (boat_id, event_id, berths)
            SELECT b.id, e.event_id, b.max_berths
            FROM boats b
            CROSS JOIN events e
            WHERE e.status = 'upcoming'
        ");

        // ====================================================================
        // Seed crew availability — tiered by season phase
        //
        // Case 1 (Events 1-3, May 29–Jun 12): 5 crew available
        //   crewCount=5 < minBerths=7 → algorithm cuts Amazon+Swallow (min=1 each)
        //   Result: 3 boats sail (Pequod, HMS Bounty, Hispaniola); 2 boats waitlisted
        //
        // Case 2 (Events 4-14, Jun 19–Aug 29): 19 crew available
        //   crewCount=19 > maxBerths=18 → 1 crew waitlisted
        //
        // Case 3 (Events 15-18, Sep 4–Sep 25): 12 crew available
        //   7 ≤ crewCount=12 ≤ 18 → all crew assigned across boats
        // ====================================================================

        $case1Events       = "'Fri May 29', 'Fri Jun 5', 'Fri Jun 12'";
        $case2Events       = "'Fri Jun 19', 'Sat Jun 27', 'Fri Jul 3', 'Fri Jul 10', 'Sat Jul 18', 'Fri Jul 24', 'Fri Jul 31', 'Sat Aug 8', 'Fri Aug 14', 'Fri Aug 21', 'Sat Aug 29'";
        $case3Events       = "'Fri Sep 4', 'Fri Sep 11', 'Fri Sep 18', 'Fri Sep 25'";

        $case1Available    = "'long-john-silver', 'fletcher-christian', 'starbuck', 'queequeg', 'john-adams'";
        $case3Available    = "'long-john-silver', 'fletcher-christian', 'starbuck', 'queequeg', 'john-adams', 'william-mccoy', 'ned-young', 'stubb', 'flask', 'susan-walker', 'titty-walker', 'peggy-blackett'";

        // Case 1: available crew (status=1)
        $this->execute("
            INSERT INTO crew_availability (crew_id, event_id, status)
            SELECT c.id, e.event_id, 1
            FROM crews c
            CROSS JOIN events e
            WHERE c.key IN ($case1Available)
              AND e.event_id IN ($case1Events)
        ");

        // Case 1: unavailable crew (status=0)
        $this->execute("
            INSERT INTO crew_availability (crew_id, event_id, status)
            SELECT c.id, e.event_id, 0
            FROM crews c
            CROSS JOIN events e
            WHERE c.key NOT IN ($case1Available)
              AND e.event_id IN ($case1Events)
        ");

        // Case 2: all 19 crew available (status=1)
        $this->execute("
            INSERT INTO crew_availability (crew_id, event_id, status)
            SELECT c.id, e.event_id, 1
            FROM crews c
            CROSS JOIN events e
            WHERE e.event_id IN ($case2Events)
        ");

        // Case 3: available crew (status=1)
        $this->execute("
            INSERT INTO crew_availability (crew_id, event_id, status)
            SELECT c.id, e.event_id, 1
            FROM crews c
            CROSS JOIN events e
            WHERE c.key IN ($case3Available)
              AND e.event_id IN ($case3Events)
        ");

        // Case 3: unavailable crew (status=0)
        $this->execute("
            INSERT INTO crew_availability (crew_id, event_id, status)
            SELECT c.id, e.event_id, 0
            FROM crews c
            CROSS JOIN events e
            WHERE c.key NOT IN ($case3Available)
              AND e.event_id IN ($case3Events)
        ");

        // ====================================================================
        // Seed crew whitelist (5 entries)
        //
        // Susan Walker: hispaniola (ASSIST+WHITELIST interaction) + swallow (home boat)
        // Titty Walker, Roger Walker: swallow
        // Peggy Blackett: amazon
        // ====================================================================
        $this->execute("
            INSERT INTO crew_whitelist (crew_id, boat_key)
            SELECT id, 'hispaniola' FROM crews WHERE key = 'susan-walker'
        ");
        $this->execute("
            INSERT INTO crew_whitelist (crew_id, boat_key)
            SELECT id, 'swallow' FROM crews WHERE key = 'susan-walker'
        ");
        $this->execute("
            INSERT INTO crew_whitelist (crew_id, boat_key)
            SELECT id, 'swallow' FROM crews WHERE key = 'titty-walker'
        ");
        $this->execute("
            INSERT INTO crew_whitelist (crew_id, boat_key)
            SELECT id, 'swallow' FROM crews WHERE key = 'roger-walker'
        ");
        $this->execute("
            INSERT INTO crew_whitelist (crew_id, boat_key)
            SELECT id, 'amazon' FROM crews WHERE key = 'peggy-blackett'
        ");

        // ====================================================================
        // Seed crew history — for REPEAT rule (6 entries)
        //
        // Long John Silver: 3 events assigned to hispaniola
        //   → REPEAT rule fires if Silver placed on hispaniola again (loss=3)
        //
        // Matthew Quintal: 3 events unassigned ('')
        //   → high unassigned count makes Quintal a strong swap target (grad=3)
        // ====================================================================
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Sep 26 2025', 'hispaniola' FROM crews WHERE key = 'long-john-silver'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 3 2025',  'hispaniola' FROM crews WHERE key = 'long-john-silver'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 10 2025', 'hispaniola' FROM crews WHERE key = 'long-john-silver'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Sep 26 2025', '' FROM crews WHERE key = 'matthew-quintal'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 3 2025',  '' FROM crews WHERE key = 'matthew-quintal'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 10 2025', '' FROM crews WHERE key = 'matthew-quintal'");
    }
}
