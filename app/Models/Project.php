<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'name',
        'description',
        'color',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('order');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('date');
    }

    public function getStats(): array
    {
        $tasks = $this->tasks;

        return [
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $tasks->where('status', TaskStatus::COMPLETED)->count(),
            'overall_progress' => $tasks->count() > 0
                ? (int) $tasks->avg('progress')
                : 0,
        ];
    }

    public function markAsCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }

    public function markAsIncomplete(): void
    {
        $this->update(['completed_at' => null]);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
