<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Domain\Entities;

use PhpMvc\Security\Domain\Entities\CurrentIdentity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CurrentIdentityTest extends TestCase
{
    public function testBuildCreatesInstanceWithGivenValues(): void
    {
        $identity = CurrentIdentity::build(true, ['admin', 'user'], 'john');
        $this->assertInstanceOf(CurrentIdentity::class, $identity);
        $this->assertTrue($identity->isAuthenticated());
        $this->assertSame(['admin', 'user'], $identity->getRoles());
        $this->assertSame('john', $identity->username());
    }

    public function testIsAuthenticatedReturnsFalse(): void
    {
        $identity = CurrentIdentity::build(false, ['guest'], 'anonymous');
        $this->assertFalse($identity->isAuthenticated());
    }

    public function testGetRolesReturnsRoles(): void
    {
        $roles = ['editor', 'viewer'];
        $identity = CurrentIdentity::build(true, $roles, 'alice');
        $this->assertSame($roles, $identity->getRoles());
    }

    public function testHasRoleReturnsTrueIfRoleExists(): void
    {
        $identity = CurrentIdentity::build(true, ['admin', 'user'], 'john');
        $this->assertTrue($identity->hasRole('admin'));
        $this->assertTrue($identity->hasRole('user'));
    }

    public function testHasRoleReturnsFalseIfRoleDoesNotExist(): void
    {
        $identity = CurrentIdentity::build(true, ['admin', 'user'], 'john');
        $this->assertFalse($identity->hasRole('guest'));
    }

    public function testUsernameReturnsUsername(): void
    {
        $identity = CurrentIdentity::build(true, ['admin'], 'john');
        $this->assertSame('john', $identity->username());
    }
}
