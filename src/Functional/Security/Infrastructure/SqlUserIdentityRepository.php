<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Infrastructure;

use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;

final readonly class SqlUserIdentityRepository implements UserIdentityRepository
{
    public function __construct(private \PDO $db) {}

    public function save(UserIdentity $user): void
    {
        $username = $user->username();

        $sql = <<<'SQL'
                INSERT INTO users (
                    id,
                    password_hash,
                    is_active,
                    is_blocked
                )
                VALUES (
                    :id,
                    :password_hash,
                    :is_active,
                    :is_blocked
                )
                ON DUPLICATE KEY UPDATE
                    password_hash = VALUES(password_hash),
                    is_active = VALUES(is_active),
                    is_blocked = VALUES(is_blocked)
            SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $username,
            'password_hash' => $user->passwordHash,
            'is_active' => $user->isActive ? 1 : 0,
            'is_blocked' => $user->isBlocked ? 1 : 0,
        ]);

        $deleteRolesSql = 'DELETE FROM user_roles WHERE user_id = :user_id';
        $deleteStmt = $this->db->prepare($deleteRolesSql);
        $deleteStmt->execute(['user_id' => $username]);

        if (!empty($user->roles)) {
            $insertRoleSql = 'INSERT INTO user_roles (user_id, role) VALUES (:user_id, :role)';
            $insertRoleStmt = $this->db->prepare($insertRoleSql);
            foreach ($user->roles as $role) {
                $insertRoleStmt->execute([
                    'user_id' => $username,
                    'role' => $role,
                ]);
            }
        }
    }

    public function getByUsername(string $username): ?UserIdentity
    {
        $sql = 'SELECT * FROM users WHERE id = :username';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => $username]);

        /** @var array{id: string, password_hash: string, is_active: bool|int, is_blocked: bool|int}|false $userData */
        $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (false === $userData) {
            return null;
        }

        $roles = $this->fetchRolesForUser($username);

        return UserIdentity::build(
            passwordHash: $userData['password_hash'],
            roles: $roles,
            username: $userData['id'],
            isActive: (bool) $userData['is_active'],
            isBlocked: (bool) $userData['is_blocked']
        );
    }

    public function existsByUsername(string $username): bool
    {
        $sql = 'SELECT COUNT(*) as count FROM users WHERE id = :username';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => $username]);

        /** @var array{count: string}|false $result */
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (false === $result) {
            return false;
        }

        return (int) $result['count'] > 0;
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

        return array_map(fn ($row) => $row['role'], $rolesData);
    }
}
