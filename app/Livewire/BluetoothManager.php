<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

use function Native\Mobile\nativephp_call;

class BluetoothManager extends Component
{
    public $devices = [];

    public $connectionStatus = 'Idle';

    public function start()
    {
        $this->devices = [];
        nativephp_call('Bluetooth.StartScan');
    }

    #[On('native:bluetooth.device_found')]
    public function onDeviceFound($device)
    {
        if (! collect($this->devices)->contains('address', $device['address'])) {
            $this->devices[] = $device;
        }
    }

    public function connect($address)
    {
        $this->connectionStatus = 'Connecting...';
        nativephp_call('Bluetooth.StopScan'); // Stop scan to save battery
        nativephp_call('Bluetooth.Connect', ['address' => $address]);
    }

    #[On('native:bluetooth.state_changed')]
    public function onStateChanged($payload)
    {
        $this->connectionStatus = ucfirst($payload['state']);
    }

    public function disconnect()
    {
        nativephp_call('Bluetooth.Disconnect');
    }

    public function render()
    {
        return view('livewire.bluetooth-manager');
    }
}
