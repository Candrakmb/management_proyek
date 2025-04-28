<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Tasks';
    protected static ?string $navigationGroup = 'Project Management';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->user()->hasRole(['super_admin'])) {
            $query->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhereHas('project.members', function ($query) {
                        $query->where('users.id', auth()->id());
                    });
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $projectId = request()->query('project_id') ?? request()->input('project_id');
        $statusId = request()->query('task_status_id') ?? request()->input('task_status_id');

        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->label('Project')
                    ->options(function () {
                        if (auth()->user()->hasRole(['super_admin'])) {
                            return Project::pluck('name', 'id')->toArray();
                        }

                        return auth()->user()->projects()->pluck('name', 'projects.id')->toArray();
                    })
                    ->default($projectId)
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (callable $set) {
                        $set('task_status_id', null);
                        $set('user_id', null);
                    }),

                Forms\Components\Select::make('task_status_id')
                    ->label('Status')
                    ->options(function ($get) {
                        $projectId = $get('project_id');
                        if (!$projectId) return [];

                        return TaskStatus::where('project_id', $projectId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->default($statusId)
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('name')
                    ->label('Task Name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\RichEditor::make('description')
                    ->label('Description')
                    ->fileAttachmentsDirectory('attachments')
                    ->columnSpanFull(),

                Forms\Components\Select::make('user_id')
                    ->label('Assignee')
                    ->options(function ($get) {
                        $projectId = $get('project_id');
                        if (!$projectId) return [];

                        $project = Project::find($projectId);
                        if (!$project) return [];
                        return $project->members()
                            ->select('users.id', 'users.name')
                            ->pluck('users.name', 'users.id')
                            ->toArray();
                    })
                    ->default(function () {
                        return auth()->id();
                    })
                    ->required()
                    ->helperText('Only project members can be assigned to tasks'),

                Forms\Components\DatePicker::make('due_date')
                    ->label('Due Date')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Task ID')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => match ($record->status?->name) {
                        'To Do' => 'warning',
                        'In Progress' => 'info',
                        'Review' => 'primary',
                        'Done' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Project')
                    ->options(function () {
                        if (auth()->user()->hasRole(['super_admin'])) {
                            return Project::pluck('name', 'id')->toArray();
                        }

                        return auth()->user()->projects()->pluck('name', 'projects.id')->toArray();
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->relationship('status', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Assignee')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('due_date')
                    ->form([
                        Forms\Components\DatePicker::make('due_from'),
                        Forms\Components\DatePicker::make('due_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(auth()->user()->hasRole(['super_admin'])),
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('task_status_id')
                                ->label('Status')
                                ->options(function () {
                                    $firstTask = Task::find(request('records')[0] ?? null);
                                    if (!$firstTask) return [];

                                    return TaskStatus::where('project_id', $firstTask->project_id)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'task_status_id' => $data['task_status_id'],
                                ]);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
            'view' => Pages\ViewTask::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getEloquentQuery();
        return $query->count();
    }
}
