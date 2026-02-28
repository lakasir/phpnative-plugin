<?php

namespace Sheenazien8\BluetoothDevices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BluetoothDeviceFound
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $name,
        public string $address
    ) {}
}
