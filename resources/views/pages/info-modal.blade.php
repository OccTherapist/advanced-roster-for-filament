<div class="space-y-4">
    <div class="space-y-8">
        <div class="prose dark:prose-invert">
            <h2>{{ __('advanced-roster-for-filament::help.sorting_title') }}</h2>
            <p>{{ __('advanced-roster-for-filament::help.sorting_body') }}</p>
        </div>

        <div class="prose dark:prose-invert">
            <h2>{{ __('advanced-roster-for-filament::help.entries_title') }}</h2>
            <p>{{ __('advanced-roster-for-filament::help.entries_body') }}</p>
        </div>

        <x-filament::section :heading="__('advanced-roster-for-filament::help.shortcuts_title')">
            <div class="prose dark:prose-invert">
                <h3>{{ __('advanced-roster-for-filament::help.calendar_navigation') }}</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-8">
                    <div class="flex items-center gap-2">
                        <kbd class="h-8 w-8 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">
                            <x-filament::icon icon="heroicon-s-arrow-left" class="w-3 h-3" />
                        </kbd>,
                        <kbd class="h-8 w-8 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">p</kbd>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('advanced-roster-for-filament::help.previous_week') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <kbd class="h-8 w-16 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">space</kbd>,
                        <kbd class="h-8 w-8 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">t</kbd>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('advanced-roster-for-filament::help.today') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <kbd class="h-8 w-8 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">
                            <x-filament::icon icon="heroicon-s-arrow-right" class="w-3 h-3" />
                        </kbd>,
                        <kbd class="h-8 w-8 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">n</kbd>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('advanced-roster-for-filament::help.next_week') }}</span>
                    </div>
                </div>

                <h3>{{ __('advanced-roster-for-filament::help.functions') }}</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 md:gap-8">
                    <div class="flex items-center gap-2">
                        <kbd class="h-8 w-12 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">ALT</kbd>
                        <span>+</span>
                        <kbd class="h-8 w-8 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">p</kbd>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('advanced-roster-for-filament::help.print') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <kbd class="h-8 w-12 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">ALT</kbd>
                        <span>+</span>
                        <kbd class="h-8 w-8 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">d</kbd>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('advanced-roster-for-filament::help.go_to_date') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <kbd class="h-8 w-12 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">ALT</kbd>
                        <span>+</span>
                        <kbd class="h-8 w-8 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">s</kbd>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('advanced-roster-for-filament::help.settings') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <kbd class="h-8 w-12 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">ALT</kbd>
                        <span>+</span>
                        <kbd class="h-8 w-8 inline-flex justify-center items-center text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">i</kbd>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('advanced-roster-for-filament::help.help') }}</span>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</div>
