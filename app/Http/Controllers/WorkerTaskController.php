<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderReportRequest;
use App\Models\Order;
use App\Models\OrderReport;
use App\Models\OrderReportPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkerTaskController extends Controller
{
    public function tasks(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'assigned', 'in_progress', 'done', 'cancelled'])],
            'type' => ['nullable', Rule::in(['included', 'extra'])],
        ]);

        $workerId = $request->user()->id;

        $query = Order::query()
            ->with([
                'client',
                'photos',
                'reports' => fn ($q) => $q->where('worker_id', $workerId)->with('photos'),
            ])
            ->where('worker_id', $workerId)
            ->orderByDesc('created_at');

        if ($status = $data['status'] ?? null) {
            $query->where('status', $status);
        } else {
            $query->whereNotIn('status', ['done', 'cancelled']);
        }

        if ($type = $data['type'] ?? null) {
            $query->where('payment_type', $type);
        }

        return response()->json($query->get());
    }

    public function reports(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['nullable', Rule::in(['included', 'extra'])],
        ]);

        $query = OrderReport::with(['order.client', 'photos'])
            ->where('worker_id', $request->user()->id)
            ->whereDate('report_date', $data['date'])
            ->orderBy('created_at');

        if ($type = $data['type'] ?? null) {
            $query->where('work_type', $type);
        }

        $reports = $query->get();

        return response()->json($reports);
    }

    public function storeReport(StoreOrderReportRequest $request, Order $order): JsonResponse
    {
        $worker = $request->user();
        $reportDate = $request->date('report_date');
        $workType = $order->payment_type === 'included' ? 'included' : 'extra';

        $report = DB::transaction(function () use ($request, $order, $worker, $reportDate) {
            $report = OrderReport::updateOrCreate(
                ['order_id' => $order->id, 'report_date' => $reportDate],
                [
                    'worker_id' => $worker->id,
                    'work_type' => $order->payment_type === 'included' ? 'included' : 'extra',
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

            if (in_array($order->status, ['pending', 'assigned'], true)) {
                $order->update(['status' => 'in_progress']);
            }

            return $report->load('photos');
        });

        return response()->json($report);
    }

}
