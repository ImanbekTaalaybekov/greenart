<?php

namespace App\Http\Controllers;

use App\Models\OrderPhoto;
use Illuminate\Http\JsonResponse;

class OrderPhotoController extends Controller
{
    public function destroy(OrderPhoto $orderPhoto): JsonResponse
    {
        $orderPhoto->delete();

        return response()->json('удалено');
    }
}
