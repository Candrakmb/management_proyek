<?php
namespace App\Filament\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class ProjectBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static string $view = 'filament.pages.project-board';

    protected static ?string $title = 'Project Board';

    protected static ?string $navigationLabel = 'Project Board';
    protected static ?string $navigationGroup = 'Project Visualization';

    protected static ?int $navigationSort = 2;

    public ?Project $selectedProject = null;
    public Collection $projects;
    public Collection $taskStatuses;
    public ?Task $selectedTask = null;

    public function mount(): void
    {
        if (auth()->user()->hasRole(['super_admin'])) {
            $this->projects = Project::all();
        } else {
            $this->projects = auth()->user()->projects;
        }

        if ($this->projects->isNotEmpty()) {
            $this->selectProject($this->projects->first()->id);
        }
    }

    public function selectProject(int $projectId): void
    {
        $this->selectedTask = null;
        $this->taskStatuses = collect();

        $this->selectedProject = Project::find($projectId);

        $this->loadTaskStatuses();
    }

    public function loadTaskStatuses(): void
    {
        if (!$this->selectedProject) {
            $this->taskStatuses = collect();
            return;
        }

        $this->taskStatuses = $this->selectedProject->taskStatuses()
            ->with(['tasks' => function($query) {
                $query->with(['assignee', 'status'])
                     ->orderBy('created_at', 'desc');
            }])
            ->orderBy('id')
            ->get();
    }

    public function moveTask($taskId, $newStatusId): void
    {
        $task = Task::find($taskId);

        // Pastikan task ditemukan dan milik proyek yang dipilih
        if ($task && $task->project_id === $this->selectedProject?->id ) {

            // Tambahkan pengecekan agar hanya task yang dimiliki pengguna yang dapat dipindahkan
            if ($task->user_id !== auth()->id() && !auth()->user()->hasRole(['super_admin'])) {
                $this->loadTaskStatuses();
                $this->dispatch('task-updated');
                Notification::make()
                    ->title('Permission Denied')
                    ->body('You do not have permission to move this task.')
                    ->danger()
                    ->send();
                return;
            }

            // Update status task jika user memiliki izin
            $task->update([
                'task_status_id' => $newStatusId
            ]);

            // Memuat ulang status task
            $this->loadTaskStatuses();

            // Kirimkan event dan notifikasi
            $this->dispatch('task-updated');

            Notification::make()
                ->title('Task Berhasil Dipindahkan')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Task Not Found or Invalid Project')
                ->danger()
                ->send();
        }
    }


    #[On('refresh-board')]
    public function refreshBoard(): void
    {
        $this->loadTaskStatuses();
        $this->dispatch('task-updated');
    }

    public function showTaskDetails(int $taskId): void
    {
        $task = Task::with(['assignee', 'status', 'project'])->find($taskId);

        if (!$task) {
            Notification::make()
                ->title('Task Not Found')
                ->danger()
                ->send();
            return;
        }

        $this->redirect(TaskResource::getUrl('view', ['record' => $taskId]));
    }

    public function closeTaskDetails(): void
    {
        $this->selectedTask = null;
    }

    public function editTask(int $taskId): void
    {
        $task = Task::find($taskId);

        if (!$this->canEditTask($task)) {
            Notification::make()
                ->title('Permission Denied')
                ->body('You do not have permission to edit this task.')
                ->danger()
                ->send();
            return;
        }

        $this->redirect(TaskResource::getUrl('edit', ['record' => $taskId]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_task')
                ->label('New Task')
                ->icon('heroicon-m-plus')
                ->visible(fn () => $this->selectedProject !== null && auth()->user()->hasRole(['super_admin']))
                ->url(fn (): string => TaskResource::getUrl('create', [
                    'project_id' => $this->selectedProject?->id,
                    'task_status_id' => $this->selectedProject?->taskStatuses->first()?->id
                ])),

            Action::make('refresh_board')
                ->label('Refresh Board')
                ->icon('heroicon-m-arrow-path')
                ->action('refreshBoard'),
        ];
    }

    private function canViewTask(?Task $task): bool
    {
        if (!$task) return false;

        return auth()->user()->hasRole(['super_admin'])
            || $task->user_id === auth()->id();
    }

    private function canEditTask(?Task $task): bool
    {
        if (!$task) return false;

        return auth()->user()->hasRole(['super_admin'])
            || $task->user_id === auth()->id();
    }

    private function canManageTask(?Task $task): bool
    {
        if (!$task) return false;

        return auth()->user()->hasRole(['super_admin'])
            || $task->user_id === auth()->id();
    }
}
