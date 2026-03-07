<?php

declare(strict_types=1);

namespace App\Application\Port\Repository;

/**
 * Dashboard Repository Interface
 *
 * Defines aggregate queries for the admin dashboard.
 * Returns raw arrays — no entity hydration.
 */
interface DashboardRepositoryInterface
{
    /**
     * Get crew availability status counts for an event
     *
     * @param string $eventId
     * @return array<int, int> Map of status value => count
     */
    public function getCrewStatusCountsForEvent(string $eventId): array;

    /**
     * Get number of boats registered for an event (berths > 0)
     *
     * @param string $eventId
     * @return int
     */
    public function getBoatRegistrationCountForEvent(string $eventId): int;

    /**
     * Get crew skill distribution across all crews
     *
     * @return array<int, int> Map of skill value => count
     */
    public function getCrewSkillDistribution(): array;

    /**
     * Get crew commitment rank distribution across all crews
     *
     * @return array<int, int> Map of rank_commitment value => count
     */
    public function getCrewCommitmentDistribution(): array;

    /**
     * Get cron notification status for an event
     *
     * @param string $eventId
     * @return array<string, string|null> Map of type => sent_at (or null)
     */
    public function getNotificationStatusForEvent(string $eventId): array;

    /**
     * Get the flotilla generated_at timestamp for an event
     *
     * @param string $eventId
     * @return string|null ISO timestamp or null if not generated
     */
    public function getFlotillaTimestamp(string $eventId): ?string;

    /**
     * Get waitlist counts from the stored flotilla JSON for an event
     *
     * @param string $eventId
     * @return array{boats: int, crews: int} Zero counts when no flotilla exists
     */
    public function getWaitlistCountsForEvent(string $eventId): array;

    /**
     * Get aggregate user counts
     *
     * @return array{total: int, crew_accounts: int, boat_owner_accounts: int, admin_count: int}
     */
    public function getUserCounts(): array;

    /**
     * Get boat flexibility distribution (flex vs standard)
     *
     * @return array{flex: int}
     */
    public function getBoatFlexDistribution(): array;

    /**
     * Get boats with high absence rank
     *
     * @param int $absenceThreshold Minimum rank_absence to include
     * @return array<int, array{key: string, display_name: string, rank_absence: int, assistance_required: bool}>
     */
    public function getNotableBoats(int $absenceThreshold): array;

    /**
     * Get crews with high absence or penalty commitment rank
     *
     * @param int $absenceThreshold Minimum rank_absence to include
     * @param int $penaltyRank Exact rank_commitment value to flag as penalised
     * @return array<int, array{key: string, display_name: string, rank_absence: int, rank_commitment: int}>
     */
    public function getNotableCrews(int $absenceThreshold, int $penaltyRank): array;
}
