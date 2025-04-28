<?php

namespace App\Filament\Pages;

use App\Models\Task;
use App\Models\Project;
use Carbon\Carbon;
use DateTime;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermission;
use Auth;

class TaskTimeline extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Timeline';
    protected static ?string $title = 'Task Timeline';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.task-timeline';
    protected static ?string $navigationGroup = 'Project Visualization';

    public ?string $projectId = null;

    public Collection $projects;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            $this->projects = Project::all();
        } else {
            $this->projects = $user->projects;
        }

        if ($this->projects->isNotEmpty() && !$this->projectId) {
            $this->projectId = $this->projects->first()->id;
        }
    }

    public function getTasksProperty(): Collection
    {
        $query = Task::query()
            ->with(['status', 'project'])
            ->whereNotNull('due_date')
            ->orderBy('due_date');

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        } else {
            $projectIds = $this->projects->pluck('id')->toArray();
            $query->whereIn('project_id', $projectIds);
        }

        return $query->get();
    }

    public function getMonthHeaders(): array
    {
        $tasks = $this->tasks;

        if ($tasks->isEmpty()) {
            $months = [];
            $current = Carbon::now()->subMonths(3)->startOfMonth();
            for ($i = 0; $i < 6; $i++) {
                $months[] = $current->format('M Y');
                $current->addMonth();
            }
            return $months;
        }

        $earliestDate = null;
        $latestDate = null;

        foreach ($tasks as $task) {
            if ($task->due_date) {
                $createdAt = $task->created_at ?? Carbon::parse($task->due_date)->subDays(14);
                $dueDate = Carbon::parse($task->due_date);

                if ($earliestDate === null || $createdAt < $earliestDate) {
                    $earliestDate = $createdAt;
                }

                if ($latestDate === null || $dueDate > $latestDate) {
                    $latestDate = $dueDate;
                }
            }
        }

        if ($earliestDate === null || $latestDate === null) {
            return ['Jan 2025', 'Feb 2025', 'Mar 2025', 'Apr 2025'];
        }

        $earliestDate = $earliestDate->startOfMonth();
        $latestDate = $latestDate->endOfMonth();

        $months = [];
        $current = clone $earliestDate;

        while ($current <= $latestDate) {
            $months[] = $current->format('M Y');
            $current->addMonth();
        }

        return $months;
    }

    public function getTimelineData(): array
    {
        $tasks = $this->tasks;

        if ($tasks->isEmpty()) {
            return [
                'tasks' => [],
            ];
        }

        $monthHeaders = $this->getMonthHeaders();
        $monthRanges = $this->getMonthDateRanges($monthHeaders);

        $taskData = [];
        $now = Carbon::now();

        foreach ($tasks as $index => $task) {
            if (!$task->due_date) {
                continue;
            }

            $startDate = $task->created_at ? Carbon::parse($task->created_at) : Carbon::parse($task->due_date)->subDays(14);
            $endDate = Carbon::parse($task->due_date);

            $hue = ($index * 137) % 360;
            $color = "hsl({$hue}, 70%, 50%)";

            $remainingDays = $now->diffInDays($endDate, false);

            $barSpans = [];

            foreach ($monthRanges as $monthIndex => $monthRange) {
                $monthStart = $monthRange['start'];
                $monthEnd = $monthRange['end'];
                $daysInMonth = $monthStart->daysInMonth;

                if ($startDate <= $monthEnd && $endDate >= $monthStart) {
                    $startPosition = 0;
                    if ($startDate > $monthStart) {
                        $daysFromMonthStart = $monthStart->diffInDays($startDate);
                        $startPosition = ($daysFromMonthStart / $daysInMonth) * 100;
                    }

                    $endPosition = 100;
                    if ($endDate < $monthEnd) {
                        $daysFromMonthStart = $monthStart->diffInDays($endDate);
                        $endPosition = (($daysFromMonthStart + 1) / $daysInMonth) * 100;
                    }

                    $widthPercentage = $endPosition - $startPosition;

                    $barSpans[$monthIndex] = [
                        'start_position' => $startPosition,
                        'width_percentage' => $widthPercentage
                    ];
                }
            }

            $status = strtolower($task->status->name ?? 'default');
            $statusLabel = ucfirst($status);
            $isOverdue = $endDate < $now && !in_array($status, ['completed', 'done', 'closed', 'resolved']);

            $remainingDaysText = '';
            if ($remainingDays > 0) {
                $remainingDaysText = "{$remainingDays} days left";
            } elseif ($remainingDays === 0) {
                $remainingDaysText = "Due today";
            } else {
                $remainingDaysText = abs($remainingDays) . " days overdue";
            }

            $taskData[] = [
                'id' => $task->id,
                'title' => $task->name,
                'task_id' => $task->uuid,
                'color' => $color,
                'bar_spans' => $barSpans,
                'start_date' => $startDate->format('M j'),
                'end_date' => $endDate->format('M j'),
                'remaining_days' => $remainingDays,
                'remaining_days_text' => $remainingDaysText,
                'status' => $status,
                'status_label' => $statusLabel,
                'is_overdue' => $isOverdue
            ];
        }

        usort($taskData, function($a, $b) {
            if ($a['is_overdue'] && !$b['is_overdue']) return -1;
            if (!$a['is_overdue'] && $b['is_overdue']) return 1;

            return $a['remaining_days'] <=> $b['remaining_days'];
        });

        return [
            'tasks' => $taskData,
        ];
    }

    private function getMonthDateRanges(array $monthHeaders): array
    {
        $ranges = [];

        foreach ($monthHeaders as $index => $monthHeader) {
            $date = Carbon::createFromFormat('M Y', $monthHeader);
            $ranges[$index] = [
                'start' => (clone $date)->startOfMonth(),
                'end' => (clone $date)->endOfMonth(),
            ];
        }

        return $ranges;
    }
}
