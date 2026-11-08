<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AppOpened
{
    use Dispatchable;

    public function __construct(public string $section)
    {
    }
}
