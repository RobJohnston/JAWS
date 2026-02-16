# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Human-Readable Documentation

For human developers, the primary documentation is in the `/docs` folder:

- **[README.md](README.md)** - Project overview and quick navigation hub
- **[docs/SETUP.md](docs/SETUP.md)** - Installation and setup instructions for new developers
- **[docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md)** - Architecture, development workflow, testing, and best practices
- **[docs/API.md](docs/API.md)** - Complete API endpoint documentation with examples
- **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** - Production deployment procedures and monitoring
- **[docs/CONTRIBUTING.md](docs/CONTRIBUTING.md)** - Code style, Git workflow, and PR process

**IMPORTANT:** When developers ask general questions about setup, architecture, or API endpoints, direct them to the appropriate human-readable documentation file above rather than replicating content from this file.

This CLAUDE.md file contains technical specifications optimized for AI assistant consumption. It serves as the "source of truth" for technical details but is not the primary documentation for human developers.

## Interactive Development Skills

Common development workflows have been extracted into **skills** for interactive, step-by-step guidance:

- **`/test`** - Run PHPUnit tests with appropriate commands
- **`/conventional-commits`** - Create properly formatted commit messages
- **`/add-endpoint`** - Add new REST API endpoints
- **`/modify-schema`** - Modify database schema with migrations
- **`/database-ops`** - Database operations (backup, restore, query)
- **`/deploy-lightsail`** - Deploy to AWS Lightsail production
- **`/add-ranking`** - Add new ranking criteria to selection algorithm
- **`/add-rule`** - Add new optimization rules to assignment algorithm

**Skills location:** `.claude/skills/` - See [`.claude/skills/README.md`](.claude/skills/README.md) for details.

Use skills for procedural "how-to" tasks. This file focuses on architecture, concepts, and technical specifications.

## Project Overview

JAWS is a PHP-based REST API for managing the Social Day Cruising program at Nepean Sailing Club. It handles boat fleet management, crew registration, and intelligent assignment of crew members to boats for seasonal sailing events. The system optimizes crew-to-boat matching based on multiple constraints including skill levels, availability, preferences, and historical participation.

**Architecture:** Clean Architecture (Hexagonal/Ports and Adapters pattern) with 4 distinct layers: Domain, Application, Infrastructure, and Presentation.

**Database:** SQLite with Phinx migrations

**API Style:** REST/JSON with JWT authentication

## Development Commands

### Install Dependencies
```bash
composer install
```

### Initialize Database
```bash
# Run all migrations (recommended)
vendor/bin/phinx migrate

# Or use legacy init script (calls Phinx internally)
php database/init_database.php
```

### Database Migrations

```bash
# Create new migration
vendor/bin/phinx create MyMigrationName

# Run pending migrations
vendor/bin/phinx migrate

# Rollback last migration
vendor/bin/phinx rollback

# Check migration status
vendor/bin/phinx status

# Seed test data
vendor/bin/phinx seed:run
```

### Start Development Server
```bash
php -S localhost:8000 -t public
```

### Run Tests

**Quick reference:**
```bash
./vendor/bin/phpunit                    # All tests
./vendor/bin/phpunit tests/Unit         # Unit tests only
./vendor/bin/phpunit --testsuite=API    # API tests
```

**For detailed testing workflows, use the `/test` skill.**

### Deploy to AWS Lightsail

**Server:** bitnami@16.52.222.15

**For complete deployment procedures, use the `/deploy-lightsail` skill or see [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).**

### Common Database Operations

**Quick reference:**
```bash
vendor/bin/phinx migrate              # Apply migrations
vendor/bin/phinx status               # Check status
sqlite3 database/jaws.db "<query>"    # Query database
```

**For database operations (backup, restore, download from production), use the `/database-ops` skill.**

## Commit Message Format

This project uses **Conventional Commits** specification. Format: `<type>: <description>`

**Common types:** feat, fix, docs, test, refactor, ci

**Example:** `feat: add crew notes field to database schema`

**For detailed guidance on creating commits, use the `/conventional-commits` skill.**

**Important:** Always include the Co-Authored-By line as specified in the git commit guidance.

## Clean Architecture Overview

The application follows Clean Architecture with strict dependency rules: outer layers depend on inner layers, never the reverse.

### Layer Dependency Direction

```
Presentation → Infrastructure → Application → Domain
  (HTTP)      (Database/AWS)   (Use Cases)   (Business Logic)
```

**Key Principle:** The Domain layer has ZERO dependencies. It contains pure business logic.

### Layer 1: Domain (`src/Domain/`)

**Responsibility:** Core business logic and rules

**Dependencies:** None (pure PHP, no external libraries)

**Contents:**

- **Entities** (`Entity/`)
  - `Boat.php` - Boat entity with capacity, owner info, ranking, history, availability
  - `Crew.php` - Crew member entity with skills, preferences, whitelist, ranking
  - `User.php` - User entity with authentication credentials, profile links

