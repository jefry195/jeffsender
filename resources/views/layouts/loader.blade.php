<div id="loader-container"
    class="fixed z-[9999999] top-0 left-0 right-0 bottom-0 flex items-center flex-col justify-center bg-white dark:bg-gray-900 transition-opacity duration-500">

    {{-- Animated background gradient --}}
    <div
        class="absolute inset-0 bg-gradient-to-br from-primary-50 via-white to-primary-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 opacity-50">
    </div>

    {{-- Floating particles effect --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div
            class="absolute w-96 h-96 bg-primary-200/20 dark:bg-primary-500/10 rounded-full blur-3xl -top-48 -left-48 animate-pulse">
        </div>
        <div class="absolute w-96 h-96 bg-primary-300/20 dark:bg-primary-400/10 rounded-full blur-3xl -bottom-48 -right-48 animate-pulse"
            style="animation-delay: 1s;"></div>
    </div>

    <div class="relative z-10 flex flex-col items-center">
        {{-- Logo with subtle animation --}}
        <div class="flex justify-center items-center mb-8 transform transition-all duration-700" id="logo-container">
            <div class="relative">
                {{-- Spinning ring around logo --}}
                <div class="absolute -inset-4 border-2 border-primary-200 dark:border-primary-500/30 rounded-full animate-spin"
                    style="animation-duration: 3s;"></div>
                <div class="absolute -inset-6 border border-primary-100 dark:border-primary-500/20 rounded-full animate-spin"
                    style="animation-duration: 4s; animation-direction: reverse;"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg">
                    <img src="{{ get_option('primary_data.deep_logo') ?? get_option('primary_data.logo') }}" alt="logo"
                        class="block h-12 dark:hidden" />
                    <img src="{{ get_option('primary_data.logo') }}" alt="logo" class="hidden h-12 dark:block" />
                </div>
            </div>
        </div>

        {{-- Modern progress bar --}}
        <div class="w-80 max-w-[90vw]">
            {{-- Progress track --}}
            <div class="relative bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner h-2">
                {{-- Animated shimmer effect --}}
                <div
                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer">
                </div>

                {{-- Progress fill with gradient --}}
                <div id="loader"
                    class="h-full rounded-full bg-gradient-to-r from-primary-500 via-primary-600 to-primary-700 transition-all duration-300 ease-out relative overflow-hidden">
                    {{-- Glowing effect --}}
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer">
                    </div>
                </div>
            </div>

            {{-- Percentage text --}}
            <div class="flex justify-between items-center mt-3">
                <p id="loader-text" class="text-primary-600 dark:text-primary-400 font-semibold text-sm tabular-nums">
                    0%
                </p>
                <p class="text-gray-500 dark:text-gray-400 text-xs">
                    Loading
                </p>
            </div>
        </div>

    </div>
</div>