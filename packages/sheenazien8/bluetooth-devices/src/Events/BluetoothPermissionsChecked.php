<?php

namespace Sheenazien8\BluetoothDevices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BluetoothPermissionsChecked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public bool $hasPermissions,
        public bool $bluetoothEnabled,
        public array $missingPermissions = [],
        public int $androidVersion = 0
    ) {}
}