- **Value Objects** (`ValueObject/`)
  - `BoatKey.php` - Immutable boat identifier
  - `CrewKey.php` - Immutable crew identifier
  - `EventId.php` - Immutable event identifier with hash support
  - `Rank.php` - Multi-dimensional rank tensor (lexicographic comparison)

- **Enums** (`Enum/`)
  - `BoatRankDimension.php` - Boat ranking dimensions (flexibility, absence)
  - `CrewRankDimension.php` - Crew ranking dimensions (commitment, flexibility, membership, absence)
  - `AvailabilityStatus.php` - UNAVAILABLE (0), AVAILABLE (1), GUARANTEED (2), WITHDRAWN (3)
  - `SkillLevel.php` - NOVICE (0), INTERMEDIATE (1), ADVANCED (2)
  - `AssignmentRule.php` - 6 optimization rules (ASSIST, WHITELIST, HIGH_SKILL, LOW_SKILL, PARTNER, REPEAT)
  - `TimeSource.php` - Production vs simulated time source

- **Collections** (`Collection/`)
  - `Fleet.php` - In-memory boat collection management
  - `Squad.php` - In-memory crew collection management

- **Domain Services** (`Service/`) - **CRITICAL ALGORITHMS PRESERVED**
  - `SelectionService.php` - Ranking & boat/crew selection algorithm (CRC32 seeding, lexicographic sort)
  - `AssignmentService.php` - Crew-to-boat optimization via constraint-based swapping
  - `RankingService.php` - Multi-dimensional rank calculations for boats and crews
  - `FlexService.php` - Flex status detection (boat owners as crew, crew owning boats)

### Layer 2: Application (`src/Application/`)

**Responsibility:** Use cases and application orchestration

**Dependencies:** Domain layer only

**Contents:**

- **Use Cases** (`UseCase/`)
  - `Auth/LoginUseCase.php` - User authentication
  - `Auth/RegisterUseCase.php` - User registration
  - `Auth/GetSessionUseCase.php` - Get current user session
  - `Auth/LogoutUseCase.php` - User logout
  - `User/GetUserProfileUseCase.php` - Get user profile (boat/crew info)
  - `User/AddProfileUseCase.php` - Add boat or crew profile
  - `User/UpdateUserProfileUseCase.php` - Update user profile
  - `Boat/UpdateBoatAvailabilityUseCase.php` - Update boat berths for events
  - `Crew/UpdateCrewAvailabilityUseCase.php` - Update crew availability
  - `Crew/GetCrewAvailabilityUseCase.php` - Get crew availability for events
  - `Crew/GetUserAssignmentsUseCase.php` - Get user's assignments across all events
  - `Event/GetAllEventsUseCase.php` - List all events
  - `Event/GetEventUseCase.php` - Get event with flotilla
  - `Flotilla/GetAllFlotillasUseCase.php` - Get all flotillas
  - `Season/ProcessSeasonUpdateUseCase.php` - **CRITICAL**: Main orchestration pipeline (replaces season_update.php)
  - `Season/GenerateFlotillaUseCase.php` - Generate flotilla for an event
  - `Season/UpdateConfigUseCase.php` - Update season configuration
  - `Admin/GetMatchingDataUseCase.php` - Get matching data for event
  - `Admin/GetConfigUseCase.php` - Get season configuration
  - `Admin/SendNotificationsUseCase.php` - Send email notifications

- **Ports (Interfaces)** (`Port/`)
  - `Repository/BoatRepositoryInterface.php` - How to persist boats
  - `Repository/CrewRepositoryInterface.php` - How to persist crew
  - `Repository/EventRepositoryInterface.php` - How to query events
  - `Repository/SeasonRepositoryInterface.php` - How to manage season config/flotillas
  - `Repository/UserRepositoryInterface.php` - How to persist users
  - `Service/EmailServiceInterface.php` - How to send emails
  - `Service/CalendarServiceInterface.php` - How to generate iCal files
  - `Service/TimeServiceInterface.php` - How to get current time
  - `Service/TokenServiceInterface.php` - How to generate/validate JWT tokens
  - `Service/PasswordServiceInterface.php` - How to hash/verify passwords
  - `Service/EmailTemplateServiceInterface.php` - How to render email templates

- **DTOs** (`DTO/`)
  - `Request/LoginRequest.php` - User login credentials
  - `Request/RegisterRequest.php` - User registration data
  - `Request/AddProfileRequest.php` - Add boat or crew profile
  - `Request/UpdateProfileRequest.php` - Update profile data
  - `Request/UpdateAvailabilityRequest.php` - Availability updates
  - `Request/UpdateConfigRequest.php` - Season config updates
  - `Response/AuthResponse.php` - Authentication token and user info
  - `Response/UserResponse.php` - User account details
  - `Response/ProfileResponse.php` - User profile (boat/crew)
  - `Response/BoatResponse.php` - Serialized boat
  - `Response/CrewResponse.php` - Serialized crew
  - `Response/EventResponse.php` - Serialized event
  - `Response/FlotillaResponse.php` - Flotilla assignment
  - `Response/AssignmentResponse.php` - Crew-to-boat assignment
  - `Response/AvailabilityResponse.php` - Availability status

