<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderReportRequest;
use App\Models\Order;
use App\Models\OrderReport;
use App\Models\OrderReportPhoto;
use App\Models\OrderSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkerTaskController extends Controller
{
    public function tasks(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $workerId = $request->user()->id;

        $tasks = OrderSchedule::query()
            ->with([
                'order.client',
                'order.photos',
                'order.reports' => function ($query) use ($data, $workerId) {
                    $query->where('report_date', $data['date'])
                          ->where('worker_id', $workerId)
                          ->with('photos');
                },
            ])
            ->where('worker_id', $workerId)
            ->whereDate('scheduled_for', $data['date'])
            ->orderBy('scheduled_for')
            ->get();

        return response()->json($tasks);
    }

    public function reports(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $reports = OrderReport::with(['order.client', 'photos'])
            ->where('worker_id', $request->user()->id)
            ->whereDate('report_date', $data['date'])
            ->orderBy('created_at')
            ->get();

        return response()->json($reports);
    }

    public function storeReport(StoreOrderReportRequest $request, Order $order): JsonResponse
    {
        $worker = $request->user();
        $reportDate = $request->date('report_date');

        $schedule = OrderSchedule::where('order_id', $order->id)
            ->where('worker_id', $worker->id)
            ->whereDate('scheduled_for', $reportDate)
            ->first();

        if (!$schedule) {
            return response()->json([
                'message' => 'Задача на указанную дату отсутствует в графике.',
            ], 422);
        }

        $report = DB::transaction(function () use ($request, $order, $worker, $reportDate, $schedule) {
            $report = OrderReport::updateOrCreate(
                ['order_id' => $order->id, 'report_date' => $reportDate],
                [
                    'worker_id' => $worker->id,
                    'comment' => $request->input('comment'),
                    'completed_at' => now(),
                ]
            );

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $path = $file->store("order-reports/{$report->id}", 'public');
                    OrderReportPhoto::create([
                        'order_report_id' => $report->id,
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            $schedule->update(['status' => 'done']);

            if ($order->status !== 'done') {
                $nextStatus = $order->status === 'pending' ? 'in_progress' : $order->status;
                $order->update(['status' => $nextStatus]);
            }

            return $report->load('photos');
        });

        return response()->json($report);
    }
}
