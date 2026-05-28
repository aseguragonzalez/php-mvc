<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Application\GetIdentity;

use PhpMvc\Security\Application\GetIdentity\GetIdentityCommand;
use PhpMvc\Security\Application\GetIdentity\GetIdentityHandler;
use PhpMvc\Security\Domain\Repositories\SignInSessionRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class GetIdentityTest extends TestCase
{
    public function testExecuteReturnsAnonymousForNullToken(): void
    {
        $handler = new GetIdentityHandler($this->createStub(SignInSessionRepository::class));

        $identity = $handler->execute(new GetIdentityCommand(null));

        $this->assertFalse($identity->isAuthenticated());
        $this->assertEquals('anonymous', $identity->username());
    }
}
