<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Port\Repository\BoatRepositoryInterface;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Application\Port\Repository\DashboardRepositoryInterface;
use App\Application\Port\Repository\EventRepositoryInterface;
use App\Application\Port\Repository\SeasonRepositoryInterface;
use App\Domain\ValueObject\EventId;

/**
 * Get Dashboard Use Case
 *
 * Assembles the admin system dashboard: season status, upcoming event health,
 * fleet/crew metrics, user counts, and computed alerts.
 */
class GetDashboardUseCase
{
    private const ABSENCE_THRESHOLD = 2;
    private const PENALTY_RANK      = 1;

    public function __construct(
        private SeasonRepositoryInterface   $seasonRepository,
        private EventRepositoryInterface    $eventRepository,
        private BoatRepositoryInterface     $boatRepository,
        private CrewRepositoryInterface     $crewRepository,
        private DashboardRepositoryInterface $dashboardRepository,
    ) {
    }

    /**
     * Execute the use case
     *
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $config      = $this->seasonRepository->getConfig();
        $nextEventId = $this->eventRepository->findNextEvent();
        $futureIds   = $this->eventRepository->findFutureEvents();

        // ── Season section ────────────────────────────────────────────────────
        $isSimulated   = ($config['source'] ?? 'production') === 'simulated';
        $seasonSection = [
            'year'           => (int)($config['year'] ?? date('Y')),
            'source'         => $config['source'] ?? 'production',
            'is_simulated'   => $isSimulated,
            'simulated_date' => $config['simulated_date'] ?? null,
            'blackout_from'  => $config['blackout_from'] ?? null,
            'blackout_to'    => $config['blackout_to'] ?? null,
        ];

        // ── Events section ────────────────────────────────────────────────────
        $eventsSection = [];
        foreach ($futureIds as $eventIdStr) {
            $eventsSection[] = $this->buildEventData($eventIdStr, $nextEventId);
        }

        // ── Fleet section ─────────────────────────────────────────────────────
        $totalBoats          = $this->boatRepository->count();
        $boatsActiveNext     = $nextEventId !== null
            ? $this->dashboardRepository->getBoatRegistrationCountForEvent($nextEventId)
            : 0;
        $notableBoats        = $this->dashboardRepository->getNotableBoats(self::ABSENCE_THRESHOLD);
        $flexDist            = $this->dashboardRepository->getBoatFlexDistribution();
        $fleetSection        = [
            'total_boats'             => $totalBoats,
            'boats_active_next_event' => $boatsActiveNext,
            'flex_distribution'       => $flexDist,
            'notable'                 => $notableBoats,
        ];

        // ── Crew section ──────────────────────────────────────────────────────
        $totalCrews       = $this->crewRepository->count();
        $skillDist        = $this->dashboardRepository->getCrewSkillDistribution();
        $commitmentDist   = $this->dashboardRepository->getCrewCommitmentDistribution();
        $notableCrews     = $this->dashboardRepository->getNotableCrews(self::ABSENCE_THRESHOLD, self::PENALTY_RANK);

        $crewSection = [
            'total_crews'             => $totalCrews,
            'skill_distribution'      => [
                'novice'       => $skillDist[0] ?? 0,
                'intermediate' => $skillDist[1] ?? 0,
                'advanced'     => $skillDist[2] ?? 0,
            ],
            'commitment_distribution' => [
                'unavailable' => $commitmentDist[0] ?? 0,
                'penalty'     => $commitmentDist[1] ?? 0,
                'available'   => $commitmentDist[2] ?? 0,
                'assigned'    => $commitmentDist[3] ?? 0,
            ],
            'notable' => array_map(fn($crew) => array_merge($crew, [
                'alerts' => $this->buildCrewAlerts($crew),
            ]), $notableCrews),
        ];

        // ── Users section ─────────────────────────────────────────────────────
        $usersSection = $this->dashboardRepository->getUserCounts();

        // ── Alerts ────────────────────────────────────────────────────────────
        $alerts = $this->buildAlerts($isSimulated, $eventsSection);

        return [
            'season'       => $seasonSection,
            'events'       => $eventsSection,
            'fleet'        => $fleetSection,
            'crew'         => $crewSection,
            'users'        => $usersSection,
            'alerts'       => $alerts,
            'generated_at' => (new \DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Build per-event data array
     */
    private function buildEventData(string $eventIdStr, ?string $nextEventId): array
    {
        $eventId   = EventId::fromString($eventIdStr);
        $eventData = $this->eventRepository->findById($eventId);

        // Capacity
        $availableBoats = $this->boatRepository->findAvailableForEvent($eventId);
        $availableCrews = $this->crewRepository->findAvailableForEvent($eventId);

        $totalBerths = 0;
        foreach ($availableBoats as $boat) {
            $totalBerths += $boat->getBerths($eventId);
        }
        $totalCrews = count($availableCrews);

        $scenario = match (true) {
            $totalCrews < $totalBerths => 'too_few_crews',
            $totalCrews > $totalBerths => 'too_many_crews',
            default                    => 'perfect_fit',
        };

        // Crew status counts from DB
        $rawCounts = $this->dashboardRepository->getCrewStatusCountsForEvent($eventIdStr);

        // Notifications
        $notifications = $this->buildNotifications(
            $this->dashboardRepository->getNotificationStatusForEvent($eventIdStr)
        );

        return [
            'event_id'     => $eventIdStr,
            'event_date'   => $eventData['event_date'] ?? null,
            'status'       => $eventData['status'] ?? 'upcoming',
            'is_next_event' => $eventIdStr === $nextEventId,
            'capacity'     => [
                'total_berths'    => $totalBerths,
                'total_crews'     => $totalCrews,
                'scenario'        => $scenario,
                'surplus_deficit' => $totalBerths - $totalCrews,
            ],
            'crew_status_counts' => [
                'unavailable' => $rawCounts[0] ?? 0,
                'available'   => $rawCounts[1] ?? 0,
                'guaranteed'  => $rawCounts[2] ?? 0,
                'withdrawn'   => $rawCounts[3] ?? 0,
            ],
            'boats_registered'    => count($availableBoats),
            'notifications'       => $notifications,
            'flotilla_generated_at' => $this->dashboardRepository->getFlotillaTimestamp($eventIdStr),
            'waitlist'            => $this->dashboardRepository->getWaitlistCountsForEvent($eventIdStr),
        ];
    }

