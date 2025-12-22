<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementPhoto;
use App\Models\User;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Announcement::query()
            ->with(['creator', 'photos'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');

        if ($user->hasRole(User::ROLE_CLIENT)) {
            $query->whereIn('audience', ['all', 'clients']);
        } elseif ($user->hasRole(User::ROLE_WORKER)) {
            $query->whereIn('audience', ['all', 'workers']);
        }

        $announcements = $query->paginate(20);

        return response()->json($announcements);
    }

    public function show(Announcement $announcement): JsonResponse
    {
        return response()->json($announcement->load(['photos']));
    }

    public function store(StoreAnnouncementRequest $request, PhotoService $photoService): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['published_at'] = $data['published_at'] ?? now();

        $announcement = Announcement::create($data);

        $photoService->apply(
            $announcement,
            $request,
            'announcements',
            AnnouncementPhoto::class,
            'announcement_id'
        );

        return response()->json($announcement->load(['photos', 'creator']), 201);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement, PhotoService $photoService): JsonResponse
    {
        $data = $request->validated();

        $announcement->update($data);

        $photoService->apply(
            $announcement,
            $request,
            'announcements',
            AnnouncementPhoto::class,
            'announcement_id'
        );

        return response()->json($announcement->load(['photos', 'creator']));
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();
        return response()->json(['deleted' => true]);
    }
}
