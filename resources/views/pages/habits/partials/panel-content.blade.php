@if ($showForm)
    <div class="p-5 lg:p-8 space-y-5 lg:space-y-6 pb-28 lg:pb-8"> {{-- pb-28 penting agar form tidak tertutup menu navigasi mobile --}}
        <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
            <h2 class="font-heading font-semibold text-zinc-100 text-xl lg:text-lg">
                {{ $editingId ? 'Edit Habit' : 'New Habit' }}
            </h2>
            <button wire:click="cancel" title="Close" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-2 lg:p-1.5 rounded-md">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="space-y-5 lg:space-y-5">
            <div>
                <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Habit Name</label>
                <input type="text" wire:model="name" placeholder="e.g., Read a book"
                       class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                @error('name') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Category</label>
                <input type="text" wire:model="category" placeholder="Study / Health"
                       class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                @error('category') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Frequency</label>
                <select wire:model="frequency"
                        class="w-full border-b border-zinc-800 bg-zinc-900 text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                </select>
                @error('frequency') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Target</label>
                    <input type="number" step="0.1" wire:model="target"
                           class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                    @error('target') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Unit</label>
                    <input type="text" wire:model="unit" placeholder="hours / pages"
                           class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition placeholder:text-zinc-700">
                    @error('unit') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-[11px] lg:text-xs uppercase tracking-wider mb-1.5 text-zinc-500 font-medium">Start Date</label>
                <input type="date" wire:model="start_date"
                       class="w-full border-b border-zinc-800 bg-transparent text-zinc-100 px-0 py-2 text-base lg:text-sm focus:ring-0 focus:border-emerald-500 outline-none transition">
                @error('start_date') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-6 lg:pt-4">
                <button wire:click="save"
                        class="w-full py-3.5 lg:py-2.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-lg transition text-base lg:text-sm">
                    Save Habit
                </button>
            </div>
        </div>
    </div>

