<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(
    name: 'app:create-verify-profile',
    description: 'Створює новий Verify Profile у Telnyx',
)]
class CreateVerifyProfileCommand extends Command
{
    public function __construct(
        private string $telnyx_api_key,
        private string $telnyx_message_template_id,
        private string $projectDir, // %kernel.project_dir%
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Перевіряємо, чи є ключі
        if (!$this->telnyx_api_key) {
            $io->error('TELNYX_API_KEY не знайдений у secrets/env.');
            return Command::FAILURE;
        }

        if (!$this->telnyx_message_template_id) {
            $io->error('TELNYX_MESSAGE_TEMPLATE_ID не знайдений у secrets/env.');
            return Command::FAILURE;
        }

        // 2. Формуємо payload
        $payload = [
            "name" => "Buch-SK",
            "sms" => [
                "messaging_template_id" => $this->telnyx_message_template_id,
                "app_name" => "BuchSK",                             // обов'язково
                "alpha_sender" => "BuchSK",                         // або свій бренд/назву
                "code_length" => 5,
                "whitelisted_destinations" => ["SK", "CZ", "AT", "DE"],
                "default_verification_timeout_secs" => 300
            ],
            "language" => "en-US"                                   // залишаємо без "sk-SK", бо воно не підтримується
        ];


        $client = HttpClient::create();

        try {
            // 3. Відправляємо POST на Telnyx
            $response = $client->request('POST', 'https://api.telnyx.com/v2/verify_profiles', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->telnyx_api_key,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $result = $response->toArray(false);
            $verifyProfileId = $result['data']['id'] ?? null;

            if ($verifyProfileId) {
                $io->success('✅ Verify Profile створено успішно!');
                $io->writeln('ID: ' . $verifyProfileId);

                // 4. Оновлюємо/додаємо у .env TELNYX_VERIFY_PROFILE_ID
                $envFile = $this->projectDir . '/.env';
                $env = file_exists($envFile) ? file_get_contents($envFile) : '';

                if (preg_match('/^TELNYX_VERIFY_PROFILE_ID=.*$/m', $env)) {
                    $env = preg_replace(
                        '/^TELNYX_VERIFY_PROFILE_ID=.*$/m',
                        'TELNYX_VERIFY_PROFILE_ID=' . $verifyProfileId,
                        $env
                    );
                } else {
                    $env .= "\nTELNYX_VERIFY_PROFILE_ID=" . $verifyProfileId . "\n";
                }

                file_put_contents($envFile, $env);
                $io->writeln('ℹ️ ID оновлено у .env');
            } else {
                $io->warning('⚠️ Не вдалося отримати ID профілю');
                $io->writeln(print_r($result, true));
            }
        } catch (\Throwable $e) {
            $io->error('Помилка при виклику API: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
