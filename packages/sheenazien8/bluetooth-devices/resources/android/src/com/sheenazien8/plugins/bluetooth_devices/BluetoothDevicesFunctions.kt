package com.sheenazien8.plugins.bluetooth_devices

import android.Manifest
import android.bluetooth.*
import android.bluetooth.le.ScanCallback
import android.bluetooth.le.ScanResult
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.pm.PackageManager
import android.os.Build
import android.os.Handler
import android.os.Looper
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import com.nativephp.mobile.utils.NativeActionCoordinator
import org.json.JSONObject
import android.util.Log
import android.os.Build.*
import kotlin.random.Random

object BluetoothState {
    var scannerCallback: ScanCallback? = null
    var classicReceiver: BroadcastReceiver? = null
    var activeGatt: BluetoothGatt? = null
    var scanHandler: Handler? = null
    var isScanning: Boolean = false
    var discoveredDevices: MutableSet<String> = mutableSetOf()

    const val SCAN_TIMEOUT_MS = 30000L // 30 seconds timeout
}

fun dispatchEvent(activity: FragmentActivity, eventClass: String, payload: Map<String, Any>) {
    val payloadJson = JSONObject(payload).toString()
    Handler(Looper.getMainLooper()).post {
        NativeActionCoordinator.dispatchEvent(activity, eventClass, payloadJson)
    }
}

object BluetoothDevicesFunctions {

    private fun isEmulator(): Boolean {
        // Check if it's a real emulator
        val isEmulator = (Build.FINGERPRINT.startsWith("generic") ||
                Build.FINGERPRINT.startsWith("unknown") ||
                Build.FINGERPRINT.contains("google/sdk_gphone") ||
                Build.MODEL.contains("google_sdk") ||
                Build.MODEL.contains("Emulator") ||
                Build.MODEL.contains("Android SDK built for x86") ||
                Build.MODEL.contains("sdk_gphone") ||
                Build.MANUFACTURER.contains("Genymotion") ||
                Build.HARDWARE.contains("goldfish") ||
                Build.HARDWARE.contains("ranchu") ||
                "google_sdk" == Build.PRODUCT ||
                Build.PRODUCT.contains("sdk_gphone"))
        
        // Don't flag Huawei/Honor devices as emulator
        val isHuaweiOrHonor = Build.MANUFACTURER.contains("Huawei", ignoreCase = true) || 
                              Build.MANUFACTURER.contains("HONOR", ignoreCase = true) ||
                              Build.BRAND.contains("Huawei", ignoreCase = true) ||
                              Build.BRAND.contains("HONOR", ignoreCase = true)
        
        // HarmonyOS detection
        val isHarmonyOS = try {
            Class.forName("com.huawei.hms.api.HuaweiApiClient") != null ||
            Class.forName("ohos.app.Context") != null ||
            System.getProperty("os.name")?.contains("Harmony") == true ||
            System.getProperty("ro.build.version.harmony") != null
        } catch (e: Exception) { false }
        
        if (isHuaweiOrHonor || isHarmonyOS) {
            Log.d("BluetoothBridge", "Huawei/Honor/HarmonyOS device detected (HarmonyOS=$isHarmonyOS) - using real device mode")
            return false
        }
        
        Log.d("BluetoothBridge", "Device check: MANUFACTURER=${Build.MANUFACTURER}, BRAND=${Build.BRAND}, MODEL=${Build.MODEL}, HARDWARE=${Build.HARDWARE}, isEmulator=$isEmulator, isHarmonyOS=$isHarmonyOS")
        
        return isEmulator
    }

    private fun checkBluetoothPermissions(context: Context): Pair<Boolean, List<String>> {
        val missingPermissions = mutableListOf<String>()

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            if (ContextCompat.checkSelfPermission(context, Manifest.permission.BLUETOOTH_SCAN) != PackageManager.PERMISSION_GRANTED) {
                missingPermissions.add("BLUETOOTH_SCAN")
            }
            if (ContextCompat.checkSelfPermission(context, Manifest.permission.BLUETOOTH_CONNECT) != PackageManager.PERMISSION_GRANTED) {
                missingPermissions.add("BLUETOOTH_CONNECT")
            }
        }

