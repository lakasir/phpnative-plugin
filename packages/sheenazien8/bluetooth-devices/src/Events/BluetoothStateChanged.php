<?php

namespace Sheenazien8\BluetoothDevices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BluetoothStateChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $address,
        public string $state
    ) {}
}