- **Exceptions** (`Exception/`)
  - `BoatNotFoundException.php` - Boat not found
  - `CrewNotFoundException.php` - Crew not found
  - `EventNotFoundException.php` - Event not found
  - `FlotillaNotFoundException.php` - Flotilla not found
  - `UserNotFoundException.php` - User not found
  - `UserAlreadyExistsException.php` - Email already registered
  - `InvalidCredentialsException.php` - Login failed
  - `InvalidTokenException.php` - JWT token invalid/expired
  - `UnauthorizedException.php` - Insufficient permissions
  - `WeakPasswordException.php` - Password doesn't meet requirements
  - `ValidationException.php` - Field-level validation errors
  - `BlackoutWindowException.php` - Registration blocked during event

### Layer 3: Infrastructure (`src/Infrastructure/`)

**Responsibility:** External adapters (database, email, calendar, etc.)

**Dependencies:** Application + Domain layers

**Contents:**

- **Persistence** (`Persistence/SQLite/`)
  - `Connection.php` - Singleton PDO manager with foreign keys & WAL mode
  - `BoatRepository.php` - Implements `BoatRepositoryInterface` (full CRUD with lazy loading)
  - `CrewRepository.php` - Implements `CrewRepositoryInterface` (full CRUD with whitelist management)
  - `EventRepository.php` - Implements `EventRepositoryInterface` (time-based event queries)
  - `SeasonRepository.php` - Implements `SeasonRepositoryInterface` (config & flotilla JSON storage)
  - `UserRepository.php` - Implements `UserRepositoryInterface` (user authentication & profile)

- **Service Adapters** (`Service/`)
  - `PhpMailerEmailService.php` - Implements `EmailServiceInterface` using PHPMailer with SMTP
  - `AwsSesEmailService.php` - Implements `EmailServiceInterface` using AWS SES
  - `EmailTemplateService.php` - Implements `EmailTemplateServiceInterface` for email rendering
  - `ICalendarService.php` - Implements `CalendarServiceInterface` using eluceo/ical
  - `SystemTimeService.php` - Implements `TimeServiceInterface` (production/simulated time)
  - `JwtTokenService.php` - Implements `TokenServiceInterface` using Firebase JWT
  - `PhpPasswordService.php` - Implements `PasswordServiceInterface` using PHP password_hash

### Layer 4: Presentation (`src/Presentation/`)

**Responsibility:** HTTP/REST API

**Dependencies:** Application layer only

**Contents:**

