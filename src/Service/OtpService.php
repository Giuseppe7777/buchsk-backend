<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserOtp;
use App\Repository\UserOtpRepository;
use Doctrine\ORM\EntityManagerInterface;

interface SmsSender {
    public function send(string $to, string $message): void;
}

final class OtpService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserOtpRepository $otps,
        private SmsSender $smsSender,
    ) {}

    public function send(User $user, bool $enforceThrottle = true): array
    {
        $now = new \DateTimeImmutable();

        // throttle: не частіше ніж раз на 60 сек і макс 5 за годину
        if ($enforceThrottle) {
            $oneMinAgo = $now->modify('-60 seconds');
            $recent = $this->otps->createQueryBuilder('o')
                ->andWhere('o.user = :u AND o.isUsed = false AND o.createdAt > :t')
                ->setParameter('u', $user)
                ->setParameter('t', $oneMinAgo)
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            if ($recent) {
                return ['sent' => false, 'reason' => 'too_frequent', 'retryInSec' => 60];
            }

            $oneHourAgo = $now->modify('-1 hour');
            $countLastHour = (int) $this->otps->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->andWhere('o.user = :u AND o.createdAt > :t')
                ->setParameter('u', $user)
                ->setParameter('t', $oneHourAgo)
                ->getQuery()->getSingleScalarResult();
            if ($countLastHour >= 5) {
                return ['sent' => false, 'reason' => 'too_many_requests'];
            }
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = (new UserOtp())
            ->setUser($user)
            ->setCode($code)
            ->setExpiresAt($now->modify('+5 minutes'))
            ->setIsUsed(false);

        $this->em->persist($otp);
        $this->em->flush();

        // продакшн: не логувати код; локально можна
        $this->smsSender->send($user->getPhone(), "Your verification code: {$code}");

        return ['sent' => true, 'expiresIn' => 300];
    }
}
