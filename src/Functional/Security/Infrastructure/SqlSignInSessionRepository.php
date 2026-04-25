<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Infrastructure;

use AlfonsoSG\Mvc\Security\Domain\Entities\CurrentIdentity;
use AlfonsoSG\Mvc\Security\Domain\Entities\SignInChallenge;
use AlfonsoSG\Mvc\Security\Domain\Entities\SignInSession;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignInSessionRepository;

final readonly class SqlSignInSessionRepository implements SignInSessionRepository
{
    public function __construct(private \PDO $db) {}

    public function save(SignInSession $session): void
    {
        $token = $session->challenge->getToken();
        $expiresAt = $session->challenge->getExpiresAt();
        $username = $session->identity->username();

        $challengeSql = <<<'SQL'
                INSERT INTO sign_in_challenges (id, expires_at)
                VALUES (:id, :expires_at)
                ON DUPLICATE KEY UPDATE
                    expires_at = VALUES(expires_at)
            SQL;

        $challengeStmt = $this->db->prepare($challengeSql);
        $challengeStmt->execute([
            'id' => $token,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        $sessionSql = <<<'SQL'
                INSERT INTO sign_in_sessions (sign_in_challenge_id, user_id)
                VALUES (:sign_in_challenge_id, :user_id)
                ON DUPLICATE KEY UPDATE
                    sign_in_challenge_id = VALUES(sign_in_challenge_id),
                    user_id = VALUES(user_id)
            SQL;

        $sessionStmt = $this->db->prepare($sessionSql);
        $sessionStmt->execute([
            'sign_in_challenge_id' => $token,
            'user_id' => $username,
        ]);
    }

    public function getByToken(string $token): ?SignInSession
    {
        $sql = <<<'SQL'
                SELECT
                    sic.id as challenge_id,
                    sic.expires_at as challenge_expires_at,
                    u.id as user_id
                FROM sign_in_sessions sis
                INNER JOIN sign_in_challenges sic ON sis.sign_in_challenge_id = sic.id
                INNER JOIN users u ON sis.user_id = u.id
                WHERE sic.id = :token
            SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);

        /** @var array{challenge_id: string, challenge_expires_at: string, user_id: string}|false $row */
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (false === $row) {
            return null;
        }

        $roles = $this->fetchRolesForUser($row['user_id']);

        $challenge = SignInChallenge::build(
            token: $row['challenge_id'],
            expiresAt: new \DateTimeImmutable($row['challenge_expires_at'])
        );

        $identity = CurrentIdentity::build(
            isAuthenticated: true,
            roles: $roles,
            username: $row['user_id']
        );

        return SignInSession::build(
            challenge: $challenge,
            identity: $identity
        );
    }

    public function deleteByToken(string $token): void
    {
        $sql = 'DELETE FROM sign_in_sessions WHERE sign_in_challenge_id = :token';
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
