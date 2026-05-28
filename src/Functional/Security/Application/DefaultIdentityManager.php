<?php

declare(strict_types=1);

namespace PhpMvc\Security\Application;

use PhpMvc\Security\Application\ActivateUserIdentity\ActivateUserIdentity;
use PhpMvc\Security\Application\ActivateUserIdentity\ActivateUserIdentityCommand;
use PhpMvc\Security\Application\GetIdentity\GetIdentity;
use PhpMvc\Security\Application\GetIdentity\GetIdentityCommand;
use PhpMvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPassword;
use PhpMvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPasswordCommand;
use PhpMvc\Security\Application\RefreshSignInSession\RefreshSignInSession;
use PhpMvc\Security\Application\RefreshSignInSession\RefreshSignInSessionCommand;
use PhpMvc\Security\Application\RequestResetPassword\RequestResetPassword;
use PhpMvc\Security\Application\RequestResetPassword\RequestResetPasswordCommand;
use PhpMvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromToken;
use PhpMvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromTokenCommand;
use PhpMvc\Security\Application\SignIn\SignIn;
use PhpMvc\Security\Application\SignIn\SignInCommand;
use PhpMvc\Security\Application\SignOut\SignOut;
use PhpMvc\Security\Application\SignOut\SignOutCommand;
use PhpMvc\Security\Application\SignUp\SignUp;
use PhpMvc\Security\Application\SignUp\SignUpCommand;
use PhpMvc\Security\Challenge;
use PhpMvc\Security\Identity;
use PhpMvc\Security\IdentityManager;

final readonly class DefaultIdentityManager implements IdentityManager
{
    public function __construct(
        private SignUp $signUp,
        private ActivateUserIdentity $activateUserIdentity,
        private SignIn $signIn,
        private GetIdentity $getIdentity,
        private RefreshSignInSession $refreshSignInSession,
        private ModifyUserIdentityPassword $modifyUserIdentityPassword,
        private RequestResetPassword $requestResetPassword,
        private ResetPasswordFromToken $resetPasswordFromToken,
        private SignOut $signOut,
    ) {}

    /**
     * @param array<string> $roles
     */
    public function signUp(string $username, string $password, array $roles): void
    {
        $this->signUp->execute(new SignUpCommand($username, $password, $roles));
    }

    public function activateUserIdentity(string $token): void
    {
        $this->activateUserIdentity->execute(new ActivateUserIdentityCommand($token));
    }

    public function signIn(string $username, string $password, bool $keepMeSignedIn): Challenge
    {
        return $this->signIn->execute(new SignInCommand($username, $password, $keepMeSignedIn));
    }

    public function getIdentity(?string $token): Identity
    {
        return $this->getIdentity->execute(new GetIdentityCommand($token));
    }

    public function refreshSignInSession(string $token): Challenge
    {
        return $this->refreshSignInSession->execute(new RefreshSignInSessionCommand($token));
    }

    public function modifyUserIdentityPassword(string $token, string $currentPassword, string $newPassword): void
    {
        $this->modifyUserIdentityPassword->execute(
            new ModifyUserIdentityPasswordCommand($token, $currentPassword, $newPassword)
        );
    }

    public function resetPasswordChallenge(string $username): void
    {
        $this->requestResetPassword->execute(new RequestResetPasswordCommand($username));
    }

    public function resetPasswordFromToken(string $token, string $newPassword): void
    {
        $this->resetPasswordFromToken->execute(new ResetPasswordFromTokenCommand($token, $newPassword));
    }

    public function signOut(string $token): void
    {
        $this->signOut->execute(new SignOutCommand($token));
    }
}
