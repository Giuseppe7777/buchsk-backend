<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelnyxOtpService
{
    private string $apiKey;
    private string $verifyProfileId;
    private HttpClientInterface $http;

    public function __construct(string $telnyx_api_key, string $telnyx_verify_profile_id, HttpClientInterface $http)
    {
        $this->apiKey = $telnyx_api_key;
        $this->verifyProfileId = $telnyx_verify_profile_id;
        $this->http = $http;
    }

    /**
     * Надсилає OTP-код на телефон
     */
    public function sendOtp(string $phone): array
    {
        $response = $this->http->request('POST', 'https://api.telnyx.com/v2/verifications/sms', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'json' => [
                'phone_number'     => $phone,
                'verify_profile_id'=> $this->verifyProfileId,
                'timeout_secs'     => 300, 
            ],
        ]);

        return $response->toArray(false);
    }

    /**
     * Перевіряє OTP-код від користувача
     */
    public function verifyOtp(string $phone, string $code): array
    {
        $url = sprintf('https://api.telnyx.com/v2/verifications/by_phone_number/%s/actions/verify', $phone);

        $response = $this->http->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'json' => [
                'code'              => $code,
                'verify_profile_id' => $this->verifyProfileId,
            ],
        ]);

        return $response->toArray(false);
    }
}