@elseif ($this->selectedHabit)
    @php $habit = $this->selectedHabit; @endphp
    <div class="p-5 lg:p-8 space-y-5 lg:space-y-6 pb-28 lg:pb-8">
        <div class="flex items-start justify-between border-b border-zinc-800 pb-4">
            <div>
                <p class="font-heading text-2xl lg:text-xl font-semibold text-zinc-100">{{ $habit->name }}</p>
                <p class="text-[11px] lg:text-xs text-emerald-500 mt-1 tracking-wide uppercase font-medium">{{ $habit->category ?: 'Uncategorized' }}</p>
            </div>
            <button wire:click="$set('selectedHabitId', null)" title="Close" class="text-zinc-400 hover:text-zinc-200 transition bg-zinc-800 hover:bg-zinc-700 p-2 lg:p-1.5 rounded-md mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:gap-4">
            <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 lg:w-6 lg:h-6 text-orange-500 mx-auto mb-1.5 lg:mb-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M12 12c2 -2.96 0 -7 -1 -8c0 3.038 -1.773 4.741 -3 6c-1.226 1.26 -2 3.24 -2 5a6 6 0 1 0 12 0c0 -1.532 -1.056 -3.94 -2 -5c-1.786 3 -2.791 3 -4 2z"></path>
                </svg>
                <p class="text-3xl lg:text-2xl font-heading font-bold text-zinc-100">{{ $this->currentStreak($habit) }}</p>
                <p class="text-[11px] lg:text-xs text-zinc-500 mt-0.5">Current Streak</p>
            </div>
            <div class="bg-zinc-800/40 rounded-xl p-4 text-center border border-zinc-700/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 lg:w-6 lg:h-6 text-blue-500 mx-auto mb-1.5 lg:mb-1" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11"></path>
                </svg>
                <p class="text-3xl lg:text-2xl font-heading font-bold text-zinc-100">{{ $this->totalCompletions($habit) }}</p>
                <p class="text-[11px] lg:text-xs text-zinc-500 mt-0.5">Total Completions</p>
            </div>
        </div>

        <div class="border-t border-zinc-800/50 pt-5 lg:pt-4">
            <div class="flex items-center justify-between mb-4 lg:mb-3">
                <button wire:click="previousMonth" class="p-2 lg:p-1 rounded-md text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 lg:w-4 lg:h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
                <p class="font-heading text-base lg:text-sm font-semibold text-zinc-200">{{ $this->calendarMonthLabel }}</p>
                <button wire:click="nextMonth" class="p-2 lg:p-1 rounded-md text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 lg:w-4 lg:h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-7 gap-y-2 lg:gap-y-1.5">
                @foreach (['S','M','T','W','T','F','S'] as $d)
                    <p class="text-[11px] lg:text-[10px] text-zinc-500 font-medium text-center">{{ $d }}</p>
                @endforeach

                @foreach ($this->calendarWeeks as $week)
                    @foreach ($week as $cell)
                        <div wire:key="cal-{{ $cell['date'] }}" class="flex justify-center mt-1">
                            <div class="w-8 h-8 lg:w-6 lg:h-6 rounded-full flex items-center justify-center text-sm lg:text-[11px]
                                        {{ ! $cell['inMonth'] ? 'text-zinc-700' : 'text-zinc-300' }}
                                        {{ $cell['completed'] ? 'bg-emerald-500 text-white font-semibold shadow-[0_0_8px_rgba(16,185,129,0.3)]' : '' }}
                                        {{ $cell['isToday'] && ! $cell['completed'] ? 'ring-1 ring-emerald-500' : '' }}">
                                {{ $cell['day'] }}
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="text-sm lg:text-sm text-zinc-300 space-y-4 pt-4 lg:pt-2">
            <div class="flex justify-between items-center border-b border-zinc-800/50 pb-2.5 lg:pb-2">
                <span class="text-zinc-500 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 lg:w-4 lg:h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    Frequency
                </span>
                <span class="font-medium text-zinc-200">{{ $habit->frequency === 'daily' ? 'Daily' : 'Weekly' }}</span>
            </div>
            <div class="flex justify-between items-center border-b border-zinc-800/50 pb-2.5 lg:pb-2">
                <span class="text-zinc-500 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 lg:w-4 lg:h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 15h2.25m8.024-9.75c.011.05.028.1.052.148.591 1.2.924 2.55.924 3.977a8.96 8.96 0 01-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398C20.613 14.547 19.833 15 19 15h-1.053c-.472 0-.745-.563-.524-.985a6.953 6.953 0 00.553-2.733v-.784z" /></svg>
                    Target
                </span>
                <span class="font-medium text-zinc-200">{{ rtrim(rtrim($habit->target, '0'), '.') }} {{ $habit->unit }}</span>
            </div>
            <div class="flex justify-between items-center border-b border-zinc-800/50 pb-2.5 lg:pb-2">
                <span class="text-zinc-500 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 lg:w-4 lg:h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" /></svg>
                    Started On
                </span>
                <span class="font-medium text-zinc-200">{{ $habit->start_date->translatedFormat('d M Y') }}</span>
            </div>
        </div>

        <div class="flex gap-3 pt-6 lg:pt-4">
            <button wire:click="edit({{ $habit->id }})"
                    class="flex-1 py-3 lg:py-2 text-base lg:text-sm font-medium bg-zinc-800 hover:bg-zinc-700 text-zinc-200 rounded-lg transition border border-zinc-700">
                Edit
            </button>
            <button wire:click="confirmDelete({{ $habit->id }})"
                    class="py-3 px-5 lg:py-2 lg:px-4 text-base lg:text-sm font-medium bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-lg transition border border-red-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 lg:w-4 lg:h-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </button>
        </div>
    </div>
@else
    <div class="flex h-full items-center justify-center p-8 text-center text-zinc-600 text-sm">
        Select a habit to view details, or click "Add" to create a new one.
    </div>
@endif