## sheenazien8/bluetooth-devices

A plugin that can fetch bluetooth devices for you nativephp

### Installation

```bash
composer require sheenazien8/bluetooth-devices
```

### PHP Usage (Livewire/Blade)

Use the `BluetoothDevices` facade:

@verbatim
<code-snippet name="Using BluetoothDevices Facade" lang="php">
use Sheenazien8\BluetoothDevices\Facades\BluetoothDevices;

// Check permissions
BluetoothDevices::checkPermissions();

// Request permissions
BluetoothDevices::requestPermissions();

// Start scanning for devices
BluetoothDevices::startScan();

// Stop scanning
BluetoothDevices::stopScan();

// Connect to a device
BluetoothDevices::connect('00:11:22:33:44:55');

// Disconnect from device
BluetoothDevices::disconnect();
</code-snippet>
@endverbatim

### Available Methods

- `BluetoothDevices::checkPermissions()`: Check Bluetooth permissions
- `BluetoothDevices::requestPermissions()`: Request Bluetooth permissions
- `BluetoothDevices::startScan()`: Start scanning for BLE devices
- `BluetoothDevices::stopScan()`: Stop scanning
- `BluetoothDevices::connect(string $address)`: Connect to a device
- `BluetoothDevices::disconnect()`: Disconnect from current device
- `BluetoothDevices::execute()`: Execute the plugin functionality
- `BluetoothDevices::getStatus()`: Get the current status

### Events

All events use `#[OnNative]` attribute with full event class namespace:

- `BluetoothDeviceFound`: Dispatched when a device is found during scan
- `BluetoothStateChanged`: Dispatched on connection state changes
- `BluetoothScanError`: Dispatched on scan errors
- `BluetoothPermissionsChecked`: Dispatched after permission check results
- `BluetoothDevicesCompleted`: General completion event
- `BluetoothDevicesReqPermissionsCompleted`: Permission request completed

@verbatim
<code-snippet name="Listening for Bluetooth Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Sheenazien8\BluetoothDevices\Events\BluetoothDeviceFound;
use Sheenazien8\BluetoothDevices\Events\BluetoothStateChanged;
use Sheenazien8\BluetoothDevices\Events\BluetoothScanError;
use Sheenazien8\BluetoothDevices\Events\BluetoothPermissionsChecked;

#[OnNative(BluetoothDeviceFound::class)]
public function onDeviceFound(BluetoothDeviceFound $event): void
{
    // $event->name - Device name
    // $event->address - Device MAC address
}

#[OnNative(BluetoothStateChanged::class)]
public function onStateChanged(BluetoothStateChanged $event): void
{
    // $event->address - Device address
    // $event->state - 'connected' or 'disconnected'
}

#[OnNative(BluetoothScanError::class)]
public function onScanError(BluetoothScanError $event): void
{
    // $event->errorCode - Error code
    // $event->message - Error message
}

#[OnNative(BluetoothPermissionsChecked::class)]
public function onPermissionsChecked(BluetoothPermissionsChecked $event): void
{
    // $event->hasPermissions - bool
    // $event->bluetoothEnabled - bool
    // $event->missingPermissions - array
    // $event->androidVersion - int
}
</code-snippet>
@endverbatim

### JavaScript Usage (Vue/React/Inertia)

@verbatim
<code-snippet name="Using BluetoothDevices in JavaScript" lang="javascript">
import { bluetoothDevices } from '@sheenazien8/bluetooth-devices';

// Execute the plugin functionality
const result = await bluetoothDevices.execute({ option1: 'value' });

// Get the current status
const status = await bluetoothDevices.getStatus();
</code-snippet>
@endverbatim