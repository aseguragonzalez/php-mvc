<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Security\Application;

use AlfonsoSG\Mvc\Security\Application\ActivateUserIdentity\ActivateUserIdentity;
use AlfonsoSG\Mvc\Security\Application\DefaultIdentityManager;
use AlfonsoSG\Mvc\Security\Application\GetIdentity\GetIdentity;
use AlfonsoSG\Mvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPassword;
use AlfonsoSG\Mvc\Security\Application\RefreshSignInSession\RefreshSignInSession;
use AlfonsoSG\Mvc\Security\Application\RequestResetPassword\RequestResetPassword;
use AlfonsoSG\Mvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromToken;
use AlfonsoSG\Mvc\Security\Application\SignIn\SignIn;
use AlfonsoSG\Mvc\Security\Application\SignOut\SignOut;
use AlfonsoSG\Mvc\Security\Application\SignUp\SignUp;
use AlfonsoSG\Mvc\Security\Challenge;
use AlfonsoSG\Mvc\Security\Domain\Entities\UserIdentity;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\InvalidCredentialsException;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\ResetPasswordChallengeException;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\SessionExpiredException;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\SignUpChallengeException;
use AlfonsoSG\Mvc\Security\Domain\Exceptions\UserIsNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DefaultIdentityManagerTest extends TestCase
{
    private SignUp&Stub $signUp;
    private ActivateUserIdentity&Stub $activateUserIdentity;
    private SignIn&Stub $signIn;
    private GetIdentity&Stub $getIdentity;
    private RefreshSignInSession&Stub $refreshSignInSession;
    private ModifyUserIdentityPassword&Stub $modifyUserIdentityPassword;
    private RequestResetPassword&Stub $requestResetPassword;
    private ResetPasswordFromToken&Stub $resetPasswordFromToken;
    private SignOut&Stub $signOut;
    private DefaultIdentityManager $manager;

    protected function setUp(): void
    {
        $this->signUp = $this->createStub(SignUp::class);
        $this->activateUserIdentity = $this->createStub(ActivateUserIdentity::class);
        $this->signIn = $this->createStub(SignIn::class);
        $this->getIdentity = $this->createStub(GetIdentity::class);
        $this->refreshSignInSession = $this->createStub(RefreshSignInSession::class);
        $this->modifyUserIdentityPassword = $this->createStub(ModifyUserIdentityPassword::class);
        $this->requestResetPassword = $this->createStub(RequestResetPassword::class);
        $this->resetPasswordFromToken = $this->createStub(ResetPasswordFromToken::class);
        $this->signOut = $this->createStub(SignOut::class);
        $this->manager = new DefaultIdentityManager(
            $this->signUp,
            $this->activateUserIdentity,
            $this->signIn,
            $this->getIdentity,
            $this->refreshSignInSession,
            $this->modifyUserIdentityPassword,
            $this->requestResetPassword,
            $this->resetPasswordFromToken,
            $this->signOut
        );
    }

    #[DataProvider('tokenProvider')]
    public function testGetIdentityReturnsAnonymousForEmptyTokenParametrized(?string $token): void
    {
        $anonymous = UserIdentity::anonymous();
        $this->getIdentity->method('execute')->willReturn($anonymous);

        $identity = $this->manager->getIdentity($token);

        $this->assertSame($anonymous, $identity);
        $this->assertFalse($identity->isAuthenticated());
        $this->assertEquals('anonymous', $identity->username());
    }

    /**
     * @return array<int, list<null|string>>
     */
    public static function tokenProvider(): array
    {
        return [
            [''],
            [null],
            ['   '],
            ["\n"],
            ["\t"],
        ];
    }

    public function testGetIdentityReturnsSessionIdentity(): void
    {
        $identity = UserIdentity::new('user@domain.com', ['role'], 'pass');
        $this->getIdentity->method('execute')->willReturn($identity);

        $actualIdentity = $this->manager->getIdentity('token');

        $this->assertSame($identity, $actualIdentity);
    }

    public function testSignUpCreatesUserAndChallenge(): void
    {
        $this->manager->signUp('user@domain.com', 'pass', ['role']);

        $this->addToAssertionCount(1);
    }

    public function testSignUpDoesNothingIfUserExists(): void
    {
        $this->manager->signUp('user@domain.com', 'pass', ['role']);

        $this->addToAssertionCount(1);
    }

    public function testActivateUserIdentityActivatesUser(): void
    {
        $this->manager->activateUserIdentity('token');

        $this->addToAssertionCount(1);
    }

    public function testActivateUserIdentityDoesNothingIfChallengeNotFound(): void
    {
        $this->activateUserIdentity->method('execute')->willThrowException(
            new SignUpChallengeException('token')
        );

        $this->expectException(SignUpChallengeException::class);

        $this->manager->activateUserIdentity('token');
    }

    public function testActivateUserIdentityDoesNothingIfChallengeExpired(): void
    {
        $this->activateUserIdentity->method('execute')->willThrowException(
            new SignUpChallengeException('token')
        );

        $this->expectException(SignUpChallengeException::class);

        $this->manager->activateUserIdentity('token');
    }

    public function testSignInThrowsIfUserNotFound(): void
    {
        $this->signIn->method('execute')->willThrowException(
            new InvalidCredentialsException('user')
        );

        $this->expectException(InvalidCredentialsException::class);

        $this->manager->signIn('user', 'pass', false);
    }

    public function testSignInThrowsIfPasswordInvalid(): void
    {
        $this->signIn->method('execute')->willThrowException(
            new InvalidCredentialsException('user')
        );

        $this->expectException(InvalidCredentialsException::class);

        $this->manager->signIn('user', 'wrongpass', false);
    }

    public function testSignInCreatesSessionAndReturnsChallenge(): void
    {
        $challenge = $this->createStub(Challenge::class);
        $challenge->method('getToken')->willReturn('session-token');
        $this->signIn->method('execute')->willReturn($challenge);

        $result = $this->manager->signIn('user@domain.com', 'pass', false);

        $this->assertInstanceOf(Challenge::class, $result);
        $this->assertSame($challenge, $result);
        $this->assertNotEmpty($result->getToken());
    }

    public function testSignInWithRememberMeCreatesSessionAndReturnsChallenge(): void
    {
        $challenge = $this->createStub(Challenge::class);
        $challenge->method('getToken')->willReturn('session-token');
        $challenge->method('getExpiresAt')->willReturn(new \DateTimeImmutable()->modify('+20 minutes'));
        $this->signIn->method('execute')->willReturn($challenge);

        $result = $this->manager->signIn('user@domain.com', 'pass', true);

        $this->assertInstanceOf(Challenge::class, $result);
        $this->assertNotEmpty($result->getToken());
    }

    public function testRefreshSignInSessionThrowsSessionExpiredIfSessionNotFound(): void
    {
        $this->refreshSignInSession->method('execute')->willThrowException(
            new SessionExpiredException()
        );

        $this->expectException(SessionExpiredException::class);

        $this->manager->refreshSignInSession('token');
    }

    public function testRefreshSignInSessionThrowsSessionExpiredIfChallengeExpired(): void
    {
        $this->refreshSignInSession->method('execute')->willThrowException(
            new SessionExpiredException()
        );

        $this->expectException(SessionExpiredException::class);

        $this->manager->refreshSignInSession('token');
    }

    public function testRefreshSignInSessionUpdatesSession(): void
    {
        $challenge = $this->createStub(Challenge::class);
        $this->refreshSignInSession->method('execute')->willReturn($challenge);

        $result = $this->manager->refreshSignInSession('token');

        $this->assertInstanceOf(Challenge::class, $result);
    }

    public function testModifyUserIdentityPasswordUpdatesPassword(): void
    {
        $this->manager->modifyUserIdentityPassword('token', 'old', 'new');

        $this->addToAssertionCount(1);
    }

    public function testModifyUserIdentityPasswordThrowsIfUserNotFound(): void
    {
        $this->modifyUserIdentityPassword->method('execute')->willThrowException(
            new UserIsNotFoundException('user@domain.com')
        );

        $this->expectException(UserIsNotFoundException::class);

        $this->manager->modifyUserIdentityPassword('token', 'old', 'new');
    }

    public function testSignOutDeletesSession(): void
    {
        $this->manager->signOut('token');

        $this->addToAssertionCount(1);
    }

    public function testResetPasswordChallengeDoesNothingIfUserNotFound(): void
    {
        $this->manager->resetPasswordChallenge('user');

        $this->addToAssertionCount(1);
    }

    public function testResetPasswordChallengeCreatesChallengeAndNotifies(): void
    {
        $this->manager->resetPasswordChallenge('user');

        $this->addToAssertionCount(1);
    }

    public function testResetPasswordFromTokenDoesNothingIfUserNotFound(): void
    {
        $this->manager->resetPasswordFromToken('token', 'newpass');

        $this->addToAssertionCount(1);
    }

    public function testResetPasswordFromTokenUpdatesPassword(): void
    {
        $this->manager->resetPasswordFromToken('token', 'newpass');

        $this->addToAssertionCount(1);
    }

    public function testResetPasswordFromTokenDoNothingIfChallengeNotFound(): void
    {
        $this->manager->resetPasswordFromToken('token', 'newpass');

        $this->addToAssertionCount(1);
    }

    public function testResetPasswordFromTokenThrowsIfChallengeExpired(): void
    {
        $this->resetPasswordFromToken->method('execute')->willThrowException(
            new ResetPasswordChallengeException('token')
        );

        $this->expectException(ResetPasswordChallengeException::class);

        $this->manager->resetPasswordFromToken('token', 'newpass');
    }
}
