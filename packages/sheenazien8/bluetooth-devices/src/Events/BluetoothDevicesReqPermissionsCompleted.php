<?php

namespace Sheenazien8\BluetoothDevices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BluetoothDevicesReqPermissionsCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public bool $hasPermissions,
        public array $missingPermissions = [],
        public bool $bluetoothEnabled = false,
        public int $androidVersion = 0
    ) {}
}
