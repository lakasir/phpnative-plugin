<div class="p-4">
    <div class="flex flex-col gap-4">
        <div class="p-4 rounded-lg bg-gray-100 border dark:bg-[#161615] dark:border-[#3E3E3A]">
            <h2 class="text-lg font-bold">Bluetooth Control</h2>
            <p class="text-sm">Status:
                <span class="font-semibold {{ $connectionStatus === 'Connected' ? 'text-green-600' : 'text-blue-600' }}">
                    {{ $connectionStatus }}
                </span>
            </p>
        </div>

        <div class="flex gap-2">
            <button wire:click="start"
                class="bg-blue-500 text-white px-4 py-2 rounded shadow hover:bg-blue-600 transition cursor-pointer">
                @if ($isScanning)
                    Scanning...
                @else
                    Start Scan
                @endif
            </button>
            <button wire:click="disconnect"
                class="bg-red-500 text-white px-4 py-2 rounded shadow hover:bg-red-600 transition">
                Disconnect
            </button>
        </div>

        <div class="mt-4">
            <h3 class="font-semibold mb-2 text-gray-600 dark:text-[#A1A09A]">Nearby Devices</h3>
            <div class="flex flex-col gap-2">
                @forelse($devices as $device)
                    <div
                        class="p-3 border rounded-lg flex justify-between items-center bg-white dark:bg-[#161615] dark:border-[#3E3E3A] shadow-sm">
                        <div>
                            <div class="font-bold">{{ $device['name'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-[#A1A09A]">{{ $device['address'] }}</div>
                        </div>
                        <button wire:click="connect('{{ $device['address'] }}')"
                            class="bg-green-500 text-white text-xs px-3 py-1 rounded hover:bg-green-600 transition">
                            Connect
                        </button>
                    </div>
                @empty
                    <div class="text-gray-400 italic text-sm text-center py-8">
                        No devices found yet. Tap "Start Scan".
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
