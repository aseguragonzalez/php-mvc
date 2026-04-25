<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Application\SignOut;

use AlfonsoSG\Mvc\Security\Application\SignOut\SignOutCommand;
use AlfonsoSG\Mvc\Security\Application\SignOut\SignOutHandler;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignInSessionRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SignOutTest extends TestCase
{
    public function testExecuteDeletesSessionByToken(): void
    {
        $signInSessionRepository = $this->createMock(SignInSessionRepository::class);
        $signInSessionRepository->expects($this->once())
            ->method('deleteByToken')
            ->with('the-token')
        ;

        $handler = new SignOutHandler($signInSessionRepository);

        $handler->execute(new SignOutCommand('the-token'));
    }
}
