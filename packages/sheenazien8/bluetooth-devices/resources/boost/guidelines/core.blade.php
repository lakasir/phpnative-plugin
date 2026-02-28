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

// Execute the plugin functionality
$result = BluetoothDevices::execute(['option1' => 'value']);

// Get the current status
$status = BluetoothDevices::getStatus();
</code-snippet>
@endverbatim

### Available Methods

- `BluetoothDevices::execute()`: Execute the plugin functionality
- `BluetoothDevices::getStatus()`: Get the current status

### Events

- `BluetoothDevicesCompleted`: Listen with `#[OnNative(BluetoothDevicesCompleted::class)]`

@verbatim
<code-snippet name="Listening for BluetoothDevices Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Sheenazien8\BluetoothDevices\Events\BluetoothDevicesCompleted;

#[OnNative(BluetoothDevicesCompleted::class)]
public function handleBluetoothDevicesCompleted($result, $id = null)
{
    // Handle the event
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