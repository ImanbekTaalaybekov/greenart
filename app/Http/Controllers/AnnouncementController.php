<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementPhoto;
use App\Models\AnnouncementRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Announcement::class);

        $user = $request->user();

        $query = Announcement::query()
            ->with(['photos'])
            ->when($user->role !== 'admin', function ($q) use ($user) {
                $q->where(function ($w) use ($user) {
                    $w->where('audience', 'all')
                      ->when($user->role === 'client', fn($qq) => $qq->orWhere('audience', 'clients'))
                      ->when($user->role === 'worker', fn($qq) => $qq->orWhere('audience', 'workers'));
                });
            })
            ->when($request->filled('audience') && $user->role === 'admin', fn($q) => $q->where('audience', $request->string('audience')))
            ->latest('published_at')
            ->latest();

        $query->withExists(['reads as is_read' => function ($qq) use ($user) {
            $qq->where('user_id', $user->id)->whereNotNull('read_at');
        }]);

        return response()->json($query->paginate(20));
    }

    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorize('view', $announcement);
        return response()->json($announcement->load(['photos']));
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['published_at'] = $data['published_at'] ?? now();

        $announcement = DB::transaction(function () use ($data, $request) {
            $a = Announcement::create($data);

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $path = $file->store("announcements/{$a->id}", 'public');
                    AnnouncementPhoto::create([
                        'announcement_id' => $a->id,
                        'path'            => $path,
                        'original_name'   => $file->getClientOriginalName(),
                        'mime_type'       => $file->getClientMimeType(),
                        'size'            => $file->getSize(),
                    ]);
                }
            }

            return $a;
        });

        return response()->json($announcement->load('photos'), 201);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        $data = $request->validated();

        $announcement = DB::transaction(function () use ($announcement, $data, $request) {
            $announcement->update($data);

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $file) {
                    $path = $file->store("announcements/{$announcement->id}", 'public');
                    AnnouncementPhoto::create([
                        'announcement_id' => $announcement->id,
                        'path'            => $path,
                        'original_name'   => $file->getClientOriginalName(),
                        'mime_type'       => $file->getClientMimeType(),
                        'size'            => $file->getSize(),
                    ]);
                }
            }

            return $announcement->load('photos');
        });

        return response()->json($announcement);
    }

    public function destroy(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorize('delete', $announcement);
        $announcement->delete();
        return response()->json(['deleted' => true]);
    }

    public function markRead(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorize('markRead', $announcement);

        $userId = $request->user()->id;

        AnnouncementRead::updateOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $userId],
            ['read_at' => now()]
        );

        return response()->json(['ok' => true]);
    }
}
