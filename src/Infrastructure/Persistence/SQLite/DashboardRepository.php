<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SQLite;

use App\Application\Port\Repository\DashboardRepositoryInterface;
use PDO;

/**
 * SQLite Dashboard Repository
 *
 * Implements aggregate queries for the admin dashboard.
 * No entity hydration — returns raw arrays for performance.
 */
class DashboardRepository implements DashboardRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function getCrewStatusCountsForEvent(string $eventId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT status, COUNT(*) AS cnt
            FROM crew_availability
            WHERE event_id = :event_id
            GROUP BY status
        ');
        $stmt->execute(['event_id' => $eventId]);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int)$row['status']] = (int)$row['cnt'];
        }
        return $counts;
    }

    public function getBoatRegistrationCountForEvent(string $eventId): int
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) AS cnt
            FROM boat_availability
            WHERE event_id = :event_id AND berths > 0
        ');
        $stmt->execute(['event_id' => $eventId]);
        return (int)$stmt->fetchColumn();
    }

    public function getCrewSkillDistribution(): array
    {
        $stmt = $this->pdo->query('
            SELECT skill, COUNT(*) AS cnt
            FROM crews
            GROUP BY skill
        ');

        $dist = [];
        foreach ($stmt->fetchAll() as $row) {
            $dist[(int)$row['skill']] = (int)$row['cnt'];
        }
        return $dist;
    }

    public function getCrewCommitmentDistribution(): array
    {
        $stmt = $this->pdo->query('
            SELECT rank_commitment, COUNT(*) AS cnt
            FROM crews
            GROUP BY rank_commitment
        ');

        $dist = [];
        foreach ($stmt->fetchAll() as $row) {
            $dist[(int)$row['rank_commitment']] = (int)$row['cnt'];
        }
        return $dist;
    }

    public function getNotificationStatusForEvent(string $eventId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT type, sent_at
            FROM cron_notifications
            WHERE event_id = :event_id
        ');
        $stmt->execute(['event_id' => $eventId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['type']] = $row['sent_at'];
        }
        return $result;
    }

    public function getFlotillaTimestamp(string $eventId): ?string
    {
        $stmt = $this->pdo->prepare('
            SELECT generated_at
            FROM flotillas
            WHERE event_id = :event_id
            LIMIT 1
        ');
        $stmt->execute(['event_id' => $eventId]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string)$value : null;
    }

    public function getWaitlistCountsForEvent(string $eventId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT flotilla_data
            FROM flotillas
            WHERE event_id = :event_id
            LIMIT 1
        ');
        $stmt->execute(['event_id' => $eventId]);
        $json = $stmt->fetchColumn();

        if ($json === false) {
            return ['boats' => 0, 'crews' => 0];
        }

        $data = json_decode((string)$json, true);

        return [
            'boats' => count($data['waitlist_boats'] ?? []),
            'crews' => count($data['waitlist_crews'] ?? []),
        ];
    }

    public function getUserCounts(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN account_type = 'crew' THEN 1 ELSE 0 END) AS crew_accounts,
                SUM(CASE WHEN account_type = 'boat_owner' THEN 1 ELSE 0 END) AS boat_owner_accounts,
                SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) AS admin_count
            FROM users
        ");
        $row = $stmt->fetch();
        return [
            'total'               => (int)$row['total'],
            'crew_accounts'       => (int)$row['crew_accounts'],
            'boat_owner_accounts' => (int)$row['boat_owner_accounts'],
            'admin_count'         => (int)$row['admin_count'],
        ];
    }

    public function getBoatFlexDistribution(): array
    {
        $stmt = $this->pdo->query('
            SELECT rank_flexibility, COUNT(*) AS cnt
            FROM boats
            GROUP BY rank_flexibility
        ');

        $dist = [];
        foreach ($stmt->fetchAll() as $row) {
            $dist[(int)$row['rank_flexibility']] = (int)$row['cnt'];
        }

        return [
            'flex' => $dist[0] ?? 0,
        ];
    }

    public function getNotableBoats(int $absenceThreshold): array
    {
        $stmt = $this->pdo->prepare("
            SELECT key, display_name, rank_absence, assistance_required
            FROM boats
            WHERE rank_absence >= :threshold OR assistance_required = 'Yes'
            ORDER BY rank_absence DESC
        ");
        $stmt->execute(['threshold' => $absenceThreshold]);

        return array_map(fn($row) => [
            'key'                 => $row['key'],
            'display_name'        => $row['display_name'],
            'rank_absence'        => (int)$row['rank_absence'],
            'assistance_required' => $row['assistance_required'] === 'Yes',
        ], $stmt->fetchAll());
    }

    public function getNotableCrews(int $absenceThreshold, int $penaltyRank): array
    {
        $stmt = $this->pdo->prepare('
            SELECT key, display_name, rank_absence, rank_commitment
            FROM crews
            WHERE rank_absence >= :absence OR rank_commitment = :penalty
            ORDER BY rank_absence DESC
        ');
        $stmt->execute(['absence' => $absenceThreshold, 'penalty' => $penaltyRank]);

        return array_map(fn($row) => [
            'key'             => $row['key'],
            'display_name'    => $row['display_name'],
            'rank_absence'    => (int)$row['rank_absence'],
            'rank_commitment' => (int)$row['rank_commitment'],
        ], $stmt->fetchAll());
    }
}
