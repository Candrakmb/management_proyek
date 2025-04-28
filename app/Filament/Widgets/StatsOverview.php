<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class StatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Total Projects
        $totalProjects = Project::count();

        // Total Tasks
        $totalTasks = Task::count();

        // Tasks created in the last 7 days
        $newTasksLastWeek = Task::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        // Users count
        $usersCount = User::count();

        // Tasks without assignee
        $unassignedTasks = Task::whereNull('user_id')->count();

        return [
            Stat::make('Total Projects', $totalProjects)
                ->description('Active projects in the system')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make('Total Tasks', $totalTasks)
                ->description('Tasks across all projects')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('success'),


            Stat::make('New Tasks This Week', $newTasksLastWeek)
                ->description('Created in the last 7 days')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),


            Stat::make('Unassigned Tasks', $unassignedTasks)
                ->description('Tasks without an assignee')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($unassignedTasks > 0 ? 'danger' : 'success'),

            Stat::make('Team Members', $usersCount)
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),
        ];
    }
}
