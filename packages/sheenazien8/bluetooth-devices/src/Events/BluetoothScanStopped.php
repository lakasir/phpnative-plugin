<?php

namespace Sheenazien8\BluetoothDevices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BluetoothScanStopped
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $reason = 'manual'
    ) {}
}
