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

    public $permissionStatus = 'Initializing...';

    public $errorMessage = null;

    public function mount()
    {
        $this->permissionStatus = 'Checking permissions...';
        // Try to check permissions on mount
        try {
            $this->checkPermissions();
        } catch (\Exception $e) {
            $this->permissionStatus = 'Error checking permissions';
            $this->errorMessage = $e->getMessage();
        }
    }

    public function checkPermissions()
    {
        $this->permissionStatus = 'Checking...';

        $response = nativephp_call('BluetoothDevices.CheckPermissions', json_encode([]));
        $result = json_decode($response, true);

        if (isset($result['success']) && $result['success']) {
            $data = $result['data'] ?? [];
            $hasPermissions = $data['has_permissions'] ?? false;
            $bluetoothEnabled = $data['bluetooth_enabled'] ?? false;

            if (! $hasPermissions) {
                $this->permissionStatus = 'Permissions needed';
            } elseif (! $bluetoothEnabled) {
                $this->permissionStatus = 'Bluetooth off';
            } else {
                $this->permissionStatus = 'Ready';
            }
        } else {
            $this->permissionStatus = 'Permission check failed';
            $this->errorMessage = $result['message'] ?? 'Unknown error';
        }
    }

    public function requestPermissions()
    {
        $this->permissionStatus = 'Requesting...';
        $this->errorMessage = null;

        try {
            $response = nativephp_call('BluetoothDevices.RequestPermissions', json_encode([]));
            $result = json_decode($response, true);

            if (isset($result['success']) && $result['success']) {
                // Wait a moment then check again
                sleep(1);
                $this->checkPermissions();
            } else {
                $this->permissionStatus = 'Request failed';
                $this->errorMessage = $result['message'] ?? 'Failed to request';
            }
        } catch (\Exception $e) {
            $this->permissionStatus = 'Error requesting';
            $this->errorMessage = $e->getMessage();
        }
    }

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
        nativephp_call('BluetoothDevices.StopScan');
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
