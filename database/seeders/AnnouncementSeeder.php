<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AnnouncementPhoto;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class AnnouncementSeeder extends Seeder
{
    /**
     * Seed the announcement feed with demo data.
     */
    public function run(): void
    {
        $admin = User::query()->where('role', User::ROLE_ADMIN)->first();

        if (! $admin) {
            return;
        }

        $worker = User::query()->where('role', User::ROLE_WORKER)->first();
        $client = User::query()->where('role', User::ROLE_CLIENT)->first();

        $payloads = [
            [
                'title' => 'График весенних уходов',
                'body' => 'С 10 по 20 апреля выходим по обновлённому графику и фиксируем прогресс фотоотчётами.',
                'audience' => 'workers',
                'published_at' => Carbon::now()->subDays(2),
                'photos' => ['schedule.png'],
                'reads' => array_filter([$worker?->id]),
            ],
            [
                'title' => 'Чат поддержки клиентов',
                'body' => 'Запустили общий чат для клиентов. Он доступен в разделе «Коммуникации».',
                'audience' => 'clients',
                'published_at' => Carbon::now()->subDay(),
                'photos' => ['client-chat.png'],
                'reads' => array_filter([$client?->id]),
            ],
            [
                'title' => 'Еженедельный дайджест',
                'body' => 'Публикуем новости компании, обновления по задачам и полезные материалы для всех ролей.',
                'audience' => 'all',
                'published_at' => Carbon::now(),
                'photos' => ['digest.jpg'],
                'reads' => array_filter([$worker?->id, $client?->id]),
            ],
        ];

        foreach ($payloads as $payload) {
            $photos = $payload['photos'];
            $reads = $payload['reads'];
            unset($payload['photos'], $payload['reads']);

            $announcement = Announcement::updateOrCreate(
                ['title' => $payload['title'], 'audience' => $payload['audience']],
                array_merge($payload, ['created_by' => $admin->id])
            );

            $this->seedPhotos($announcement, $photos);
            $this->seedReads($announcement, $reads);
        }
    }

    private function seedPhotos(Announcement $announcement, array $photos): void
    {
        foreach ($photos as $fileName) {
            $relativePath = "announcements/{$announcement->id}/{$fileName}";
            $disk = Storage::disk('public');
            $directory = dirname($relativePath);

            if ($directory && $directory !== '.') {
                $disk->makeDirectory($directory);
            }

            $content = "Demo content for {$fileName} generated at ".now()->toIso8601String();
            $disk->put($relativePath, $content);

            AnnouncementPhoto::updateOrCreate(
                [
                    'announcement_id' => $announcement->id,
                    'path' => $relativePath,
                ],
                [
                    'original_name' => $fileName,
                    'mime_type' => $this->guessMimeType($fileName),
                    'size' => strlen($content),
                ]
            );
        }
    }

    private function seedReads(Announcement $announcement, array $userIds): void
    {
        foreach ($userIds as $userId) {
            AnnouncementRead::updateOrCreate(
                [
                    'announcement_id' => $announcement->id,
                    'user_id' => $userId,
                ],
                [
                    'read_at' => now(),
                ]
            );
        }
    }

    private function guessMimeType(string $fileName): string
    {
        return match (strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
