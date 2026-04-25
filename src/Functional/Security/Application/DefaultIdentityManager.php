<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security\Application;

use AlfonsoSG\Mvc\Security\Application\ActivateUserIdentity\ActivateUserIdentity;
use AlfonsoSG\Mvc\Security\Application\ActivateUserIdentity\ActivateUserIdentityCommand;
use AlfonsoSG\Mvc\Security\Application\GetIdentity\GetIdentity;
use AlfonsoSG\Mvc\Security\Application\GetIdentity\GetIdentityCommand;
use AlfonsoSG\Mvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPassword;
use AlfonsoSG\Mvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPasswordCommand;
use AlfonsoSG\Mvc\Security\Application\RefreshSignInSession\RefreshSignInSession;
use AlfonsoSG\Mvc\Security\Application\RefreshSignInSession\RefreshSignInSessionCommand;
use AlfonsoSG\Mvc\Security\Application\RequestResetPassword\RequestResetPassword;
use AlfonsoSG\Mvc\Security\Application\RequestResetPassword\RequestResetPasswordCommand;
use AlfonsoSG\Mvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromToken;
use AlfonsoSG\Mvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromTokenCommand;
use AlfonsoSG\Mvc\Security\Application\SignIn\SignIn;
use AlfonsoSG\Mvc\Security\Application\SignIn\SignInCommand;
use AlfonsoSG\Mvc\Security\Application\SignOut\SignOut;
use AlfonsoSG\Mvc\Security\Application\SignOut\SignOutCommand;
use AlfonsoSG\Mvc\Security\Application\SignUp\SignUp;
use AlfonsoSG\Mvc\Security\Application\SignUp\SignUpCommand;
use AlfonsoSG\Mvc\Security\Challenge;
use AlfonsoSG\Mvc\Security\Identity;
use AlfonsoSG\Mvc\Security\IdentityManager;

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
