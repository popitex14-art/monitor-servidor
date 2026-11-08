<?php

namespace App\Http\Controllers;

use App\Services\ServerMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(private readonly ServerMetrics $metrics)
    {
    }

    public function index(): View
    {
        return view('servidor', [
            'metrics' => $this->metrics->snapshot(),
        ]);
    }

    public function metrics(): JsonResponse
    {
        return response()->json($this->metrics->snapshot());
    }

    public function charts(): View
    {
        return view('graficos', [
            'metrics' => $this->metrics->snapshot(),
        ]);
    }
}
