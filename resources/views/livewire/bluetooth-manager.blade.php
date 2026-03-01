<div class="container" wire:poll.2s="checkPermissions">
    <div class="content-wrapper">

        <!-- Error Message -->
        @if($errorMessage)
            <div class="alert alert-error">
                <div class="flex items-center gap-2">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ $errorMessage }}</span>
                </div>
            </div>
        @endif

        <!-- Permission Request Card (shown when not ready) -->
        @if($permissionStatus !== 'Ready')
            <div class="permission-card">
                <svg class="permission-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <h3 class="permission-title">Bluetooth Permissions Required</h3>
                <p class="permission-text">
                    This app needs permission to access Bluetooth and location services to scan for nearby devices.
                </p>
                <button wire:click="requestPermissions" wire:loading.attr="disabled"
                    class="btn btn-warning btn-full {{ $permissionStatus === 'Requesting' ? 'btn-loading' : '' }}">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Grant Permissions
                </button>
                <p class="permission-status">Current status: {{ $permissionStatus }}</p>
            </div>
        @endif

        <!-- Main Content (only shown when ready) -->
        @if($permissionStatus === 'Ready')
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg width="20" height="20" style="color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Bluetooth Control
                    </h2>
                    <span class="status-badge status-badge-ready">Ready</span>
                </div>

                <div class="space-y-2">
                    <div class="info-row">
                        <span class="info-label">Connection:</span>
                        <span class="info-value {{ $connectionStatus === 'Connected' ? 'info-value-green' : ($connectionStatus === 'Connecting' ? 'info-value-yellow' : 'info-value-blue') }}">
                            <span class="status-dot status-dot-{{ $connectionStatus === 'Connected' ? 'green' : ($connectionStatus === 'Connecting' ? 'yellow' : 'blue') }} {{ $connectionStatus === 'Connected' ? 'status-dot-pulse' : '' }}"></span>
                            {{ $connectionStatus }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button wire:click="start" wire:loading.attr="disabled"
                    class="btn btn-primary flex-1 {{ $isScanning ? 'animate-pulse' : '' }}">
                    @if ($isScanning)
                        <svg class="animate-spin" width="20" height="20" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                        Scanning...
                    @else
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Start Scan
                    @endif
                </button>

                <button wire:click="disconnect" wire:loading.attr="disabled"
                    class="btn btn-danger"
                    {{ $connectionStatus === 'Idle' || $connectionStatus === 'Disconnected' ? 'disabled' : '' }}>
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Disconnect
                </button>
            </div>

            <div class="devices-section">
                <div class="devices-header">
                    <h3 class="devices-title">
                        <svg width="20" height="20" style="color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Nearby Devices
                    </h3>
                    @if(count($devices) > 0)
                        <span class="device-count">{{ count($devices) }} found</span>
                    @endif
                </div>

                <div class="device-list">
                    @forelse($devices as $device)
                        <div class="device-item">
                            <div class="device-info">
                                <div class="device-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="device-details">
                                    <div class="device-name">{{ $device['name'] }}</div>
                                    <div class="device-address">{{ $device['address'] }}</div>
                                </div>
                            </div>
                            <button wire:click="connect('{{ $device['address'] }}')" wire:loading.attr="disabled"
                                class="device-connect-btn">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                Connect
                            </button>
                        </div>
                    @empty
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <p class="empty-state-text">
                                No devices found yet
                            </p>
                            <p class="empty-state-subtext">
                                Tap "Start Scan" to discover nearby devices
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
