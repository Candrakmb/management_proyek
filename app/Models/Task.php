<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'task_status_id',
        'name',
        'description',
        'user_id',
        'due_date',
        'uuid',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($task) {
            if (empty($task->uuid)) {
                $project = Project::find($task->project_id);
                $prefix = $project ? $project->task_prefix : 'TKT';
                $randomString = Str::upper(Str::random(6));

                $task->uuid = "{$prefix}-{$randomString}";
            }
        });

        static::updating(function ($task) {
            if ($task->isDirty('task_status_id')) {
                TaskHistory::create([
                    'task_id' => $task->id,
                    'user_id' => auth()->id(),
                    'task_status_id' => $task->task_status_id,
                ]);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class)->orderBy('created_at', 'desc');
    }
}
