<?php

namespace App\Services;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AdminLogService
{
    public function log(User $admin, string $action, ?Model $target = null, ?string $description = null, array $metadata = []): AdminLog
    {
        return AdminLog::query()->create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'description' => $description,
            'ip_address' => Request::ip(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
