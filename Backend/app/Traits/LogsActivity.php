<?php

namespace App\Traits;

use illuminate\Support\Facades\Log;

trait LogsActivity
{
    public function LogActivity(string $message, string $level = 'info')
    {
        $class = static::class;
        Log::{$level}("[$class] $message");
    }
}
