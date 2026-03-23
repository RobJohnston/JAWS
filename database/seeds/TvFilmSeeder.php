<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * TV & Film Seeder
 *
 * Five fictional boats and 26 crew drawn from classic TV and film.
 *
 * Boats (minBerths=5, maxBerths=21 combined):
 *   - SS Minnow        (Gilligan's Island)           min=1, max=4
 *   - Pacific Princess (The Love Boat)               min=1, max=5
 *   - Black Pearl      (Pirates of the Caribbean)    min=1, max=5
 *   - The Orca         (Jaws)                        min=1, max=3, assistance_required='Yes'
 *   - PT-73            (McHale's Navy)               min=1, max=4
 *
 * Crew (26):
 *   Gilligan's Island:        Gilligan, Thurston, Lovey, Ginger, Professor, Mary Ann
 *   The Love Boat:            Gopher, Doc, Isaac, Julie, Vicki
 *   Pirates of the Caribbean: Barbossa, Will, Elizabeth, Gibbs, Cotton, Marty
 *   Jaws:                     Brody, Hooper
 *   McHale's Navy:            Parker, Tinker, Gruber, Happy, Christy, Virgil, Willy
 *
 * Capacity cases (tiered availability):
 *   Events  1-2  (May 29–Jun 5):   4 crew  < minBerths=5  → Case 1 (1 boat waitlisted)
 *   Events  3-14 (Jun 12–Aug 29): 26 crew  > maxBerths=21 → Case 2 (5 crew waitlisted)
 *   Events 15-18 (Sep 4–Sep 25):  14 crew, 5≤14≤21        → Case 3 (perfect fit)
 *
 * Assignment rules exercised:
 *   ASSIST    — The Orca has assistance_required='Yes'; skill-2 crew: Professor, Barbossa, Turner
 *   WHITELIST — Gilligan→ss-minnow; Brody→the-orca; Parker→pt-73
 *   PARTNER   — Thurston+Lovey Howell; Will Turner+Elizabeth Swann
 *   HIGH_SKILL/LOW_SKILL — 5 boats, 3 skill levels
 *   REPEAT    — Brody+Hooper: 3× history on the-orca; Virgil+Isaac: 3× unassigned
 *
 * rank_membership=0 (non-members): Gilligan, Ginger, Cotton, Marty, Brody
 *
 * Usage:
 *   vendor/bin/phinx seed:run -s TvFilmSeeder
 *
 * Test credentials: All users have password "Password123"
 */
class TvFilmSeeder extends AbstractSeed
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
        // Seed users — 5 boat owners + 26 crew members
        // ====================================================================
        $passwordHash = password_hash('Password123', PASSWORD_DEFAULT);

        $users = [
            // Boat owners
            ['email' => 'jonas.grumby@example.com',      'password_hash' => $passwordHash, 'account_type' => 'boat_owner', 'is_admin' => 1],
            ['email' => 'merrill.stubing@example.com',   'password_hash' => $passwordHash, 'account_type' => 'boat_owner', 'is_admin' => 0],
            ['email' => 'jack.sparrow@example.com',      'password_hash' => $passwordHash, 'account_type' => 'boat_owner', 'is_admin' => 0],
            ['email' => 'quint@example.com',             'password_hash' => $passwordHash, 'account_type' => 'boat_owner', 'is_admin' => 0],
            ['email' => 'quinton.mchale@example.com',    'password_hash' => $passwordHash, 'account_type' => 'boat_owner', 'is_admin' => 0],
            // Crew — Gilligan's Island
            ['email' => 'gilligan@example.com',          'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'thurston.howell@example.com',   'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'lovey.howell@example.com',      'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'ginger.grant@example.com',      'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'the.professor@example.com',     'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'mary.ann@example.com',          'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            // Crew — The Love Boat
            ['email' => 'gopher.smith@example.com',      'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'doc.bricker@example.com',       'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'isaac.washington@example.com',  'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'julie.mccoy@example.com',       'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'vicki.stubing@example.com',     'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            // Crew — Pirates of the Caribbean
            ['email' => 'hector.barbossa@example.com',   'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'will.turner@example.com',       'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'elizabeth.swann@example.com',   'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'joshamee.gibbs@example.com',    'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'cotton@example.com',            'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'marty@example.com',             'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            // Crew — Jaws
            ['email' => 'martin.brody@example.com',      'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'matt.hooper@example.com',       'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            // Crew — McHale's Navy
            ['email' => 'charles.parker@example.com',    'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'tinker.bell@example.com',       'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'george.gruber@example.com',     'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'happy.haines@example.com',      'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'george.christy@example.com',    'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'virgil.edwards@example.com',    'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
            ['email' => 'willy.moss@example.com',        'password_hash' => $passwordHash, 'account_type' => 'crew', 'is_admin' => 0],
        ];

        $this->table('users')->insert($users)->saveData();

        // ====================================================================
        // Seed boats — 5 vessels
        //
        // The Orca: assistance_required='Yes' activates the ASSIST rule.
        //   Skill-2 crew (Professor, Barbossa, Turner) are candidates.
        //
        // Combined: minBerths=5, maxBerths=21
        // ====================================================================
        $this->execute("
            INSERT INTO boats (key, display_name, owner_first_name, owner_last_name, owner_mobile,
                               min_berths, max_berths, assistance_required, social_preference,
                               rank_flexibility, rank_absence, owner_user_id)
            VALUES
                ('ss-minnow',        'SS Minnow',        'Jonas',   'Grumby',  '555-SKIPPER',
                 1, 4, 'No', 'No', 1, 0,
                 (SELECT id FROM users WHERE email = 'jonas.grumby@example.com')),

                ('pacific-princess', 'Pacific Princess', 'Merrill', 'Stubing', '555-CAPTAIN',
                 1, 5, 'No', 'No', 1, 0,
                 (SELECT id FROM users WHERE email = 'merrill.stubing@example.com')),

                ('black-pearl',      'Black Pearl',      'Jack',    'Sparrow', '555-SAVVY',
                 1, 5, 'No', 'No', 1, 0,
                 (SELECT id FROM users WHERE email = 'jack.sparrow@example.com')),

                ('the-orca',         'The Orca',         'Quint',   '',        '555-ORCA',
                 1, 3, 'Yes', 'No', 1, 0,
                 (SELECT id FROM users WHERE email = 'quint@example.com')),

                ('pt-73',            'PT-73',            'Quinton', 'McHale',  '555-PT73',
                 1, 4, 'No', 'No', 1, 0,
                 (SELECT id FROM users WHERE email = 'quinton.mchale@example.com'))
        ");

        // ====================================================================
        // Seed crew — 26 members across 5 source works
        //
        // rank_membership=0 (non-NSC-member): Gilligan, Ginger, Cotton, Marty, Brody
        // PARTNER pairs: Thurston+Lovey Howell; Will Turner+Elizabeth Swann
        // Skill distribution: skill-0 × 8, skill-1 × 15, skill-2 × 3
        // ====================================================================
        $this->execute("
            INSERT INTO crews (key, display_name, first_name, last_name, partner_key, mobile,
                               skill, membership_number, rank_membership, user_id)
            VALUES
                -- Gilligan's Island
                ('gilligan',         'Gilligan',           'Gilligan',  '',          NULL,             '555-GILLIGAN',  0, NULL,     0, (SELECT id FROM users WHERE email = 'gilligan@example.com')),
                ('thurston-howell',  'Thurston Howell III','Thurston',  'Howell',    'lovey-howell',   '555-HOWELL',    1, 'NSC500', 1, (SELECT id FROM users WHERE email = 'thurston.howell@example.com')),
                ('lovey-howell',     'Lovey Howell',       'Lovey',     'Howell',    'thurston-howell','555-LOVEY',     0, 'NSC501', 1, (SELECT id FROM users WHERE email = 'lovey.howell@example.com')),
                ('ginger-grant',     'Ginger Grant',       'Ginger',    'Grant',     NULL,             '555-GINGER',    0, NULL,     0, (SELECT id FROM users WHERE email = 'ginger.grant@example.com')),
                ('the-professor',    'The Professor',      'Roy',       'Hinkley',   NULL,             '555-PROF',      2, 'NSC502', 1, (SELECT id FROM users WHERE email = 'the.professor@example.com')),
                ('mary-ann',         'Mary Ann Summers',   'Mary Ann',  'Summers',   NULL,             '555-MARYANN',   1, 'NSC503', 1, (SELECT id FROM users WHERE email = 'mary.ann@example.com')),

                -- The Love Boat
                ('gopher-smith',     'Gopher Smith',       'Burl',      'Smith',     NULL,             '555-GOPHER',    1, 'NSC504', 1, (SELECT id FROM users WHERE email = 'gopher.smith@example.com')),
                ('doc-bricker',      'Doc Bricker',        'Adam',      'Bricker',   NULL,             '555-DOCB',      0, 'NSC505', 1, (SELECT id FROM users WHERE email = 'doc.bricker@example.com')),
                ('isaac-washington', 'Isaac Washington',   'Isaac',     'Washington',NULL,             '555-ISAAC',     0, 'NSC506', 1, (SELECT id FROM users WHERE email = 'isaac.washington@example.com')),
                ('julie-mccoy',      'Julie McCoy',        'Julie',     'McCoy',     NULL,             '555-JULIE',     0, 'NSC507', 1, (SELECT id FROM users WHERE email = 'julie.mccoy@example.com')),
                ('vicki-stubing',    'Vicki Stubing',      'Vicki',     'Stubing',   NULL,             '555-VICKI',     1, 'NSC508', 1, (SELECT id FROM users WHERE email = 'vicki.stubing@example.com')),

                -- Pirates of the Caribbean
                ('hector-barbossa',  'Hector Barbossa',    'Hector',    'Barbossa',  NULL,             '555-BARBOSSA',  2, 'NSC509', 1, (SELECT id FROM users WHERE email = 'hector.barbossa@example.com')),
                ('will-turner',      'Will Turner',        'Will',      'Turner',    'elizabeth-swann','555-TURNER',    2, 'NSC510', 1, (SELECT id FROM users WHERE email = 'will.turner@example.com')),
                ('elizabeth-swann',  'Elizabeth Swann',    'Elizabeth', 'Swann',     'will-turner',    '555-SWANN',     1, 'NSC511', 1, (SELECT id FROM users WHERE email = 'elizabeth.swann@example.com')),
                ('joshamee-gibbs',   'Joshamee Gibbs',     'Joshamee',  'Gibbs',     NULL,             '555-GIBBS',     1, 'NSC512', 1, (SELECT id FROM users WHERE email = 'joshamee.gibbs@example.com')),
                ('cotton',           'Cotton',             'Cotton',    '',          NULL,             '555-COTTON',    1, NULL,     0, (SELECT id FROM users WHERE email = 'cotton@example.com')),
                ('marty',            'Marty',              'Marty',     '',          NULL,             '555-MARTY',     1, NULL,     0, (SELECT id FROM users WHERE email = 'marty@example.com')),

                -- Jaws
                ('martin-brody',     'Chief Brody',        'Martin',    'Brody',     NULL,             '555-BRODY',     0, NULL,     0, (SELECT id FROM users WHERE email = 'martin.brody@example.com')),
                ('matt-hooper',      'Matt Hooper',        'Matt',      'Hooper',    NULL,             '555-HOOPER',    1, 'NSC513', 1, (SELECT id FROM users WHERE email = 'matt.hooper@example.com')),

                -- McHale's Navy
                ('charles-parker',   'Ensign Parker',      'Charles',   'Parker',    NULL,             '555-PARKER',    0, 'NSC514', 1, (SELECT id FROM users WHERE email = 'charles.parker@example.com')),
                ('tinker-bell',      'Tinker Bell',        'Tinker',    'Bell',      NULL,             '555-TINKER',    1, 'NSC515', 1, (SELECT id FROM users WHERE email = 'tinker.bell@example.com')),
                ('george-gruber',    'Gruber',             'George',    'Gruber',    NULL,             '555-GRUBER',    1, 'NSC516', 1, (SELECT id FROM users WHERE email = 'george.gruber@example.com')),
                ('happy-haines',     'Happy Haines',       'Happy',     'Haines',    NULL,             '555-HAPPY',     1, 'NSC517', 1, (SELECT id FROM users WHERE email = 'happy.haines@example.com')),
                ('george-christy',   'Christy',            'George',    'Christy',   NULL,             '555-CHRISTY',   1, 'NSC518', 1, (SELECT id FROM users WHERE email = 'george.christy@example.com')),
                ('virgil-edwards',   'Virgil Edwards',     'Virgil',    'Edwards',   NULL,             '555-VIRGIL',    0, 'NSC519', 1, (SELECT id FROM users WHERE email = 'virgil.edwards@example.com')),
                ('willy-moss',       'Willy Moss',         'Willy',     'Moss',      NULL,             '555-WILLY',     1, 'NSC520', 1, (SELECT id FROM users WHERE email = 'willy.moss@example.com'))
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
        // Case 1 (Events 1-2, May 29–Jun 5): 4 crew available
        //   crewCount=4 < minBerths=5 → algorithm cuts 1 lowest-ranked boat
        //   Result: 4 boats sail; 1 boat waitlisted
        //
        // Case 2 (Events 3-14, Jun 12–Aug 29): 26 crew available
        //   crewCount=26 > maxBerths=21 → 5 crew waitlisted
        //
        // Case 3 (Events 15-18, Sep 4–Sep 25): 14 crew available
        //   5 ≤ crewCount=14 ≤ 21 → all crew assigned across boats
        // ====================================================================

        $case1Events    = "'Fri May 29', 'Fri Jun 5'";
        $case2Events    = "'Fri Jun 12', 'Fri Jun 19', 'Sat Jun 27', 'Fri Jul 3', 'Fri Jul 10', 'Sat Jul 18', 'Fri Jul 24', 'Fri Jul 31', 'Sat Aug 8', 'Fri Aug 14', 'Fri Aug 21', 'Sat Aug 29'";
        $case3Events    = "'Fri Sep 4', 'Fri Sep 11', 'Fri Sep 18', 'Fri Sep 25'";

        // Case 1: 4 available crew — high-skill candidates trigger ASSIST early
        $case1Available = "'the-professor', 'hector-barbossa', 'will-turner', 'matt-hooper'";

        // Case 3: 14 crew — spans all 5 boats, exercises ASSIST, PARTNER, WHITELIST
        $case3Available = "'the-professor', 'hector-barbossa', 'will-turner', 'elizabeth-swann', 'joshamee-gibbs', 'matt-hooper', 'gopher-smith', 'vicki-stubing', 'mary-ann', 'tinker-bell', 'george-gruber', 'happy-haines', 'george-christy', 'willy-moss'";

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

        // Case 2: all 26 crew available (status=1)
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
        // Seed crew whitelist (3 entries)
        //
        // Gilligan: ss-minnow (home boat — tests WHITELIST on Case 2 overflow)
        // Brody: the-orca (WHITELIST + ASSIST interaction, REPEAT rule fires here)
        // Parker: pt-73 (home boat)
        // ====================================================================
        $this->execute("
            INSERT INTO crew_whitelist (crew_id, boat_key)
            SELECT id, 'ss-minnow' FROM crews WHERE key = 'gilligan'
        ");
        $this->execute("
            INSERT INTO crew_whitelist (crew_id, boat_key)
            SELECT id, 'the-orca' FROM crews WHERE key = 'martin-brody'
        ");
        $this->execute("
            INSERT INTO crew_whitelist (crew_id, boat_key)
            SELECT id, 'pt-73' FROM crews WHERE key = 'charles-parker'
        ");

        // ====================================================================
        // Seed crew history — for REPEAT rule (12 entries)
        //
        // Chief Brody:    3 events assigned to the-orca
        //   → REPEAT rule fires if Brody placed on the-orca again (loss=3)
        //
        // Matt Hooper:    3 events assigned to the-orca
        //   → REPEAT loss=3; combined with Brody creates swap pressure
        //
        // Virgil Edwards: 3 events unassigned ('')
        //   → high unassigned count makes Virgil a strong swap target (grad=3)
        //
        // Isaac Washington: 3 events unassigned ('')
        //   → grad=3; paired with Virgil for double swap-target pressure
        // ====================================================================
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Sep 26 2025', 'the-orca' FROM crews WHERE key = 'martin-brody'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 3 2025',  'the-orca' FROM crews WHERE key = 'martin-brody'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 10 2025', 'the-orca' FROM crews WHERE key = 'martin-brody'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Sep 26 2025', 'the-orca' FROM crews WHERE key = 'matt-hooper'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 3 2025',  'the-orca' FROM crews WHERE key = 'matt-hooper'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 10 2025', 'the-orca' FROM crews WHERE key = 'matt-hooper'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Sep 26 2025', '' FROM crews WHERE key = 'virgil-edwards'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 3 2025',  '' FROM crews WHERE key = 'virgil-edwards'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 10 2025', '' FROM crews WHERE key = 'virgil-edwards'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Sep 26 2025', '' FROM crews WHERE key = 'isaac-washington'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 3 2025',  '' FROM crews WHERE key = 'isaac-washington'");
        $this->execute("INSERT INTO crew_history (crew_id, event_id, boat_key) SELECT id, 'Fri Oct 10 2025', '' FROM crews WHERE key = 'isaac-washington'");
    }
}
