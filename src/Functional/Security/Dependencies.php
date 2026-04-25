<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Security;

use AlfonsoSG\Mvc\Security\Application\ActivateUserIdentity\ActivateUserIdentity;
use AlfonsoSG\Mvc\Security\Application\ActivateUserIdentity\ActivateUserIdentityHandler;
use AlfonsoSG\Mvc\Security\Application\DefaultIdentityManager;
use AlfonsoSG\Mvc\Security\Application\GetIdentity\GetIdentity;
use AlfonsoSG\Mvc\Security\Application\GetIdentity\GetIdentityHandler;
use AlfonsoSG\Mvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPassword;
use AlfonsoSG\Mvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPasswordHandler;
use AlfonsoSG\Mvc\Security\Application\RefreshSignInSession\RefreshSignInSession;
use AlfonsoSG\Mvc\Security\Application\RefreshSignInSession\RefreshSignInSessionHandler;
use AlfonsoSG\Mvc\Security\Application\RequestResetPassword\RequestResetPassword;
use AlfonsoSG\Mvc\Security\Application\RequestResetPassword\RequestResetPasswordHandler;
use AlfonsoSG\Mvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromToken;
use AlfonsoSG\Mvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromTokenHandler;
use AlfonsoSG\Mvc\Security\Application\SignIn\SignIn;
use AlfonsoSG\Mvc\Security\Application\SignIn\SignInHandler;
use AlfonsoSG\Mvc\Security\Application\SignOut\SignOut;
use AlfonsoSG\Mvc\Security\Application\SignOut\SignOutHandler;
use AlfonsoSG\Mvc\Security\Application\SignUp\SignUp;
use AlfonsoSG\Mvc\Security\Application\SignUp\SignUpHandler;
use AlfonsoSG\Mvc\Security\Domain\Repositories\ResetPasswordChallengeRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignInSessionRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\SignUpChallengeRepository;
use AlfonsoSG\Mvc\Security\Domain\Repositories\UserIdentityRepository;
use AlfonsoSG\Mvc\Security\Infrastructure\SqlResetPasswordChallengeRepository;
use AlfonsoSG\Mvc\Security\Infrastructure\SqlSignInSessionRepository;
use AlfonsoSG\Mvc\Security\Infrastructure\SqlSignUpChallengeRepository;
use AlfonsoSG\Mvc\Security\Infrastructure\SqlUserIdentityRepository;
use DI\Container;

final class Dependencies
{
    public static function configure(Container $container): void
    {
        $container->set(UserIdentityRepository::class, $container->get(SqlUserIdentityRepository::class));
        $container->set(SignInSessionRepository::class, $container->get(SqlSignInSessionRepository::class));
        $container->set(SignUpChallengeRepository::class, $container->get(SqlSignUpChallengeRepository::class));
        $container->set(
            ResetPasswordChallengeRepository::class,
            $container->get(SqlResetPasswordChallengeRepository::class)
        );

        $container->set(SignUp::class, $container->get(SignUpHandler::class));
        $container->set(ActivateUserIdentity::class, $container->get(ActivateUserIdentityHandler::class));
        $container->set(SignIn::class, $container->get(SignInHandler::class));
        $container->set(GetIdentity::class, $container->get(GetIdentityHandler::class));
        $container->set(RefreshSignInSession::class, $container->get(RefreshSignInSessionHandler::class));
        $container->set(ModifyUserIdentityPassword::class, $container->get(ModifyUserIdentityPasswordHandler::class));
        $container->set(RequestResetPassword::class, $container->get(RequestResetPasswordHandler::class));
        $container->set(ResetPasswordFromToken::class, $container->get(ResetPasswordFromTokenHandler::class));
        $container->set(SignOut::class, $container->get(SignOutHandler::class));
        $container->set(IdentityManager::class, $container->get(DefaultIdentityManager::class));
    }
}
