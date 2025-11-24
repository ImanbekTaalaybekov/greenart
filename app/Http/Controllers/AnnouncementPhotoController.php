<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementPhoto;
use Illuminate\Http\JsonResponse;

class AnnouncementPhotoController extends Controller
{
    public function destroy(AnnouncementPhoto $announcementPhoto): JsonResponse
    {
        $announcementPhoto->delete();

        return response()->json([]);
    }
}
