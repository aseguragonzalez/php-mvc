<?php

declare(strict_types=1);

namespace PhpMvc\Security;

interface IdentityManager
{
    /**
     * @param array<string> $roles
     */
    public function signUp(string $username, string $password, array $roles): void;

    public function activateUserIdentity(string $token): void;

    public function signIn(string $username, string $password, bool $keepMeSignedIn): Challenge;

    public function getIdentity(?string $token): Identity;

    public function refreshSignInSession(string $token): Challenge;

    public function modifyUserIdentityPassword(string $token, string $currentPassword, string $newPassword): void;

    public function resetPasswordChallenge(string $username): void;

    public function resetPasswordFromToken(string $token, string $newPassword): void;

    public function signOut(string $token): void;
}
