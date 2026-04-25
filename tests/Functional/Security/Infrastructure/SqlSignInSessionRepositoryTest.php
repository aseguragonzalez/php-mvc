<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Infrastructure;

use AlfonsoSG\Mvc\Security\Domain\Entities\CurrentIdentity;
use AlfonsoSG\Mvc\Security\Domain\Entities\SignInChallenge;
use AlfonsoSG\Mvc\Security\Domain\Entities\SignInSession;
use AlfonsoSG\Mvc\Security\Infrastructure\SqlSignInSessionRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SqlSignInSessionRepositoryTest extends TestCase
{
    private MockObject&\PDO $pdo;
    private SqlSignInSessionRepository $repository;

    /** @var array<\PDOStatement> */
    private array $prepareStatementQueue = [];

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->repository = new SqlSignInSessionRepository($this->pdo);
        $this->prepareStatementQueue = [];
    }

    public function testSaveSavesChallengeAndSession(): void
    {
        $token = 'session_token_123';
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $username = 'test@example.com';

        $challenge = SignInChallenge::build($token, $expiresAt);
        $identity = CurrentIdentity::build(
            isAuthenticated: true,
            roles: ['ROLE_USER'],
            username: $username
        );
        $session = SignInSession::build($challenge, $identity);
        $challengeStmt = $this->createMock(\PDOStatement::class);
        $challengeStmt->expects($this->once())
            ->method('execute')
            ->with([
                'id' => $token,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ])
        ;
        $this->prepareStatementQueue[] = $challengeStmt;
        $sessionStmt = $this->createMock(\PDOStatement::class);
        $sessionStmt->expects($this->once())
            ->method('execute')
            ->with([
                'sign_in_challenge_id' => $token,
                'user_id' => $username,
            ])
        ;
        $this->prepareStatementQueue[] = $sessionStmt;

        $this->setupPrepareCallback();

        $this->repository->save($session);
    }

    public function testGetByTokenReturnsSessionWithIdentityAndRoles(): void
    {
        $token = 'session_token_123';
        $username = 'test@example.com';
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $sessionStmt = $this->createMock(\PDOStatement::class);
        $sessionStmt->expects($this->once())
            ->method('execute')
            ->with(['token' => $token])
        ;
        $sessionStmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([
                'challenge_id' => $token,
                'challenge_expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'user_id' => $username,
            ])
        ;
        $this->prepareStatementQueue[] = $sessionStmt;
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

        $result = $this->repository->getByToken($token);

        $this->assertInstanceOf(SignInSession::class, $result);
        $this->assertSame($token, $result->challenge->getToken());
        $this->assertSame($username, $result->identity->username());
        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $result->identity->getRoles());
        $this->assertTrue($result->identity->isAuthenticated());
        $this->assertInstanceOf(CurrentIdentity::class, $result->identity);
    }

    public function testGetByTokenReturnsNullWhenNotFound(): void
    {
        $token = 'nonexistent_token';
        $sessionStmt = $this->createMock(\PDOStatement::class);
        $sessionStmt->expects($this->once())
            ->method('execute')
            ->with(['token' => $token])
        ;
        $sessionStmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn(false)
        ;
        $this->prepareStatementQueue[] = $sessionStmt;

        $this->setupPrepareCallback();

        $result = $this->repository->getByToken($token);

        $this->assertNull($result);
    }

    public function testDeleteByTokenDeletesSession(): void
    {
        $token = 'session_token_123';
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['token' => $token])
        ;
        $this->prepareStatementQueue[] = $stmt;

        $this->setupPrepareCallback();

        $this->repository->deleteByToken($token);
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
