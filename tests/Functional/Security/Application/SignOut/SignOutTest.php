<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Application\SignOut;

use PhpMvc\Security\Application\SignOut\SignOutCommand;
use PhpMvc\Security\Application\SignOut\SignOutHandler;
use PhpMvc\Security\Domain\Repositories\SignInSessionRepository;
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
