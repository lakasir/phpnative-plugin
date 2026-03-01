<div class="p-4 max-w-2xl mx-auto" wire:init="checkPermissions">
    <div class="flex flex-col gap-4">

        <!-- Error Message -->
        @if($errorMessage)
            <div class="p-4 rounded-lg bg-red-100 border border-red-200 text-red-700 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ $errorMessage }}</span>
                </div>
            </div>
        @endif

        <!-- Permission Request Card (shown when not ready) -->
        @if($permissionStatus !== 'Ready')
            <div class="p-6 rounded-xl bg-yellow-50 border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800 text-center">
                <svg class="w-12 h-12 mx-auto text-yellow-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Bluetooth Permissions Required</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                    This app needs permission to access Bluetooth and location services to scan for nearby devices.
                </p>
                <button wire:click="requestPermissions" wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-wait"
                    class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg shadow-sm font-medium transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Grant Permissions
                </button>
                <p class="text-xs text-gray-500 mt-3">Current status: {{ $permissionStatus }}</p>
            </div>
        @endif

        <!-- Main Content (only shown when ready) -->
        @if($permissionStatus === 'Ready')
            <div class="p-5 rounded-xl bg-white border border-gray-200 shadow-sm dark:bg-[#161615] dark:border-[#3E3E3A]">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Bluetooth Control
                    </h2>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                        Ready
                    </span>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-[#A1A09A]">Connection:</span>
                        <span class="font-semibold flex items-center gap-1.5 {{ $connectionStatus === 'Connected' ? 'text-green-600' : ($connectionStatus === 'Connecting' ? 'text-yellow-600' : 'text-blue-600') }}">
                            <span class="w-2 h-2 rounded-full {{ $connectionStatus === 'Connected' ? 'bg-green-500 animate-pulse' : ($connectionStatus === 'Connecting' ? 'bg-yellow-500' : 'bg-blue-500') }}"></span>
                            {{ $connectionStatus }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button wire:click="start" wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-wait"
                    class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg shadow-sm font-medium transition-all duration-200 flex items-center justify-center gap-2 {{ $isScanning ? 'animate-pulse' : '' }}">
                    @if ($isScanning)
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                        Scanning...
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Start Scan
                    @endif
                </button>

                <button wire:click="disconnect" wire:loading.attr="disabled"
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-lg shadow-sm font-medium transition-all duration-200 flex items-center gap-2"
                    {{ $connectionStatus === 'Idle' || $connectionStatus === 'Disconnected' ? 'disabled' : '' }}>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Disconnect
                </button>
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-700 dark:text-[#A1A09A] flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Nearby Devices
                    </h3>
                    @if(count($devices) > 0)
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full dark:bg-blue-900 dark:text-blue-300">
                            {{ count($devices) }} found
                        </span>
                    @endif
                </div>

                <div class="flex flex-col gap-2">
                    @forelse($devices as $device)
                        <div
                            class="p-4 border rounded-xl flex items-center justify-between bg-white dark:bg-[#1c1c1a] dark:border-[#3E3E3A] shadow-sm hover:shadow-md transition-shadow duration-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800 dark:text-white">{{ $device['name'] }}</div>
                                    <div class="text-xs font-mono text-gray-400">{{ $device['address'] }}</div>
                                </div>
                            </div>
                            <button wire:click="connect('{{ $device['address'] }}')" wire:loading.attr="disabled"
                                class="bg-green-500 hover:bg-green-600 text-white text-sm px-4 py-2 rounded-lg transition-all duration-200 flex items-center gap-1.5 font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                Connect
                            </button>
                        </div>
                    @empty
                        <div class="text-center py-12 bg-gray-50 dark:bg-[#1c1c1a] rounded-xl border border-dashed border-gray-300 dark:border-[#3E3E3A]">
                            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                No devices found yet
                            </p>
                            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">
                                Tap "Start Scan" to discover nearby devices
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
