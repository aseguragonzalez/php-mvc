<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Infrastructure;

use AlfonsoSG\Mvc\Security\Domain\Entities\SignUpChallenge;
use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignUpChallengeRepository;

final readonly class SqlSignUpChallengeRepository implements SignUpChallengeRepository
{
    public function __construct(private \PDO $db) {}

    public function save(SignUpChallenge $challenge): void
    {
        $token = $challenge->token;
        $expiresAt = $challenge->expiresAt;
        $username = $challenge->userIdentity->username();

        $sql = <<<'SQL'
                INSERT INTO sign_up_challenges (id, expires_at, user_id)
                VALUES (:id, :expires_at, :user_id)
                ON DUPLICATE KEY UPDATE
                    expires_at = VALUES(expires_at),
                    user_id = VALUES(user_id)
            SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $token,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'user_id' => $username,
        ]);
    }

    public function getByToken(string $token): ?SignUpChallenge
    {
        $sql = <<<'SQL'
                SELECT
                    suc.id,
                    suc.expires_at,
                    suc.user_id,
                    u.password_hash,
                    u.is_active,
                    u.is_blocked
                FROM sign_up_challenges suc
                INNER JOIN users u ON suc.user_id = u.id
                WHERE suc.id = :token
            SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);

        /** @var array{id: string, expires_at: string, user_id: string, password_hash: string, is_active: bool|int, is_blocked: bool|int}|false $row */
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (false === $row) {
            return null;
        }

        $roles = $this->fetchRolesForUser($row['user_id']);

        $userIdentity = UserIdentity::build(
            passwordHash: $row['password_hash'],
            roles: $roles,
            username: $row['user_id'],
            isActive: (bool) $row['is_active'],
            isBlocked: (bool) $row['is_blocked']
        );

        return SignUpChallenge::build(
            token: $row['id'],
            expiresAt: new \DateTimeImmutable($row['expires_at']),
            userIdentity: $userIdentity
        );
    }

    public function deleteByToken(string $token): void
    {
        $sql = 'DELETE FROM sign_up_challenges WHERE id = :token';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);
    }

    /**
     * @return array<string>
     */
    private function fetchRolesForUser(string $userId): array
    {
        $rolesSql = 'SELECT role FROM user_roles WHERE user_id = :user_id';
        $rolesStmt = $this->db->prepare($rolesSql);
        $rolesStmt->execute(['user_id' => $userId]);

        /** @var array<int, array{role: string}> $rolesData */
        $rolesData = $rolesStmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn ($r) => $r['role'], $rolesData);
    }
}
