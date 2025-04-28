<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use App\Models\Task;
use App\Models\TaskComment;
use Filament\Forms;
use Filament\Actions\Action;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    public ?int $editingCommentId = null;

    protected function getHeaderActions(): array
    {
        $task = $this->getRecord();
        $project = $task->project;
        $canComment = auth()->user()->hasRole(['super_admin'])
            || $project->members()->where('users.id', auth()->id())->exists();

        return [
            Actions\EditAction::make()
                ->visible(function () {
                    $task = $this->getRecord();

                    return auth()->user()->hasRole(['super_admin'])
                        || $task->user_id === auth()->id();
                }),

            Actions\Action::make('addComment')
                ->label('Add Comment')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->form([
                    RichEditor::make('comment')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $task = $this->getRecord();

                    $task->comments()->create([
                        'user_id' => auth()->id(),
                        'comment' => $data['comment'],
                    ]);

                    Notification::make()
                        ->title('Comment added successfully')
                        ->success()
                        ->send();
                })
                ->visible($canComment),

            Actions\Action::make('back')
                ->label('Back to Board')
                ->color('gray')
                ->url(fn () => route('filament.admin.pages.project-board')),
        ];
    }


    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Group::make([
                            Section::make()
                                ->schema([
                                    TextEntry::make('uuid')
                                        ->label('Task ID')
                                        ->copyable(),

                                    TextEntry::make('name')
                                        ->label('Task Name'),

                                    TextEntry::make('project.name')
                                        ->label('Project'),
                                ])
                        ])->columnSpan(1),

                        Group::make([
                            Section::make()
                                ->schema([
                                    TextEntry::make('status.name')
                                        ->label('Status')
                                        ->badge()
                                        ->color(fn($state) => match ($state) {
                                            'To Do' => 'warning',
                                            'In Progress' => 'info',
                                            'Review' => 'primary',
                                            'Done' => 'success',
                                            default => 'gray',
                                        }),

                                    TextEntry::make('assignee.name')
                                        ->label('Assignee'),

                                    TextEntry::make('due_date')
                                        ->label('Due Date')
                                        ->date(),
                                ])
                        ])->columnSpan(1),

                        Group::make([
                            Section::make()
                                ->schema([
                                    TextEntry::make('created_at')
                                        ->label('Created At')
                                        ->dateTime(),

                                    TextEntry::make('updated_at')
                                        ->label('Updated At')
                                        ->dateTime(),
                                ])
                        ])->columnSpan(1),
                    ]),

                Section::make('Description')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Section::make('Comments')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->description('Discussion about this task')
                    ->schema([
                        TextEntry::make('comments_list')
                            ->label('Recent Comments')
                            ->state(function (Task $record) {
                                if (method_exists($record, 'comments')) {
                                    return $record->comments()->with('user')->latest()->get();
                                }
                                return collect();
                            })
                            ->view('filament.resources.task-resource.latest-comments'),
                    ])
                    ->collapsible(),

                Section::make('Status History')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('histories')
                            ->hiddenLabel()
                            ->view('filament.resources.task-resource.timeline-history')
                    ]),
            ]);
    }

}
