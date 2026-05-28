<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Security\Domain\Entities;

use PhpMvc\Security\Domain\Entities\UserIdentity;
use PhpMvc\Security\Domain\Exceptions\InvalidCredentialsException;
use PhpMvc\Security\Domain\Exceptions\UserBlockedException;
use PhpMvc\Security\Domain\Exceptions\UserIsNotActiveException;
use PhpMvc\Security\Domain\Exceptions\UsernameIsNotEmailException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserIdentityTest extends TestCase
{
    public function testNewCreatesIdentityWithCorrectUsernameAndRoles(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $this->assertInstanceOf(UserIdentity::class, $identity);
        $this->assertEquals('user@domain.com', $identity->username());
        $this->assertEquals(['ROLE_USER'], $identity->roles);
        $this->assertFalse($identity->isActive);
        $this->assertFalse($identity->isAuthenticated());
        $this->assertFalse($identity->isBlocked);
        $identity->validatePassword('password');
    }

    public function testNewStoresPasswordAsBcryptHash(): void
    {
        $identity = UserIdentity::new('user@domain.com', [], 'secret');
        $this->assertTrue(password_verify('secret', $identity->passwordHash));
    }

    public function testBuildCreatesIdentityWithAllProperties(): void
    {
        $passwordHash = password_hash('password', PASSWORD_BCRYPT);

        $identity = UserIdentity::build($passwordHash, ['ROLE_USER'], 'user@domain.com', true, false);
        $this->assertInstanceOf(UserIdentity::class, $identity);
        $this->assertEquals('user@domain.com', $identity->username());
        $this->assertEquals(['ROLE_USER'], $identity->roles);
        $this->assertTrue($identity->isActive);
        $this->assertFalse($identity->isAuthenticated());
        $this->assertFalse($identity->isBlocked);
        $identity->validatePassword('password');
    }

    public function testAnonymousReturnsAnonymousIdentity(): void
    {
        $identity = UserIdentity::anonymous();
        $this->assertEquals('anonymous', $identity->username());
        $this->assertEquals([], $identity->roles);
        $this->assertFalse($identity->isActive);
        $this->assertFalse($identity->isAuthenticated());
        $this->assertFalse($identity->isBlocked);
    }

    public function testActivateAndDeactivate(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $activated = $identity->activate();
        $this->assertTrue($activated->isActive);
        $deactivated = $activated->deactivate();
        $this->assertFalse($deactivated->isActive);
    }

    public function testBlockAndUnblock(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $blocked = $identity->block();
        $this->assertTrue($blocked->isBlocked);
        $unblocked = $blocked->unblock();
        $this->assertFalse($unblocked->isBlocked);
    }

    public function testHasRoleReturnsTrueIfRoleExists(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER', 'ROLE_ADMIN'], 'password');
        $this->assertTrue($identity->hasRole('ROLE_USER'));
        $this->assertTrue($identity->hasRole('ROLE_ADMIN'));
    }

    public function testHasRoleReturnsFalseIfRoleDoesNotExist(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $this->assertFalse($identity->hasRole('ROLE_ADMIN'));
    }

    public function testValidatePasswordThrowsExceptionOnInvalidPassword(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $this->expectException(InvalidCredentialsException::class);
        $identity->validatePassword('wrongpassword');
    }

    public function testValidatePasswordDoesNotThrowOnValidPassword(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $identity->validatePassword('password');
        $this->assertTrue('user@domain.com' === $identity->username());
    }

    public function testAuthenticateReturnsAuthenticatedIdentity(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $activatedIdentity = $identity->activate();
        $authenticated = $activatedIdentity->authenticate('password');
        $this->assertTrue($authenticated->isAuthenticated());
        $this->assertEquals($identity->username(), $authenticated->username());
    }

    public function testUpdatePasswordFailsWithOldPassword(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $activatedIdentity = $identity->activate();
        $updated = $activatedIdentity->updatePassword('newpass');
        $this->expectException(InvalidCredentialsException::class);
        $updated->validatePassword('password');
    }

    public function testUpdatePasswordChangesPassword(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $activatedIdentity = $identity->activate();
        $updated = $activatedIdentity->updatePassword('newpass');
        $authenticated = $updated->authenticate('newpass');
        $this->assertTrue($authenticated->isAuthenticated());
    }

    public function testUpdatePasswordGeneratesNewBcryptHash(): void
    {
        $identity = UserIdentity::new('user@domain.com', [], 'oldpass')->activate();
        $updated = $identity->updatePassword('newpass');
        $this->assertTrue(password_verify('newpass', $updated->passwordHash));
        $this->assertFalse(password_verify('oldpass', $updated->passwordHash));
    }

    public function testUpdateRolesChangesRoles(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $updated = $identity->updateRoles(['ROLE_ADMIN']);
        $this->assertEquals(['ROLE_ADMIN'], $updated->roles);
    }

    public function testUpdateRolesToEmptyArray(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $updated = $identity->updateRoles([]);
        $this->assertEquals([], $updated->roles);
    }

    public function testBlockAlreadyBlockedIdentity(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password')->block();
        $blockedAgain = $identity->block();
        $this->assertTrue($blockedAgain->isBlocked);
    }

    public function testUnblockAlreadyUnblockedIdentity(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $unblockedAgain = $identity->unblock();
        $this->assertFalse($unblockedAgain->isBlocked);
    }

    public function testActivateAlreadyActiveIdentity(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password')->activate();
        $activatedAgain = $identity->activate();
        $this->assertTrue($activatedAgain->isActive);
    }

    public function testDeactivateAlreadyInactiveIdentity(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $deactivatedAgain = $identity->deactivate();
        $this->assertFalse($deactivatedAgain->isActive);
    }

    public function testAuthenticateThrowsIfInactive(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $this->expectException(UserIsNotActiveException::class);
        $identity->authenticate('password');
    }

    public function testAuthenticateThrowsIfBlocked(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password')->activate()->block();
        $this->expectException(UserBlockedException::class);
        $identity->authenticate('password');
    }

    public function testAuthenticate(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password')->activate();

        $authenticatedUser = $identity->authenticate('password');

        $this->assertTrue($authenticatedUser->isAuthenticated());
    }

    public function testAuthenticateDoNothingIfAlreadyAuthenticated(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password')->activate();
        $authenticatedUser = $identity->authenticate('password');

        $updatedUser = $authenticatedUser->authenticate('password');

        $this->assertTrue($updatedUser->isAuthenticated());
    }

    public function testUpdatePasswordThrowsIfInactive(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password');
        $this->expectException(UserIsNotActiveException::class);
        $identity->updatePassword('newpass');
    }

    public function testUpdatePasswordThrowsIfBlocked(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password')->activate()->block();
        $this->expectException(UserBlockedException::class);
        $identity->updatePassword('newpass');
    }

    public function testGetCurrentIdentityReturnsCurrentIdentity(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['ROLE_USER'], 'password')
            ->activate()
            ->authenticate('password')
        ;
        $current = $identity->getCurrentIdentity();
        $this->assertEquals($identity->username(), $current->username());
        $this->assertEquals($identity->getRoles(), $current->getRoles());
        $this->assertTrue($current->isAuthenticated());
    }

    public function testThrowsExceptionIfUsernameIsNotEmail(): void
    {
        $this->expectException(UsernameIsNotEmailException::class);
        UserIdentity::new('not-an-email', ['ROLE_USER'], 'password');
    }
}