- **Controllers** (`Controller/`)
  - `AuthController.php` - POST /api/auth/login, POST /api/auth/register, POST /api/auth/logout, GET /api/auth/session
  - `UserController.php` - GET/POST/PATCH /api/users/me (user profile management)
  - `EventController.php` - GET /api/events, GET /api/events/{id}, GET /api/flotillas
  - `AvailabilityController.php` - Boat/crew registration & availability updates
  - `AssignmentController.php` - GET /api/assignments (user's assignments)
  - `AdminController.php` - Admin endpoints (matching data, notifications, config)

- **Middleware** (`Middleware/`)
  - `JwtAuthMiddleware.php` - JWT token authentication (Authorization: Bearer header)
  - `ErrorHandlerMiddleware.php` - Exception → JSON responses (maps to HTTP status codes)
  - `CorsMiddleware.php` - Cross-Origin Resource Sharing headers

- **Router** (`Router.php`)
  - Pattern-matching router with parameter extraction

- **Response** (`Response/JsonResponse.php`)
  - Standardized response factory (success/error/404/500)

## The Season Update Pipeline

**Entry Point:** `ProcessSeasonUpdateUseCase` (replaces legacy `season_update.php`)

**Trigger:** Invoked after every user input (registration or availability update) via POST endpoints

**Pipeline:** Executes this workflow for each future event:

1. **Load Data**
   - Fetch all boats, crews, events, season config from database
   - Build in-memory Fleet and Squad collections

2. **Selection Phase** (`SelectionService`)
   - Get available boats and crews for this event
   - Apply multi-dimensional ranking (boats: flexibility, absence; crews: commitment, flexibility, membership, absence)
   - Perform deterministic shuffle using `crc32($eventId)` as seed
   - Sort using lexicographic rank comparison (bubble sort)
   - Execute capacity matching (3 cases: too few crews, too many crews, perfect fit)

3. **Event Consolidation**
   - Form selected boats and crews into structured flotilla
   - Separate crewed boats from waitlisted boats/crews

4. **Assignment Optimization** (`AssignmentService`) - **NEXT EVENT ONLY**
   - For the immediate next event only, perform constraint-based optimization
   - Iteratively swap crew between boats to minimize 6 rule violations:
     - **ASSIST**: Boats requiring assistance get appropriate crew
     - **WHITELIST**: Crew assigned to boats they've whitelisted
     - **HIGH_SKILL / LOW_SKILL**: Balance skill distribution
     - **PARTNER**: Keep requested partnerships together
     - **REPEAT**: Minimize crew repeating same boat
   - Use greedy approach: for each rule, identify highest-loss crew and best-grad swap candidate
   - Lock crew after swapping to prevent thrashing

5. **Persistence**
   - Update crew availability statuses (GUARANTEED for assigned crew)
   - Update boat/crew history
   - Save flotilla assignments to database (JSON format)

6. **Output Generation** (future enhancement)
   - Render flotilla tables to HTML
   - Generate iCalendar files for participants

## Database Schema (SQLite)

**Location:** `database/jaws.db`

**10 Tables:**

1. **boats** - Boat info (display_name, owner_*, capacity, assistance_required, ranking)
2. **crews** - Crew info (name, partner_key, email, skill, membership_number, ranking)
3. **events** - Event schedule (event_id, event_date, start_time, finish_time, status)
4. **boat_availability** - Berths offered per boat per event
5. **crew_availability** - Crew status per event (0=unavailable, 1=available, 2=guaranteed, 3=withdrawn)
6. **boat_history** - Boat participation history ('Y' or '')
7. **crew_history** - Crew-to-boat assignments per event
8. **crew_whitelist** - Crew preferences for specific boats
9. **season_config** - Season-wide singleton config (dates, times, blackout windows)
10. **flotillas** - Generated flotilla assignments (JSON)

**Schema Features:**
- Foreign key constraints enabled with CASCADE deletes
- Composite indexes on frequently queried columns (boat_key, crew_key, event_id)
- WAL mode for concurrent access
- Timestamps (created_at, updated_at) on key tables
- Triggers for automatic updated_at maintenance

**Migrations:** `database/migrations/001_initial_schema.sql`

## Multi-Dimensional Ranking System

The system uses rank tensors for prioritization:

- **Boats**: `[flexibility, absence]` (2D)
- **Crews**: `[commitment, flexibility, membership, absence]` (4D)

Rankings are compared lexicographically during bubble sort. **Lower rank = higher priority.**

**Rank Components:**
- `flexibility` - Whether boat owner is also registered as crew (boats) or crew owns a boat (crew)
- `absence` - Count of past no-shows (updated dynamically)
- `commitment` - Crew's availability for the next scheduled event (0=unavailable, 1=available)
- `membership` - Valid NSC membership number status (0=valid, 1=invalid)

**Deterministic Shuffling:** Ties are broken using deterministic shuffling seeded by `crc32($eventId)`, ensuring reproducible results.

**Implementation:** `RankingService::calculateBoatRank()`, `RankingService::calculateCrewRank()`

## Assignment Optimization Algorithm

**Location:** `src/Domain/Service/AssignmentService.php` (preserved from legacy)

**Purpose:** For the next event only, optimize crew-to-boat assignments to minimize rule violations.

**Process:**

1. **Calculate Loss and Grad** for each crew on each rule:
   - **Loss**: How much this crew violates this rule on their current boat
   - **Grad**: How much this crew could reduce violations by swapping to another boat

2. **Iterate through 6 rules** in priority order (ASSIST, WHITELIST, HIGH_SKILL, LOW_SKILL, PARTNER, REPEAT)

3. **For each rule:**
   - Identify highest-loss crew (most violating)
   - Find best-grad swap candidate (crew that would reduce violations most)
   - Swap if it reduces total violations and doesn't create other conflicts
   - Mark swapped crew as "locked" for this rule to prevent thrashing

4. **Greedy Approach:** This balances optimality with computational efficiency

**Critical Methods:**
- `crew_loss()` - Calculate violation severity for each rule
- `crew_grad()` - Calculate mitigation capacity for each rule
- `bad_swap()` - Swap validation logic
- `best_swap()` - Greedy swap selection
- `assign()` - Main optimization loop with unlocked_crews tracking

## API Endpoints

**Base URL:** `/api`

**Entry Point:** `public/index.php`

**Routing:** `config/routes.php` (pattern-matching with parameter extraction)

### Public Endpoints (No Authentication)

**GET /api/events**
- Returns all events for the season
- Controller: `EventController::getAll()`

**GET /api/events/{id}**
- Returns specific event with flotilla assignments
- Controller: `EventController::getOne($eventId)`

**GET /api/flotillas**
- Returns all flotillas for the season
- Controller: `EventController::getAllFlotillas()`

**POST /api/auth/register**
- Register new user account
- Controller: `AuthController::register()`

**POST /api/auth/login**
- Authenticate user and receive JWT token
- Controller: `AuthController::login()`

### Authenticated Endpoints (JWT)

**Headers Required:**
```
Authorization: Bearer {jwt_token}
```

**GET /api/auth/session**
- Get current authenticated user session
- Controller: `AuthController::getSession()`

**POST /api/auth/logout**
- Logout current user
- Controller: `AuthController::logout()`

**GET /api/users/me**
- Get user profile (boat and/or crew information)
- Controller: `UserController::getProfile()`

**POST /api/users/me**
- Add boat or crew profile to user account
- Controller: `UserController::addProfile()`

**PATCH /api/users/me**
- Update user profile information
- Controller: `UserController::updateProfile()`

**GET /api/users/me/availability**
- Get crew availability for all events
- Controller: `AvailabilityController::getCrewAvailability()`

**PATCH /api/users/me/availability**
- Update availability for authenticated user
- Auto-detects if user is boat owner, crew member, or both (flex)
- Updates all applicable entities:
  - Boat owners: updates boat berths (capacity offered)
  - Crew members: updates crew availability status (0-3 enum)
  - Flex members: updates BOTH boat berths AND crew status
- Controller: `AvailabilityController::updateAvailability()`
- Triggers: `ProcessSeasonUpdateUseCase`
- Response includes `updated` array indicating which entities were modified (e.g., `["boat"]`, `["crew"]`, or `["boat", "crew"]`)

**GET /api/assignments**
- Get user's assignments across all events
- Controller: `AssignmentController::getUserAssignments()`

### Admin Endpoints (Authenticated)

**GET /api/admin/config**
- Get season configuration
- Controller: `AdminController::getConfig()`

**PATCH /api/admin/config**
- Update season configuration (dates, times, blackout windows)
- Controller: `AdminController::updateConfig()`

**GET /api/admin/matching/{eventId}**
- Get matching data for event (available boats/crews, capacity analysis)
- Controller: `AdminController::getMatchingData($eventId)`

**POST /api/admin/notifications/{eventId}**
- Send email notifications for event
- Controller: `AdminController::sendNotifications($eventId)`

## Important Architectural Concepts

### Dependency Inversion Principle

Application layer defines **interfaces (ports)**, Infrastructure layer provides **implementations (adapters)**.

Example:
```php
// Application layer defines contract
interface BoatRepositoryInterface {
    public function save(Boat $boat): void;
    public function findByKey(BoatKey $key): ?Boat;
}

// Infrastructure layer implements it
class BoatRepository implements BoatRepositoryInterface {
    // SQLite implementation
}
```

Use cases depend on interfaces, not concrete implementations. This allows swapping SQLite for PostgreSQL without changing business logic.

### Flex Concept

Boat owners can also register as crew, and crew can own boats. This "flex" status affects ranking calculations and prevents double-counting in capacity matching.

**Detection:** `FlexService::isFlexBoatOwner()`, `FlexService::isFlexCrewMember()`

**Impact on Ranking:** Flex status sets `flexibility` rank dimension to 0 (higher priority)

### Deterministic Behavior

The system uses seeded randomization by `crc32($eventId)` to ensure the same inputs always produce identical assignments. This is critical for reproducibility and user trust.

**Implementation:** `SelectionService::shuffle()` uses `srand(crc32($eventId))`

### Capacity Matching (3 Cases)

**`SelectionService::cut()`** handles three scenarios:

1. **Too Few Crews** (`case_1()`)
   - Not enough crew to fill all boats
   - Leave boats partially crewed, rest go to waitlist
   - Example: 10 boats need 20 crew, but only 15 available → crew 7 boats, waitlist 3 boats + 1 crew

2. **Too Many Crews** (`case_2()`)
   - More crew than boat capacity
   - Fill all boats, excess crews go to waitlist
   - Example: 5 boats need 10 crew, but 15 available → crew all 5 boats, waitlist 5 crews

3. **Perfect Fit** (`case_3()`)
   - Exactly enough crew to fill all boats
   - All boats perfectly crewed, no waitlists

### History Tracking

Boats and crews maintain participation history arrays. The `absence` rank dimension dynamically updates based on past no-shows, deprioritizing unreliable participants.

**Storage:**
- **boat_history**: `participated` field ('Y' or '')
- **crew_history**: `boat_key` field (boat assigned or '')

**Rank Impact:** `absence` count increments for each no-show, worsening rank

### Availability States

**Enum:** `AvailabilityStatus`

- `UNAVAILABLE` (0) - Cannot participate
- `AVAILABLE` (1) - Can participate (default for new registrations)
- `GUARANTEED` (2) - Selected for event (set by assignment algorithm)
- `WITHDRAWN` (3) - Explicitly withdrawn (user action)

**Database:** `crew_availability.status` column (integer 0-3)

### Blackout Logic

During event days (10:00-18:00 by default), registration is blocked to prevent mid-event changes.

**Configuration:** `season_config.blackout_from`, `season_config.blackout_to`

**Check:** `TimeServiceInterface::isInBlackoutWindow()`

**Exception:** `BlackoutWindowException` (maps to HTTP 403)

## Testing

Test cases are documented in `/tests/Test cases.numbers` (Apple Numbers spreadsheet). Automated tests use PHPUnit.

**Test Structure:**
- `tests/Unit/` - Unit tests (Domain layer, no external dependencies)
- `tests/Integration/` - Integration tests (Infrastructure layer, in-memory SQLite)
  - **Base Class:** `IntegrationTestCase` - Extends PHPUnit TestCase with Phinx migration support
  - All integration tests extend this base class for automatic schema setup
- `tests/Integration/Api/` - API tests (PHPUnit test suite)
  - `EventApiTest.php` - Event endpoint tests
  - `AuthApiTest.php` - Authentication endpoint tests
  - `UserProfileApiTest.php` - User profile endpoint tests
  - `AvailabilityApiTest.php` - Availability endpoint tests
  - `AssignmentApiTest.php` - Assignment endpoint tests
  - `AdminApiTest.php` - Admin endpoint tests
- `tests/JAWS_API.postman_collection.json` - Postman test collection

**Running Tests:**
```bash
# All tests
./vendor/bin/phpunit

# Unit tests only
./vendor/bin/phpunit tests/Unit

# Integration tests only
./vendor/bin/phpunit tests/Integration

# Specific test
./vendor/bin/phpunit tests/Unit/Domain/SelectionServiceTest.php
```

**Writing Tests:**

**Unit Test Example (Domain):**
```php
// Test business logic without database
$selectionService = new SelectionService();
$boats = [new Boat(...), new Boat(...)];
$result = $selectionService->shuffle($boats, new EventId('Fri May 29'));
$this->assertEquals($expected, $result);
```

**Integration Test Example (Infrastructure):**
```php
use Tests\Integration\IntegrationTestCase;

class MyIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();  // Runs all Phinx migrations + initializes season config

        // Your test-specific setup
        $this->repository = new BoatRepository();
    }

    public function testRepositorySave(): void
    {
        // $this->pdo is available (in-memory SQLite)
        // All tables exist with latest schema from Phinx migrations
        $boat = new Boat(...);
        $this->repository->save($boat);
        $found = $this->repository->findByKey($boat->getKey());
        $this->assertEquals($boat, $found);
    }
}
```

**Database Setup:**
- Integration tests use Phinx migrations (NOT SQL fixtures)
- Schema is always up-to-date with `database/migrations/*.php`
- `tests/fixtures/*.sql` are archived (deprecated, see `tests/fixtures/ARCHIVED_README.md`)
- Base class provides utilities: `createTestEvent()`, `createTestUser()`

## Dependencies

**Composer Packages:**
```json
{
  "require": {
    "php": "^8.1",
    "phpmailer/phpmailer": "^7.0",
    "eluceo/ical": "^2.13",
    "vlucas/phpdotenv": "^5.6",
    "ext-pdo": "*",
    "ext-pdo_sqlite": "*"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.5",
    "robmorgan/phinx": "^0.16"
  }
}
```

**Environment Variables:**
- `DB_PATH` - Database file path (default: `database/jaws.db`)
- `JWT_SECRET` - JWT signing secret (minimum 32 characters, required)
- `JWT_EXPIRATION_MINUTES` - Token expiration (default: 60)
- `SMTP_HOST` - SMTP server hostname (default: `email-smtp.ca-central-1.amazonaws.com`)
- `SMTP_PORT` - SMTP server port (default: `587`)
- `SMTP_SECURE` - SMTP encryption (default: `tls`)
- `SMTP_USERNAME` - SMTP authentication username
- `SMTP_PASSWORD` - SMTP authentication password
- `EMAIL_FROM` - From email address
- `EMAIL_FROM_NAME` - From name
- `ADMIN_NOTIFICATION_EMAIL` - Admin email for notifications
- `APP_DEBUG` - Debug mode (true/false)
- `APP_ENV` - Environment (production/development)
- `APP_TIMEZONE` - Timezone (default: `America/Toronto`)
- `CORS_ALLOWED_ORIGINS` - Comma-separated origins
- `CORS_ALLOWED_HEADERS` - Comma-separated headers (default: `Content-Type,Authorization`)

**Configuration:** `config/config.php` (reads environment variables with defaults)

## Development Workflows

The following common development tasks have dedicated skills for step-by-step guidance:

- **`/add-endpoint`** - Adding new REST API endpoints
- **`/modify-schema`** - Modifying database schema with Phinx migrations
- **`/add-ranking`** - Adding new ranking criteria to boat/crew selection
- **`/add-rule`** - Adding new optimization rules to assignment algorithm

These skills provide detailed instructions, code examples, checklists, and best practices.

### Working with Time

The `TimeServiceInterface` provides time-aware methods for testing:

**Production Mode:** Uses system clock
**Simulated Mode:** Uses `season_config.simulated_date` (for testing)

**Configuration:** `season_config.source` (production|simulated)

**Usage:**
```php
$timeService = $container->get(TimeServiceInterface::class);
$now = $timeService->getCurrentTime(); // DateTime
$isBlackout = $timeService->isInBlackoutWindow($now);
```

## Common Patterns

### Dependency Injection

All dependencies are wired in `config/container.php`:

```php
$container = new Container();

// Repositories
$container->set(BoatRepositoryInterface::class, fn() => new BoatRepository());

// Services
$container->set(TimeServiceInterface::class, fn() => new SystemTimeService($config));

// Use Cases
$container->set(UpdateBoatAvailabilityUseCase::class, fn() => new UpdateBoatAvailabilityUseCase(
    $container->get(BoatRepositoryInterface::class)
));
```

### Loading and Saving Entities

```php
// Inject repository via constructor
class UpdateBoatAvailabilityUseCase {
    public function __construct(
        private BoatRepositoryInterface $boatRepository
    ) {}

    public function execute(string $ownerFirstName, string $ownerLastName, UpdateAvailabilityRequest $request): BoatResponse {
        // Find existing boat by owner name
        $boat = $this->boatRepository->findByOwnerName($ownerFirstName, $ownerLastName);

        // Update boat availabilities
        foreach ($request->availabilities as $eventId => $berths) {
            $this->boatRepository->setAvailability($boat->getKey(), new EventId($eventId), $berths);
        }

        return BoatResponse::fromEntity($boat);
    }
}
```

### Accessing Entities

```php
// Find by key
$boat = $boatRepository->findByKey(new BoatKey('sailaway'));
$crew = $crewRepository->findByName('John', 'Doe');

// Find by availability
$availableBoats = $boatRepository->findAvailableForEvent(new EventId('Fri May 29'));
$availableCrews = $crewRepository->findAvailableForEvent(new EventId('Fri May 29'));
```

### Working with Events

```php
// Load events
$allEvents = $eventRepository->findAll();
$futureEvents = $eventRepository->findFutureEvents($currentTime);
$nextEvent = $eventRepository->findNextEvent($currentTime);

// Process events
foreach ($futureEvents as $event) {
    $flotilla = $seasonRepository->getFlotilla($event->getEventId());
    // Process flotilla...
}
```

### Error Handling

Exceptions are automatically mapped to HTTP status codes by `ErrorHandlerMiddleware`:

- `BoatNotFoundException` → 404
- `CrewNotFoundException` → 404
- `EventNotFoundException` → 404
- `ValidationException` → 400
- `BlackoutWindowException` → 403
- Generic exceptions → 500

**Example:**
```php
public function execute(UpdateAvailabilityRequest $request): void {
    if (empty($request->availabilities)) {
        throw new ValidationException('Availabilities are required');
    }
    // ...
}
```

### Repository Pattern

Repositories handle all database interactions:

```php
interface BoatRepositoryInterface {
    public function save(Boat $boat): void;
    public function findByKey(BoatKey $key): ?Boat;
    public function findAll(): array;
    public function findAvailableForEvent(EventId $eventId): array;
    public function setAvailability(BoatKey $key, EventId $eventId, int $berths): void;
    public function getAvailability(BoatKey $key, EventId $eventId): int;
    public function setHistory(BoatKey $key, EventId $eventId, bool $participated): void;
    public function getHistory(BoatKey $key, EventId $eventId): bool;
}
```

Implementation in `src/Infrastructure/Persistence/SQLite/BoatRepository.php`

## File Paths Reference

**Entry Point:**
- `public/index.php` - REST API entry point

**Configuration:**
- `config/config.php` - Application configuration
- `config/container.php` - Dependency injection
- `config/routes.php` - API route definitions

**Domain Layer (Pure Business Logic):**
- `src/Domain/Entity/Boat.php`
- `src/Domain/Entity/Crew.php`
- `src/Domain/Entity/User.php`
- `src/Domain/Service/SelectionService.php` ⚠️ CRITICAL ALGORITHM
- `src/Domain/Service/AssignmentService.php` ⚠️ CRITICAL ALGORITHM
- `src/Domain/Service/RankingService.php`
- `src/Domain/Service/FlexService.php`

**Application Layer (Use Cases & Ports):**
- `src/Application/UseCase/Season/ProcessSeasonUpdateUseCase.php` ⚠️ MAIN ORCHESTRATOR
- `src/Application/Port/Repository/BoatRepositoryInterface.php`
- `src/Application/Port/Repository/CrewRepositoryInterface.php`
- `src/Application/Port/Repository/UserRepositoryInterface.php`

**Infrastructure Layer (Database & External Services):**
- `src/Infrastructure/Persistence/SQLite/Connection.php`
- `src/Infrastructure/Persistence/SQLite/BoatRepository.php`
- `src/Infrastructure/Persistence/SQLite/CrewRepository.php`
- `src/Infrastructure/Persistence/SQLite/UserRepository.php`
- `src/Infrastructure/Service/PhpMailerEmailService.php`
- `src/Infrastructure/Service/AwsSesEmailService.php`
- `src/Infrastructure/Service/EmailTemplateService.php`
- `src/Infrastructure/Service/ICalendarService.php`
- `src/Infrastructure/Service/SystemTimeService.php`
- `src/Infrastructure/Service/JwtTokenService.php`
- `src/Infrastructure/Service/PhpPasswordService.php`

**Presentation Layer (HTTP/API):**
- `src/Presentation/Controller/AuthController.php`
- `src/Presentation/Controller/UserController.php`
- `src/Presentation/Controller/EventController.php`
- `src/Presentation/Controller/AvailabilityController.php`
- `src/Presentation/Controller/AssignmentController.php`
- `src/Presentation/Controller/AdminController.php`
- `src/Presentation/Middleware/JwtAuthMiddleware.php`
- `src/Presentation/Middleware/ErrorHandlerMiddleware.php`
- `src/Presentation/Middleware/CorsMiddleware.php`

**Database:**
- `database/jaws.db` - SQLite database
- `database/migrations/` - Phinx migration files
- `database/init_database.php` - Database initialization script

**Tests:**
- `tests/Unit/Domain/` - Domain layer unit tests
- `tests/Integration/Infrastructure/` - Infrastructure integration tests
- `tests/Integration/Api/` - API endpoint tests (PHPUnit)
- `tests/JAWS_API.postman_collection.json` - Postman collection

**Documentation:**
- `README.md` - Comprehensive project documentation
- `CLAUDE.md` - This file
- `docs/JAWS_Clean_Architecture_Refactoring_Plan.md` - Refactoring plan
- `docs/PHASE_*_COMPLETE.md` - Phase completion summaries

**Legacy (Reference Only):**
- `legacy/` - Original pre-refactoring codebase (archived)

## Critical Success Factors

1. **Preserve Business Logic:** Selection and Assignment algorithms must produce identical results to legacy system
2. **Maintain Determinism:** Same inputs always produce same outputs (use seeded randomization)
3. **Respect Layer Boundaries:** Never violate dependency direction (outer → inner only)
4. **Test Thoroughly:** Unit tests for Domain, integration tests for Infrastructure, API tests for Presentation
5. **Document Changes:** Update CLAUDE.md and README.md when architecture changes

## Migration Notes

**From Legacy Architecture:**
- Original codebase in `legacy/` folder (preserved for reference)
- Selection/Assignment algorithms preserved character-for-character in Domain layer
- `season_update.php` replaced by `ProcessSeasonUpdateUseCase`
- PHP forms replaced by REST API endpoints

**Key Migrations:**
- `legacy/Libraries/Selection/` → `src/Domain/Service/SelectionService.php`
- `legacy/Libraries/Assignment/` → `src/Domain/Service/AssignmentService.php`
- `legacy/Libraries/Fleet/` → `src/Domain/Collection/Fleet.php` + `src/Infrastructure/Persistence/SQLite/BoatRepository.php`
- `legacy/Libraries/Squad/` → `src/Domain/Collection/Squad.php` + `src/Infrastructure/Persistence/SQLite/CrewRepository.php`
- `legacy/season_update.php` → `src/Application/UseCase/Season/ProcessSeasonUpdateUseCase.php`

## Completed Enhancements

**Phase 7: Clean Architecture Refactoring** ✅
- Migrated legacy codebase to Clean Architecture (4 layers)
- Preserved all business logic algorithms character-for-character
- Created comprehensive test suite (PHPUnit)
- Built REST API with dependency injection
- Implemented Phinx database migrations

**Phase 8: JWT Authentication & User Management** ✅
- Implemented User entity with secure password hashing (PHP password_hash)
- Created authentication use cases (Login, Register, Logout)
- Built JwtTokenService for token generation/validation
- Added user registration with email/password
- Created users table migration
- Protected API endpoints with JWT middleware

**Phase 9: Frontend Application** ✅
- Built vanilla JavaScript multi-page application in `/public/app`
- Implemented 13 HTML pages with responsive CSS
- Created service-oriented JS architecture (10+ service modules)
- Integrated JWT authentication flow
- Added mobile navigation with hamburger menu
- No framework dependencies (pure ES6+ modules)

**Phase 10: CI/CD Pipeline** ✅
- Set up GitHub Actions workflow (`.github/workflows/ci.yml`)
- Smart test execution strategy:
  - **On push:** Unit tests only (fast feedback, no database required)
  - **On pull request:** Full test suite (unit, integration, API tests with database)
- Parallel job execution: build, unit tests, database setup, integration tests, API tests
- Automated PHPUnit test execution with proper test isolation
- Database seeding with Phinx for integration tests
- Artifact caching for faster builds
- Runs on all branches (unit tests) and pull requests (full suite)

## Potential Future Enhancements

**Authentication Improvements**
- Password reset functionality via email
- Email verification for new accounts
- Two-factor authentication (2FA)
- OAuth integration (Google, GitHub)

**Frontend Modernization**
- Migrate to modern framework (React, Vue, Svelte) if needed
- Real-time updates via WebSockets for live flotilla changes
- Progressive Web App (PWA) capabilities
- Mobile native application (React Native, Flutter)

**Testing & Quality**
- Expand test coverage to >80%
- Add code coverage reporting to CI
- Integrate static analysis tools (PHPStan, Psalm)
- Add code style checks (PHPCS, PHP-CS-Fixer)
- Performance testing and profiling

**Infrastructure & Operations**
- Automated deployment pipeline
- Database backup automation
- Application performance monitoring (APM)
- Error tracking integration (Sentry, Rollbar)
- Horizontal scaling capabilities