        if (ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            missingPermissions.add("ACCESS_FINE_LOCATION")
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            if (ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_BACKGROUND_LOCATION) != PackageManager.PERMISSION_GRANTED) {
                Log.d("BluetoothBridge", "BACKGROUND_LOCATION not granted (optional)")
            }
        }

        Log.d("BluetoothBridge", "Permission check: missing = $missingPermissions")
        return Pair(missingPermissions.isEmpty(), missingPermissions)
    }

    class StartScan(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            Log.d("BluetoothBridge", "Starting scan... Emulator: ${isEmulator()}")

            val activity = context as FragmentActivity

            // Stop any existing scan first
            stopScanInternal(context)
            BluetoothState.discoveredDevices.clear()

            val (hasPermissions, missingPermissions) = checkBluetoothPermissions(context)
            if (!hasPermissions) {
                Log.e("BluetoothBridge", "Missing required permissions: $missingPermissions")

                val permissionsToRequest = missingPermissions.map { permission ->
                    when (permission) {
                        "BLUETOOTH_SCAN" -> Manifest.permission.BLUETOOTH_SCAN
                        "BLUETOOTH_CONNECT" -> Manifest.permission.BLUETOOTH_CONNECT
                        "ACCESS_FINE_LOCATION" -> Manifest.permission.ACCESS_FINE_LOCATION
                        else -> Manifest.permission.ACCESS_FINE_LOCATION
                    }
                }.toTypedArray()

                ActivityCompat.requestPermissions(activity, permissionsToRequest, 1001)

                return BridgeResponse.error("PERMISSION_REQUIRED", "Requesting permissions: ${missingPermissions.joinToString(", ")}. Please grant permissions and try again.")
            }

            val bluetoothManager = context.getSystemService(Context.BLUETOOTH_SERVICE) as BluetoothManager
            val adapter = bluetoothManager.adapter

            if (adapter == null) {
                Log.e("BluetoothBridge", "Bluetooth not supported")
                // In emulator, allow mock devices even without real Bluetooth
                if (isEmulator()) {
                    Log.d("BluetoothBridge", "Emulator mode - Bluetooth adapter null, using mock mode")
                    BluetoothState.scanHandler = Handler(Looper.getMainLooper())
                    BluetoothState.isScanning = true
                    generateMockDevices(activity)
                    
                    // Set auto-stop timeout
                    BluetoothState.scanHandler?.postDelayed({
                        if (BluetoothState.isScanning) {
                            Log.d("BluetoothBridge", "Scan timeout reached")
                            stopScanInternal(context)
                            dispatchEvent(
                                activity,
                                "Sheenazien8\\BluetoothDevices\\Events\\BluetoothScanStopped",
                                mapOf("reason" to "timeout", "deviceCount" to BluetoothState.discoveredDevices.size)
                            )
                        }
                    }, BluetoothState.SCAN_TIMEOUT_MS)
                    
                    return BridgeResponse.success(mapOf(
                        "status" to "scanning",
                        "timeout" to BluetoothState.SCAN_TIMEOUT_MS,
                        "isEmulator" to isEmulator(),
                        "mockMode" to true
                    ))
                }
                return BridgeResponse.error("NOT_SUPPORTED", "Bluetooth not supported on this device")
            }

            if (!adapter.isEnabled) {
                Log.e("BluetoothBridge", "Bluetooth is disabled")
                // In emulator, allow mock devices even when Bluetooth is disabled
                if (isEmulator()) {
                    Log.d("BluetoothBridge", "Emulator mode - Bluetooth disabled, using mock mode")
                    BluetoothState.scanHandler = Handler(Looper.getMainLooper())
                    BluetoothState.isScanning = true
                    generateMockDevices(activity)
                    
                    // Set auto-stop timeout
                    BluetoothState.scanHandler?.postDelayed({
                        if (BluetoothState.isScanning) {
                            Log.d("BluetoothBridge", "Scan timeout reached")
                            stopScanInternal(context)
                            dispatchEvent(
                                activity,
                                "Sheenazien8\\BluetoothDevices\\Events\\BluetoothScanStopped",
                                mapOf("reason" to "timeout", "deviceCount" to BluetoothState.discoveredDevices.size)
                            )
                        }
                    }, BluetoothState.SCAN_TIMEOUT_MS)
                    
                    return BridgeResponse.success(mapOf(
                        "status" to "scanning",
                        "timeout" to BluetoothState.SCAN_TIMEOUT_MS,
                        "isEmulator" to isEmulator(),
                        "mockMode" to true
                    ))
                }
                return BridgeResponse.error("BLUETOOTH_DISABLED", "Bluetooth is disabled. Please enable Bluetooth first.")
            }

            // Start scan timeout handler
            BluetoothState.scanHandler = Handler(Looper.getMainLooper())
            BluetoothState.isScanning = true

            // Check if we're in emulator
            if (isEmulator()) {
                Log.d("BluetoothBridge", "Running in emulator - generating mock devices")
                generateMockDevices(activity)
            } else {
                // Try BLE scan first (Android 5.0+)
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                    startBleScan(context, adapter, activity)
                }

                // Also start Classic Bluetooth discovery as fallback
                startClassicDiscovery(context, adapter, activity)
            }

            // Set auto-stop timeout
            BluetoothState.scanHandler?.postDelayed({
                if (BluetoothState.isScanning) {
                    Log.d("BluetoothBridge", "Scan timeout reached")
                    stopScanInternal(context)
                    dispatchEvent(
                        activity,
                        "Sheenazien8\\BluetoothDevices\\Events\\BluetoothScanStopped",
                        mapOf("reason" to "timeout", "deviceCount" to BluetoothState.discoveredDevices.size)
                    )
                }
            }, BluetoothState.SCAN_TIMEOUT_MS)

            return BridgeResponse.success(mapOf(
                "status" to "scanning",
                "timeout" to BluetoothState.SCAN_TIMEOUT_MS,
                "isEmulator" to isEmulator()
            ))
        }

        private fun generateMockDevices(activity: FragmentActivity) {
            val mockDevices = listOf(
                Pair("Mock Device 1", "00:11:22:33:44:55"),
                Pair("Mock Device 2", "00:11:22:33:44:66"),
                Pair("Test BLE Device", "00:11:22:33:44:77"),
                Pair("Android Device", "00:11:22:33:44:88"),
                Pair("Unknown Device", "00:11:22:33:44:99")
            )

            // Simulate finding devices with delays
            mockDevices.forEachIndexed { index, (name, address) ->
                BluetoothState.scanHandler?.postDelayed({
                    if (BluetoothState.isScanning && !BluetoothState.discoveredDevices.contains(address)) {
                        BluetoothState.discoveredDevices.add(address)
                        Log.d("BluetoothBridge", "Mock device found: $name - $address")
                        dispatchEvent(
                            activity,
                            "Sheenazien8\\BluetoothDevices\\Events\\BluetoothDeviceFound",
                            mapOf(
                                "name" to name,
                                "address" to address,
                                "rssi" to Random.nextInt(-80, -30)
                            )
                        )
                    }
                }, (index + 1) * 2000L) // Stagger discoveries
            }
        }

        private fun startBleScan(context: Context, adapter: BluetoothAdapter, activity: FragmentActivity) {
            val scanner = adapter.bluetoothLeScanner
            if (scanner == null) {
                Log.w("BluetoothBridge", "BLE scanner not available")
                return
            }

            BluetoothState.scannerCallback = object : ScanCallback() {
                override fun onScanResult(callbackType: Int, result: ScanResult) {
                    try {
                        val device = result.device
                        val deviceName = device.name ?: "Unknown"
                        val deviceAddress = device.address
                        val rssi = result.rssi

                        if (BluetoothState.discoveredDevices.contains(deviceAddress)) {
                            return // Skip duplicates
                        }
                        BluetoothState.discoveredDevices.add(deviceAddress)

                        Log.d("BluetoothBridge", "BLE Device found: $deviceName - $deviceAddress (RSSI: $rssi)")

                        dispatchEvent(
                            activity,
                            "Sheenazien8\\BluetoothDevices\\Events\\BluetoothDeviceFound",
                            mapOf(
                                "name" to deviceName,
                                "address" to deviceAddress,
                                "rssi" to rssi
                            )
                        )
                    } catch (e: Exception) {
                        Log.e("BluetoothBridge", "Error processing BLE scan result", e)
                    }
                }

                override fun onBatchScanResults(results: MutableList<ScanResult>) {
                    results.forEach { result ->
                        onScanResult(0, result)
                    }
                }

                override fun onScanFailed(errorCode: Int) {
                    Log.e("BluetoothBridge", "BLE Scan failed with error code: $errorCode")
                    dispatchEvent(
                        activity,
                        "Sheenazien8\\BluetoothDevices\\Events\\BluetoothScanError",
                        mapOf("errorCode" to errorCode, "message" to getScanErrorMessage(errorCode))
                    )
                }
            }

            try {
                scanner.startScan(BluetoothState.scannerCallback)
                Log.d("BluetoothBridge", "BLE scan started successfully")
            } catch (e: Exception) {
                Log.e("BluetoothBridge", "Error starting BLE scan", e)
            }
        }

        private fun startClassicDiscovery(context: Context, adapter: BluetoothAdapter, activity: FragmentActivity) {
            try {
                if (adapter.isDiscovering) {
                    adapter.cancelDiscovery()
                }

                BluetoothState.classicReceiver = object : BroadcastReceiver() {
                    override fun onReceive(context: Context?, intent: Intent?) {
                        when (intent?.action) {
                            BluetoothDevice.ACTION_FOUND -> {
                                val device: BluetoothDevice? = intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE)
                                device?.let {
                                    val deviceName = it.name ?: "Unknown"
                                    val deviceAddress = it.address

                                    if (BluetoothState.discoveredDevices.contains(deviceAddress)) {
                                        return
                                    }
                                    BluetoothState.discoveredDevices.add(deviceAddress)

                                    Log.d("BluetoothBridge", "Classic device found: $deviceName - $deviceAddress")

                                    dispatchEvent(
                                        activity,
                                        "Sheenazien8\\BluetoothDevices\\Events\\BluetoothDeviceFound",
                                        mapOf(
                                            "name" to deviceName,
                                            "address" to deviceAddress,
                                            "rssi" to -50
                                        )
                                    )
                                }
                            }
                            BluetoothAdapter.ACTION_DISCOVERY_FINISHED -> {
                                Log.d("BluetoothBridge", "Classic discovery finished")
                                // Restart discovery to continue scanning
                                if (BluetoothState.isScanning && !isEmulator()) {
                                    Handler(Looper.getMainLooper()).postDelayed({
                                        if (BluetoothState.isScanning) {
                                            adapter.startDiscovery()
                                        }
                                    }, 1000)
                                }
                            }
                        }
                    }
                }

                val filter = IntentFilter().apply {
                    addAction(BluetoothDevice.ACTION_FOUND)
                    addAction(BluetoothAdapter.ACTION_DISCOVERY_FINISHED)
                }

                context.registerReceiver(BluetoothState.classicReceiver, filter)
                adapter.startDiscovery()
                Log.d("BluetoothBridge", "Classic Bluetooth discovery started")
            } catch (e: Exception) {
                Log.e("BluetoothBridge", "Error starting classic discovery", e)
            }
        }
    }

    class Connect(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val address = parameters["address"] as? String
                ?: return BridgeResponse.error("INVALID_ADDRESS", "Device address is required")

            val activity = context as FragmentActivity
            val adapter = (context.getSystemService(Context.BLUETOOTH_SERVICE) as BluetoothManager).adapter
                ?: return BridgeResponse.error("BLUETOOTH_UNAVAILABLE", "Bluetooth adapter not available")

            // Check if this is a mock device in emulator
            if (isEmulator()) {
                Log.d("BluetoothBridge", "Emulator mode - simulating connection to $address")
                Handler(Looper.getMainLooper()).postDelayed({
                    dispatchEvent(
                        activity,
                        "Sheenazien8\\BluetoothDevices\\Events\\BluetoothStateChanged",
                        mapOf("address" to address, "state" to "connected", "status" to 0)
                    )
                }, 1000)
                return BridgeResponse.success(mapOf("status" to "connecting", "emulator" to true))
            }

            val device = try {
                adapter.getRemoteDevice(address)
            } catch (e: IllegalArgumentException) {
                return BridgeResponse.error("INVALID_ADDRESS", "Invalid device address: $address")
            }

            // Stop scanning before connecting
            stopScanInternal(context)

            // Close any existing connection
            BluetoothState.activeGatt?.let { gatt ->
                try {
                    gatt.disconnect()
                    gatt.close()
                } catch (e: Exception) {
                    Log.e("BluetoothBridge", "Error closing previous GATT connection", e)
                }
            }

            BluetoothState.activeGatt = device.connectGatt(context, false, object : BluetoothGattCallback() {
                override fun onConnectionStateChange(gatt: BluetoothGatt, status: Int, newState: Int) {
                    val stateString = when (newState) {
                        BluetoothProfile.STATE_CONNECTED -> "connected"
                        BluetoothProfile.STATE_DISCONNECTED -> "disconnected"
                        BluetoothProfile.STATE_CONNECTING -> "connecting"
                        BluetoothProfile.STATE_DISCONNECTING -> "disconnecting"
                        else -> "unknown"
                    }

                    Log.d("BluetoothBridge", "Connection state changed: $stateString (status: $status)")

                    dispatchEvent(
                        activity,
                        "Sheenazien8\\BluetoothDevices\\Events\\BluetoothStateChanged",
                        mapOf(
                            "address" to gatt.device.address,
                            "state" to stateString,
                            "status" to status
                        )
                    )
                }

                override fun onServicesDiscovered(gatt: BluetoothGatt, status: Int) {
                    Log.d("BluetoothBridge", "Services discovered, status: $status")
                }
            })

            return BridgeResponse.success(mapOf("status" to "connecting"))
        }
    }

    class Disconnect(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            BluetoothState.activeGatt?.let { gatt ->
                try {
                    gatt.disconnect()
                    gatt.close()
                    Log.d("BluetoothBridge", "Disconnected and closed GATT")
                } catch (e: Exception) {
                    Log.e("BluetoothBridge", "Error disconnecting", e)
                }
            }
            BluetoothState.activeGatt = null

            val activity = context as FragmentActivity
            dispatchEvent(
                activity,
                "Sheenazien8\\BluetoothDevices\\Events\\BluetoothStateChanged",
                mapOf("address" to "", "state" to "disconnected")
            )

            return BridgeResponse.success(mapOf("status" to "disconnected"))
        }
    }

    class StopScan(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val activity = context as FragmentActivity
            val wasScanning = BluetoothState.isScanning
            val deviceCount = BluetoothState.discoveredDevices.size

            stopScanInternal(context)

            if (wasScanning) {
                dispatchEvent(
                    activity,
                    "Sheenazien8\\BluetoothDevices\\Events\\BluetoothScanStopped",
                    mapOf("reason" to "manual", "deviceCount" to deviceCount)
                )
            }

            return BridgeResponse.success(mapOf("status" to "stopped", "wasScanning" to wasScanning, "deviceCount" to deviceCount))
        }
    }

    private fun stopScanInternal(context: Context) {
        // Remove timeout callback
        BluetoothState.scanHandler?.removeCallbacksAndMessages(null)
        BluetoothState.scanHandler = null

        if (!BluetoothState.isScanning) {
            return
        }

        val adapter = (context.getSystemService(Context.BLUETOOTH_SERVICE) as? BluetoothManager)?.adapter

        // Stop BLE scan
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            val scanner = adapter?.bluetoothLeScanner
            BluetoothState.scannerCallback?.let { callback ->
                try {
                    scanner?.stopScan(callback)
                    Log.d("BluetoothBridge", "BLE scan stopped")
                } catch (e: Exception) {
                    Log.e("BluetoothBridge", "Error stopping BLE scan", e)
                }
                BluetoothState.scannerCallback = null
            }
        }

        // Stop Classic Bluetooth discovery
        BluetoothState.classicReceiver?.let { receiver ->
            try {
                adapter?.cancelDiscovery()
                context.unregisterReceiver(receiver)
                Log.d("BluetoothBridge", "Classic discovery stopped")
            } catch (e: Exception) {
                Log.e("BluetoothBridge", "Error stopping classic discovery", e)
            }
            BluetoothState.classicReceiver = null
        }

        BluetoothState.isScanning = false
        Log.d("BluetoothBridge", "Scan stopped - found ${BluetoothState.discoveredDevices.size} devices")
    }

    private fun getScanErrorMessage(errorCode: Int): String {
        return when (errorCode) {
            ScanCallback.SCAN_FAILED_ALREADY_STARTED -> "Scan already started"
            ScanCallback.SCAN_FAILED_APPLICATION_REGISTRATION_FAILED -> "App registration failed"
            ScanCallback.SCAN_FAILED_INTERNAL_ERROR -> "Internal error"
            ScanCallback.SCAN_FAILED_FEATURE_UNSUPPORTED -> "Feature unsupported"
            ScanCallback.SCAN_FAILED_OUT_OF_HARDWARE_RESOURCES -> "Out of hardware resources"
            ScanCallback.SCAN_FAILED_SCANNING_TOO_FREQUENTLY -> "Scanning too frequently"
            else -> "Unknown error code: $errorCode"
        }
    }

    private fun dispatchPermissionStatus(activity: FragmentActivity, context: Context) {
        val (hasPermissions, missingPermissions) = checkBluetoothPermissions(context)

        val bluetoothManager = context.getSystemService(Context.BLUETOOTH_SERVICE) as? BluetoothManager
        val adapter = bluetoothManager?.adapter
        val isBluetoothEnabled = adapter?.isEnabled == true

        Log.d("BluetoothBridge", "Dispatching permission status: has=$hasPermissions, missing=$missingPermissions, bt_enabled=$isBluetoothEnabled")

        dispatchEvent(
            activity,
            "Sheenazien8\\BluetoothDevices\\Events\\BluetoothPermissionsChecked",
            mapOf(
                "hasPermissions" to hasPermissions,
                "missingPermissions" to missingPermissions,
                "bluetoothEnabled" to isBluetoothEnabled,
                "androidVersion" to Build.VERSION.SDK_INT
            )
        )
    }

    class CheckPermissions(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val activity = context as FragmentActivity
            dispatchPermissionStatus(activity, context)
            return BridgeResponse.success(mapOf("status" to "checking"))
        }
    }

    class RequestPermissions(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val activity = context as FragmentActivity
            val (_, missingPermissions) = checkBluetoothPermissions(context)

            if (missingPermissions.isEmpty()) {
                dispatchPermissionStatus(activity, context)
                return BridgeResponse.success(mapOf("status" to "already_granted"))
            }

            val permissionsToRequest = mutableListOf<String>()

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                if (missingPermissions.contains("BLUETOOTH_SCAN")) {
                    permissionsToRequest.add(Manifest.permission.BLUETOOTH_SCAN)
                }
                if (missingPermissions.contains("BLUETOOTH_CONNECT")) {
                    permissionsToRequest.add(Manifest.permission.BLUETOOTH_CONNECT)
                }
            }

            if (missingPermissions.contains("ACCESS_FINE_LOCATION")) {
                permissionsToRequest.add(Manifest.permission.ACCESS_FINE_LOCATION)
            }

            ActivityCompat.requestPermissions(activity, permissionsToRequest.toTypedArray(), 1001)

            val handler = Handler(Looper.getMainLooper())
            val delays = longArrayOf(1000, 2000, 4000)
            for (delay in delays) {
                handler.postDelayed({
                    dispatchPermissionStatus(activity, context)
                }, delay)
            }

            return BridgeResponse.success(mapOf(
                "status" to "requested",
                "permissions" to missingPermissions
            ))
        }
    }
}
