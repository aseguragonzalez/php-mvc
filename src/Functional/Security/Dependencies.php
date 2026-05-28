<?php

declare(strict_types=1);

namespace PhpMvc\Security;

use PhpMvc\MutableContainerInterface;
use PhpMvc\Security\Application\ActivateUserIdentity\ActivateUserIdentity;
use PhpMvc\Security\Application\ActivateUserIdentity\ActivateUserIdentityHandler;
use PhpMvc\Security\Application\DefaultIdentityManager;
use PhpMvc\Security\Application\GetIdentity\GetIdentity;
use PhpMvc\Security\Application\GetIdentity\GetIdentityHandler;
use PhpMvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPassword;
use PhpMvc\Security\Application\ModifyUserIdentityPassword\ModifyUserIdentityPasswordHandler;
use PhpMvc\Security\Application\RefreshSignInSession\RefreshSignInSession;
use PhpMvc\Security\Application\RefreshSignInSession\RefreshSignInSessionHandler;
use PhpMvc\Security\Application\RequestResetPassword\RequestResetPassword;
use PhpMvc\Security\Application\RequestResetPassword\RequestResetPasswordHandler;
use PhpMvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromToken;
use PhpMvc\Security\Application\ResetPasswordFromToken\ResetPasswordFromTokenHandler;
use PhpMvc\Security\Application\SignIn\SignIn;
use PhpMvc\Security\Application\SignIn\SignInHandler;
use PhpMvc\Security\Application\SignOut\SignOut;
use PhpMvc\Security\Application\SignOut\SignOutHandler;
use PhpMvc\Security\Application\SignUp\SignUp;
use PhpMvc\Security\Application\SignUp\SignUpHandler;
use PhpMvc\Security\Domain\Repositories\ResetPasswordChallengeRepository;
use PhpMvc\Security\Domain\Repositories\SignInSessionRepository;
use PhpMvc\Security\Domain\Repositories\SignUpChallengeRepository;
use PhpMvc\Security\Domain\Repositories\UserIdentityRepository;
use PhpMvc\Security\Infrastructure\SqlResetPasswordChallengeRepository;
use PhpMvc\Security\Infrastructure\SqlSignInSessionRepository;
use PhpMvc\Security\Infrastructure\SqlSignUpChallengeRepository;
use PhpMvc\Security\Infrastructure\SqlUserIdentityRepository;

final class Dependencies
{
    public static function configure(MutableContainerInterface $container): void
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
