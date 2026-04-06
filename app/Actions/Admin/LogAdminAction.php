<?php

namespace App\Actions\Admin;

use App\Models\AdminActionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class LogAdminAction
{
    public static function handle(
        Request $request,
        string $action,
        Model $subject,
        ?string $reason = null,
        array $metadata = [],
    ): void {
        AdminActionLog::create([
            'admin_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent() ?? '',
            'reason' => $reason,
            'metadata' => $metadata ?: null,
        ]);
    }
}
