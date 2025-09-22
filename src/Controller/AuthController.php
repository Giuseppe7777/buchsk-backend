<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\TelnyxOtpService;

#[Route('/auth', name: 'auth_')]
final class AuthController extends AbstractController
{
    public function __construct(
      private TelnyxOtpService $telnyxOtp,
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $users,
        \Psr\Log\LoggerInterface $logger,
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

        // send OTP
        try {
          $result = $this->telnyxOtp->sendOtp($phone);

          if (isset($result['data'])) {
              $logger->info('OTP sent', ['phone' => $phone, 'result' => $result]);
              $message = 'User created. Verification SMS sent.';
          } elseif (isset($result['errors'])) {
              $logger->error('OTP sending failed', ['phone' => $phone, 'errors' => $result['errors']]);
              $message = 'User created, but OTP failed to send. Please try resend.';
          } else {
              $logger->warning('Unexpected OTP response', ['phone' => $phone, 'result' => $result]);
              $message = 'User created, but verification service returned unexpected response.';
          }
        } catch (\Throwable $e) {
            $logger->critical('OTP send exception', ['phone' => $phone, 'exception' => $e->getMessage()]);
            $message = 'User created, but verification service unavailable. Please try later.';
        }

        // Response
        return $this->json([
          'status' => JsonResponse::HTTP_CREATED,
          'message' => $message,
          'data' => [
            'id' => $user->getId(),
            'phone' => $user->getPhone()
          ],
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/send-otp', name: 'send-otp', methods: ['POST'])]
    public function sendOtp(
        Request $request,
    ): JsonResponse 
    {
        // TODO: sent OTP 
        return $this->json([
          "status" => JsonResponse::HTTP_OK,
          "message" => "otp verified (stub)",
          "data" => null
        ]);
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
