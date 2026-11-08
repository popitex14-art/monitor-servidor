<?php

namespace App\Http\Controllers;

use App\Events\AppOpened;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section' => ['required', 'string', 'in:inicio,reportes,configuracion,usuarios'],
        ]);

        AppOpened::dispatch($validated['section']);

        return response()->json([
            'ok' => true,
            'section' => $validated['section'],
        ]);
    }
}
