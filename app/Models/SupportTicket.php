<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function subjectLabels(): array
    {
        return [
            'general' => 'General',
            'account' => 'Account',
            'deposits' => 'Deposits',
            'withdrawals' => 'Withdrawals',
            'technical' => 'Technical',
        ];
    }

    public function getSubjectLabelAttribute(): string
    {
        return static::subjectLabels()[$this->subject] ?? ucfirst($this->subject);
    }
}