    /**
     * Build notifications sub-array from raw DB notification map
     *
     * @param array<string, string|null> $raw
     */
    private function buildNotifications(array $raw): array
    {
        return [
            'reminder_sent'    => isset($raw['reminder']),
            'reminder_sent_at' => $raw['reminder'] ?? null,
            'crew_list_sent'   => isset($raw['crew-list']),
            'crew_list_sent_at' => $raw['crew-list'] ?? null,
        ];
    }

    /**
     * Build per-crew alert labels
     *
     * @param array{rank_absence: int, rank_commitment: int} $crew
     * @return string[]
     */
    private function buildCrewAlerts(array $crew): array
    {
        $alerts = [];
        if ($crew['rank_absence'] >= self::ABSENCE_THRESHOLD) {
            $alerts[] = 'high_absence';
        }
        if ($crew['rank_commitment'] <= self::PENALTY_RANK) {
            $alerts[] = 'penalty_rank';
        }
        return $alerts;
    }

    /**
     * Build system-level alerts array
     *
     * @param bool  $isSimulated
     * @param array $events
     * @return array<int, array{level: string, type: string, message: string}>
     */
    private function buildAlerts(bool $isSimulated, array $events): array
    {
        $alerts = [];

        if ($isSimulated) {
            $alerts[] = [
                'level'   => 'info',
                'type'    => 'simulated_time',
                'message' => 'System is running in simulated time mode.',
            ];
        }

        foreach ($events as $event) {
            if ($event['boats_registered'] === 0) {
                $alerts[] = [
                    'level'    => 'warning',
                    'type'     => 'no_boats_registered',
                    'message'  => "No boats registered for event {$event['event_id']}.",
                    'event_id' => $event['event_id'],
                ];
            }

            $crewCounts = $event['crew_status_counts'];
            $totalActive = ($crewCounts['available'] ?? 0) + ($crewCounts['guaranteed'] ?? 0);
            if ($totalActive === 0) {
                $alerts[] = [
                    'level'    => 'warning',
                    'type'     => 'no_crews_registered',
                    'message'  => "No crews registered for event {$event['event_id']}.",
                    'event_id' => $event['event_id'],
                ];
            }
        }

        return $alerts;
    }
}
