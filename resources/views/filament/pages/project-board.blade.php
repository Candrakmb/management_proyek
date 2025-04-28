<x-filament-panels::page>

    {{-- Project Selector --}}
    <div class="mb-6">
        <x-filament::section>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ $selectedProject ? $selectedProject->name : 'Pilih Project' }}
                </h2>

                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input.select
                            wire:model.live="selectedProject"
                            wire:change="selectProject($event.target.value)"
                        >
                            <option value="">Pilih Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ $selectedProject && $selectedProject->id == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>
    </div>

    @if($selectedProject)
        <div
            x-data="dragDropHandler()"
            x-init="init()"
            @task-moved.window="init()"
            @task-updated.window="init()"
            @refresh-board.window="init()"
            class="overflow-x-auto pb-6"
            id="board-container"
        >
            <div class="flex gap-4 min-w-full">
                @foreach ($taskStatuses as $status)
                    <div
                        class="status-column flex-1 min-w-[300px] rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-900"
                        data-status-id="{{ $status->id }}"
                    >
                        <div class="px-4 py-3 rounded-t-xl bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-medium text-gray-900 dark:text-white flex items-center justify-between">
                                <span>{{ $status->name }}</span>
                                <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $status->tasks->count() }}</span>
                            </h3>
                        </div>

                        <div class="p-3 flex flex-col gap-3 h-[calc(100vh-20rem)] overflow-y-auto">
                            @foreach ($status->tasks as $task)
                                <div
                                    class="task-card bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-move"
                                    data-task-id="{{ $task->id }}"
                                >
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400 px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">
                                            {{ $task->uuid }}
                                        </span>
                                        @if ($task->due_date)
                                            <span class="text-xs px-1.5 py-0.5 rounded {{ $task->due_date->isPast() ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' }}">
                                                {{ $task->due_date->format('M d') }}
                                            </span>
                                        @endif
                                    </div>

                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ $task->name }}</h4>

                                    @if ($task->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($task->description), 100) }}
                                        </p>
                                    @endif

                                    <div class="flex justify-between items-center mt-2">
                                        @if ($task->assignee)
                                            <div class="inline-flex items-center px-2 py-1 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 gap-2">
                                                <span class="w-4 h-4 rounded-full bg-primary-500 flex items-center justify-center text-xs text-white mr-1.5">
                                                    {{ substr($task->assignee->name, 0, 1) }}
                                                </span>
                                                @if ($task->assignee && $task->assignee->id == auth()->id())
                                                    <span class="text-xs font-medium">Anda</span>
                                                @else
                                                    <span class="text-xs font-medium">{{ $task->assignee->name }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 dark:text-gray-500 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="text-xs font-medium">Unassigned</span>
                                            </div>
                                        @endif

                                        <button
                                            type="button"
                                            wire:click="showTaskDetails({{ $task->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium rounded-lg border border-gray-200 dark:border-gray-700 text-primary-600 hover:text-primary-500 dark:text-primary-500 dark:hover:text-primary-400"
                                        >
                                            <x-heroicon-m-eye class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            @if ($status->tasks->isEmpty())
                                <div class="flex items-center justify-center h-24 text-gray-500 dark:text-gray-400 text-sm italic border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                                    No tasks
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($taskStatuses->isEmpty())
                    <div class="w-full flex items-center justify-center h-40 text-gray-500 dark:text-gray-400">
                        No status columns found for this project
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="flex items-center justify-center h-40 text-gray-500 dark:text-gray-400">
            Please select a project to view the board
        </div>
    @endif


    {{-- Drag and Drop Handler Script --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dragDropHandler', () => ({
                draggingTask: null,

                init() {
                    this.$nextTick(() => {
                        this.removeAllEventListeners();
                        this.attachAllEventListeners();
                    });
                },

                removeAllEventListeners() {
                    const tasks = document.querySelectorAll('.task-card');
                    tasks.forEach(task => {
                        task.removeAttribute('draggable');
                        const newTask = task.cloneNode(true);
                        task.parentNode.replaceChild(newTask, task);
                    });

                    const columns = document.querySelectorAll('.status-column');
                    columns.forEach(column => {
                        const newColumn = column.cloneNode(false);

                        while (column.firstChild) {
                            newColumn.appendChild(column.firstChild);
                        }

                        if (column.parentNode) {
                            column.parentNode.replaceChild(newColumn, column);
                        }
                    });
                },

                attachAllEventListeners() {
                    const tasks = document.querySelectorAll('.task-card');
                    tasks.forEach(task => {
                        task.setAttribute('draggable', true);

                        task.addEventListener('dragstart', (e) => {
                            this.draggingTask = task.getAttribute('data-task-id');
                            task.classList.add('opacity-50');
                            e.dataTransfer.effectAllowed = 'move';
                        });

                        task.addEventListener('dragend', () => {
                            task.classList.remove('opacity-50');
                            this.draggingTask = null;
                        });

                        const detailsButton = task.querySelector('button');
                        if (detailsButton) {
                            const taskId = task.getAttribute('data-task-id');
                            detailsButton.addEventListener('click', () => {
                                const componentId = document.querySelector('[wire\\:id]').getAttribute('wire:id');
                                if (componentId) {
                                    Livewire.find(componentId).showTaskDetails(taskId);
                                }
                            });
                        }
                    });

                    const columns = document.querySelectorAll('.status-column');
                    columns.forEach(column => {
                        column.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            e.dataTransfer.dropEffect = 'move';
                            column.classList.add('bg-primary-50', 'dark:bg-primary-950');
                        });

                        column.addEventListener('dragleave', () => {
                            column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                        });

                        column.addEventListener('drop', (e) => {
                            e.preventDefault();
                            column.classList.remove('bg-primary-50', 'dark:bg-primary-950');

                            if (this.draggingTask) {
                                const statusId = column.getAttribute('data-status-id');
                                const taskId = this.draggingTask;
                                this.draggingTask = null;

                                const componentId = document.querySelector('[wire\\:id]').getAttribute('wire:id');
                                if (componentId) {
                                    Livewire.find(componentId).moveTask(
                                        parseInt(taskId),
                                        parseInt(statusId)
                                    );
                                }
                            }
                        });
                    });
                }
            }));
        });
    </script>
</x-filament-panels::page>
