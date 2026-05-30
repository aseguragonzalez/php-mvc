<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Infrastructure;

use PhpMvc\Security\Domain\Entities\UserIdentity;
use PhpMvc\Security\Infrastructure\SqlUserIdentityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SqlUserIdentityRepositoryTest extends TestCase
{
    private MockObject&\PDO $pdo;
    private SqlUserIdentityRepository $repository;

    /** @var array<\PDOStatement> */
    private array $prepareStatementQueue = [];

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->repository = new SqlUserIdentityRepository($this->pdo);
        $this->prepareStatementQueue = [];
    }

    public function testSaveInsertsNewUser(): void
    {
        $passwordHash = password_hash('secret', PASSWORD_BCRYPT);
        $user = $this->createUserIdentity(
            username: 'test@example.com',
            passwordHash: $passwordHash,
            isActive: true,
            isBlocked: false,
            roles: ['ROLE_USER', 'ROLE_ADMIN']
        );
        $insertStmt = $this->createMock(\PDOStatement::class);
        $insertStmt->expects($this->once())
            ->method('execute')
            ->with([
                'id' => 'test@example.com',
                'password_hash' => $passwordHash,
                'is_active' => 1,
                'is_blocked' => 0,
            ])
        ;
        $this->prepareStatementQueue[] = $insertStmt;
        $deleteRolesStmt = $this->createMock(\PDOStatement::class);
        $deleteRolesStmt->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 'test@example.com'])
        ;
        $this->prepareStatementQueue[] = $deleteRolesStmt;
        $insertRoleStmt = $this->createMock(\PDOStatement::class);
        $expectedRoles = ['ROLE_USER', 'ROLE_ADMIN'];
        $calledRoles = [];
        $insertRoleStmt->expects($this->exactly(2))
            ->method('execute')
            ->with($this->callback(function (array $params) use (&$calledRoles, $expectedRoles): bool {
                if (
                    isset($params['user_id'])
                    && 'test@example.com' === $params['user_id']
                    && isset($params['role'])
                    && in_array($params['role'], $expectedRoles, true)
                ) {
                    $calledRoles[] = $params['role'];

                    return true;
                }

                return false;
            }))
            ->willReturn(true)
        ;

        $this->prepareStatementQueue[] = $insertRoleStmt;

        $this->setupPrepareCallback();

        $this->repository->save($user);

        $this->assertCount(2, $calledRoles);
        $this->assertContains('ROLE_USER', $calledRoles);
        $this->assertContains('ROLE_ADMIN', $calledRoles);
    }

    public function testSaveUpdatesExistingUser(): void
    {
        $passwordHash = password_hash('secret', PASSWORD_BCRYPT);
        $user = $this->createUserIdentity(
            username: 'test@example.com',
            passwordHash: $passwordHash,
            isActive: false,
            isBlocked: true,
            roles: []
        );
        $insertStmt = $this->createMock(\PDOStatement::class);
        $insertStmt->expects($this->once())
            ->method('execute')
            ->with([
                'id' => 'test@example.com',
                'password_hash' => $passwordHash,
                'is_active' => 0,
                'is_blocked' => 1,
            ])
        ;
        $this->prepareStatementQueue[] = $insertStmt;
        $deleteRolesStmt = $this->createMock(\PDOStatement::class);
        $deleteRolesStmt->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 'test@example.com'])
        ;
        $this->prepareStatementQueue[] = $deleteRolesStmt;
        $this->setupPrepareCallback();

        $this->repository->save($user);
    }

    public function testSaveDeletesAndReinsertsRoles(): void
    {
        $user = $this->createUserIdentity(
            username: 'test@example.com',
            passwordHash: password_hash('secret', PASSWORD_BCRYPT),
            isActive: true,
            isBlocked: false,
            roles: ['ROLE_USER']
        );
        $insertStmt = $this->createMock(\PDOStatement::class);
        $insertStmt->expects($this->once())
            ->method('execute')
        ;
        $this->prepareStatementQueue[] = $insertStmt;
        $deleteRolesStmt = $this->createMock(\PDOStatement::class);
        $deleteRolesStmt->expects($this->once())
            ->method('execute')
            ->with(['user_id' => 'test@example.com'])
        ;
        $this->prepareStatementQueue[] = $deleteRolesStmt;
        $insertRoleStmt = $this->createMock(\PDOStatement::class);
        $insertRoleStmt->expects($this->once())
            ->method('execute')
            ->with([
                'user_id' => 'test@example.com',
                'role' => 'ROLE_USER',
            ])
        ;
        $this->prepareStatementQueue[] = $insertRoleStmt;

        $this->setupPrepareCallback();

        $this->repository->save($user);
    }

    public function testGetByUsernameReturnsUserWithRoles(): void
    {
        $username = 'test@example.com';
        $passwordHash = password_hash('secret', PASSWORD_BCRYPT);
        $userStmt = $this->createMock(\PDOStatement::class);
        $userStmt->expects($this->once())
            ->method('execute')
            ->with(['username' => $username])
        ;
        $userStmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => $username,
                'password_hash' => $passwordHash,
                'is_active' => 1,
                'is_blocked' => 0,
            ])
        ;
        $this->prepareStatementQueue[] = $userStmt;
        $rolesStmt = $this->createMock(\PDOStatement::class);
        $rolesStmt->expects($this->once())
            ->method('execute')
            ->with(['user_id' => $username])
        ;
        $rolesStmt->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([
                ['role' => 'ROLE_USER'],
                ['role' => 'ROLE_ADMIN'],
            ])
        ;
        $this->prepareStatementQueue[] = $rolesStmt;

        $this->setupPrepareCallback();

        $result = $this->repository->getByUsername($username);

        $this->assertInstanceOf(UserIdentity::class, $result);
        $this->assertSame($username, $result->username());
        $this->assertSame($passwordHash, $result->passwordHash);
        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $result->roles);
        $this->assertTrue($result->isActive);
        $this->assertFalse($result->isBlocked);
    }

    public function testGetByUsernameReturnsNullWhenNotFound(): void
    {
        $username = 'nonexistent@example.com';
        $userStmt = $this->createMock(\PDOStatement::class);
        $userStmt->expects($this->once())
            ->method('execute')
            ->with(['username' => $username])
        ;
        $userStmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn(false)
        ;
        $this->prepareStatementQueue[] = $userStmt;

        $this->setupPrepareCallback();

        $result = $this->repository->getByUsername($username);

        $this->assertNull($result);
    }

    public function testGetByUsernameHandlesUserWithoutRoles(): void
    {
        $username = 'test@example.com';
        $userStmt = $this->createMock(\PDOStatement::class);
        $userStmt->expects($this->once())
            ->method('execute')
            ->with(['username' => $username])
        ;
        $userStmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => $username,
                'password_hash' => password_hash('secret', PASSWORD_BCRYPT),
                'is_active' => 1,
                'is_blocked' => 0,
            ])
        ;
        $this->prepareStatementQueue[] = $userStmt;
        $rolesStmt = $this->createMock(\PDOStatement::class);
        $rolesStmt->expects($this->once())
            ->method('execute')
            ->with(['user_id' => $username])
        ;
        $rolesStmt->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([])
        ;
        $this->prepareStatementQueue[] = $rolesStmt;
        $this->setupPrepareCallback();

        $result = $this->repository->getByUsername($username);

        $this->assertInstanceOf(UserIdentity::class, $result);
        $this->assertSame([], $result->roles);
    }

    public function testExistsByUsernameReturnsTrueWhenExists(): void
    {
        $username = 'test@example.com';
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['username' => $username])
        ;
        $stmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn(['count' => '1'])
        ;
        $this->prepareStatementQueue[] = $stmt;

        $this->setupPrepareCallback();

        $result = $this->repository->existsByUsername($username);

        $this->assertTrue($result);
    }

    public function testExistsByUsernameReturnsFalseWhenNotExists(): void
    {
        $username = 'nonexistent@example.com';
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['username' => $username])
        ;
        $stmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn(['count' => '0'])
        ;
        $this->prepareStatementQueue[] = $stmt;

        $this->setupPrepareCallback();

        $result = $this->repository->existsByUsername($username);

        $this->assertFalse($result);
    }

    public function testExistsByUsernameReturnsFalseWhenFetchFails(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['username' => 'user@example.com']);
        $stmt->expects($this->once())->method('fetch')->with(\PDO::FETCH_ASSOC)->willReturn(false);
        $this->prepareStatementQueue[] = $stmt;

        $this->setupPrepareCallback();

        $this->assertFalse($this->repository->existsByUsername('user@example.com'));
    }

    /**
     * @param array<string> $roles
     */
    private function createUserIdentity(
        string $username,
        string $passwordHash,
        bool $isActive,
        bool $isBlocked,
        array $roles
    ): UserIdentity {
        return UserIdentity::build(
            passwordHash: $passwordHash,
            roles: $roles,
            username: $username,
            isActive: $isActive,
            isBlocked: $isBlocked
        );
    }

    private function setupPrepareCallback(): void
    {
        $this->pdo->expects($this->atLeastOnce())
            ->method('prepare')
            ->willReturnCallback(function (string $sql): \PDOStatement {
                if (empty($this->prepareStatementQueue)) {
                    $fallbackStmt = $this->createMock(\PDOStatement::class);
                    $fallbackStmt->expects($this->any())->method('execute');
                    $fallbackStmt->expects($this->any())->method('fetch')->willReturn(false);
                    $fallbackStmt->expects($this->any())->method('fetchAll')->willReturn([]);

                    return $fallbackStmt;
                }

                return array_shift($this->prepareStatementQueue);
            })
        ;
    }
}
