<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Заборона логіну, якщо не підтверджений
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Account is not verified. Please verify OTP first.');
        }

        // Опційно: ще й статус
        if ($user->getStatus() !== 'active') {
            throw new CustomUserMessageAccountStatusException('Account is not active.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Нічого
    }
}
