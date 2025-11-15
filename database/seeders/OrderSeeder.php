<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderReport;
use App\Models\OrderReportPhoto;
use App\Models\OrderSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::where('email', 'client@greenart.test')->first();
        $worker = User::where('email', 'worker@greenart.test')->first();

        if (!$client || !$worker) {
            $this->command?->warn('Пропущено создание заказов — отсутствуют базовые пользователи.');
            return;
        }

        $orders = [
            [
                'description' => 'Еженедельный полив клумб во дворе.',
                'payment_type' => 'included',
                'payment_money' => null,
                'status' => 'assigned',
            ],
            [
                'description' => 'Доп. обрезка кустарника у входа.',
                'payment_type' => 'extra',
                'payment_money' => 2500,
                'status' => 'pending',
            ],
        ];

        foreach ($orders as $index => $data) {
            $order = Order::create([
                'client_id' => $client->id,
                'worker_id' => $worker->id,
                'description' => $data['description'],
                'payment_type' => $data['payment_type'],
                'payment_money' => $data['payment_money'],
                'status' => $data['status'],
            ]);

            $date = now()->addDays($index)->toDateString();

            $schedule = OrderSchedule::create([
                'order_id' => $order->id,
                'worker_id' => $worker->id,
                'scheduled_for' => $date,
                'status' => $index === 0 ? 'done' : 'planned',
            ]);

            if ($index === 0) {
                $report = OrderReport::create([
                    'order_id' => $order->id,
                    'worker_id' => $worker->id,
                    'report_date' => $date,
                    'comment' => 'Полив и прополка завершены без замечаний.',
                    'completed_at' => now()->addDays($index)->setTime(15, 0),
                ]);

                $path = "order-reports/{$report->id}/sample.jpg";
                Storage::disk('public')->put($path, 'demo');

                OrderReportPhoto::create([
                    'order_report_id' => $report->id,
                    'path' => $path,
                    'original_name' => 'sample.jpg',
                    'mime_type' => 'image/jpeg',
                    'size' => 4,
                ]);
            }
        }
    }
}
