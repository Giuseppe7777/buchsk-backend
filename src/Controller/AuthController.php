<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\OtpService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth', name: 'auth_')]
final class AuthController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $users,
        OtpService $otpService
      ): JsonResponse {

        // TODO: user register
        // read data
        $data = json_decode($request->getContent(), true);
        $phone = $data['phone'] ?? null;
        $password = $data['password'] ?? null;
        $firstName = $data['firstName'] ?? null;
        $lastName = $data['lastName'] ?? null;

        // validation
        if (!$phone || !$password || !$firstName || !$lastName) {
          return $this->json([
            'status' => JsonResponse::HTTP_BAD_REQUEST,
            'message' => 'phone, password, firstName, lastName — are necessarily',
            'data' => null
          ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // phone uniqueness check
        if ($users->findOneBy(['phone' => $phone])) {
          return $this->json([
            'status' => JsonResponse::HTTP_CONFLICT,
            'message' => 'a user with this phone number already exists',
            'data' => null
          ], JsonResponse::HTTP_CONFLICT);
        }

        // user creation
        $user = new User();
        $user->setPhone($phone);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setStatus('pending');
        $user->setIsVerified(false);

        // password hash
        $hashed = $hasher->hashPassword($user, $password);
        $user->setPassword($hashed);

        // create
        $em->persist($user);
        $em->flush();

        $otpMeta = $otpService->send($user, enforceThrottle: false); // first time without a throttle

        // Response
        return $this->json([
          'status' => JsonResponse::HTTP_CREATED,
          'message' => 'User created. Verification SMS sent (if delivery fails, use resend)',
          'data' => [
            'id' => $user->getId(),
            'phone' => $user->getPhone(),
            'otp' => ['sent' => $otpMeta['sent'] ?? false, 'expiresIn' => $otpMeta['expiresIn'] ?? null],
          ],
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/send-otp', name: 'send-otp', methods: ['POST'])]
    public function sendOtp(
        Request $request,
        UserRepository $users,
        OtpService $otpService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $phone = $data['phone'] ?? null;
        if (!$phone) {
            return $this->json(['status'=>400,'message'=>'phone is required','data'=>null], 400);
        }

        // Анти-енумерація: у продакшні краще завжди повертати 200 з однаковим меседжем
        $user = $users->findOneBy(['phone' => $phone]);
        if (!$user) {
            return $this->json(['status'=>200,'message'=>'If the account exists, an SMS has been sent','data'=>null], 200);
        }
        if ($user->isVerified()) {
            return $this->json(['status'=>409,'message'=>'User already verified','data'=>null], 409);
        }

        $res = $otpService->send($user, enforceThrottle: true);
        if (!($res['sent'] ?? false)) {
            $msg = $res['reason'] === 'too_frequent' ? 'Try again in 60 seconds' : 'Too many requests';
            return $this->json(['status'=>429,'message'=>$msg,'data'=>null], 429);
        }

        return $this->json(['status'=>200,'message'=>'OTP sent','data'=>['expiresIn'=>$res['expiresIn'] ?? 300]], 200);
    }

    #[Route('/verify-otp', name: 'verify-otp', methods: ['POST'])]
    public function verifyOtp(Request $request): JsonResponse 
    {
        // TODO: OTP verification and JWT issuance
        return $this->json([
          "status" => JsonResponse::HTTP_OK,
          "message" => "otp verified (stub)",
          "data" => null
        ]);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function loginPassword(Request $request): JsonResponse
    {
        // TODO: Login by phone and password
        return $this->json([
          "status" => JsonResponse::HTTP_OK,
          "message" => "login by password (stub)",
          "data" => null
        ]);
    }
}
