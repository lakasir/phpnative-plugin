<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('BluetoothDevices Manager')]
class BluetoothManager extends Component
{
    public $devices = [];

    public bool $isScanning = false;

    public $connectionStatus = 'Idle';

    public function start()
    {
        $this->isScanning = true;
        $this->devices = [];
        nativephp_call('BluetoothDevices.StartScan', json_encode([]));
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
        nativephp_call('BluetoothDevices.StopScan'); // Stop scan to save battery
        nativephp_call('BluetoothDevices.Connect', json_encode(['address' => $address]));
    }

    #[On('native:bluetooth.state_changed')]
    public function onStateChanged($payload)
    {
        $this->connectionStatus = ucfirst($payload['state']);
    }

    public function disconnect()
    {
        nativephp_call('BluetoothDevices.Disconnect', json_encode([]));
    }

    public function render()
    {
        return view('livewire.bluetooth-manager');
    }
}
