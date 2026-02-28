package com.sheenazien8.plugins.bluetooth_devices

import android.Manifest
import android.bluetooth.*
import android.bluetooth.le.ScanCallback
import android.bluetooth.le.ScanResult
import android.content.Context
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import com.nativephp.mobile.utils.NativeActionCoordinator
import org.json.JSONObject
import android.util.Log

object BluetoothState {
    var scannerCallback: ScanCallback? = null
    var activeGatt: BluetoothGatt? = null
}

object BluetoothDevicesFunctions {

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
            Log.d("BluetoothBridge", "Starting a new BLE scan...")

            val activity = context as FragmentActivity

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
                return BridgeResponse.error("NOT_SUPPORTED", "Bluetooth not supported on this device")
            }

            if (!adapter.isEnabled) {
                Log.e("BluetoothBridge", "Bluetooth is disabled")
                return BridgeResponse.error("BLUETOOTH_DISABLED", "Bluetooth is disabled. Please enable Bluetooth first.")
            }

            val scanner = adapter.bluetoothLeScanner
            if (scanner == null) {
                Log.e("BluetoothBridge", "BluetoothLeScanner is null - Bluetooth might be turning on or off")
                return BridgeResponse.error("SCANNER_UNAVAILABLE", "Bluetooth scanner not available. Please try again.")
            }

            BluetoothState.scannerCallback?.let { callback ->
                try {
                    scanner.stopScan(callback)
                } catch (e: Exception) {
                    Log.e("BluetoothBridge", "Error stopping previous scan", e)
                }
            }

            BluetoothState.scannerCallback = object : ScanCallback() {
                override fun onScanResult(callbackType: Int, result: ScanResult) {
                    try {
                        val deviceName = result.device.name ?: "Unknown"
                        val deviceAddress = result.device.address

                        Log.d("BluetoothBridge", "Device found: $deviceName - $deviceAddress")

                        NativeActionCoordinator.dispatchEvent(
                            activity,
                            "bluetooth.device_found",
                            JSONObject(mapOf(
                                "name" to deviceName,
                                "address" to deviceAddress
                            )).toString()
                        )
                    } catch (e: Exception) {
                        Log.e("BluetoothBridge", "Error dispatching event", e)
                    }
                }

                override fun onBatchScanResults(results: MutableList<ScanResult>) {
                    results.forEach { result ->
                        onScanResult(0, result)
                    }
                }

                override fun onScanFailed(errorCode: Int) {
                    Log.e("BluetoothBridge", "Scan failed with error code: $errorCode")
                    try {
                        NativeActionCoordinator.dispatchEvent(
                            activity,
                            "bluetooth.scan_error",
                            JSONObject(mapOf(
                                "error_code" to errorCode,
                                "error_message" to "Scan failed with code $errorCode"
                            )).toString()
                        )
                    } catch (e: Exception) {
                        Log.e("BluetoothBridge", "Error dispatching scan failed event", e)
                    }
                }
            }

            try {
                scanner.startScan(BluetoothState.scannerCallback)
                Log.d("BluetoothBridge", "BLE scan started successfully")
                return BridgeResponse.success(mapOf("status" to "scanning"))
            } catch (e: Exception) {
                Log.e("BluetoothBridge", "Error starting scan", e)
                return BridgeResponse.error("SCAN_FAILED", "Failed to start scan: ${e.message}")
            }
        }
    }

    class Connect(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val address = parameters["address"] as String
            val adapter = (context.getSystemService(Context.BLUETOOTH_SERVICE) as BluetoothManager).adapter
            val device = adapter.getRemoteDevice(address)

            BluetoothState.activeGatt = device.connectGatt(context, false, object : BluetoothGattCallback() {
                override fun onConnectionStateChange(gatt: BluetoothGatt, status: Int, newState: Int) {
                    val stateString = if (newState == BluetoothProfile.STATE_CONNECTED) "connected" else "disconnected"
                    try {
                        NativeActionCoordinator.dispatchEvent(
                            context as FragmentActivity,
                            "bluetooth.state_changed",
                            JSONObject(mapOf(
                                "address" to gatt.device.address,
                                "state" to stateString
                            )).toString()
                        )
                    } catch (e: Exception) {
                        Log.e("BluetoothBridge", "Error dispatching state changed", e)
                    }
                }
            })
            return BridgeResponse.success(mapOf("status" to "connecting"))
        }
    }

    class Disconnect(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            BluetoothState.activeGatt?.disconnect()
            BluetoothState.activeGatt?.close()
            BluetoothState.activeGatt = null
            return BridgeResponse.success(mapOf("status" to "disconnected"))
        }
    }

    class StopScan(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val adapter = (context.getSystemService(Context.BLUETOOTH_SERVICE) as BluetoothManager).adapter
            BluetoothState.scannerCallback?.let { adapter.bluetoothLeScanner.stopScan(it as ScanCallback) }
            return BridgeResponse.success(mapOf("status" to "stopped"))
        }
    }

    class CheckPermissions(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val (hasPermissions, missingPermissions) = checkBluetoothPermissions(context)

            val bluetoothManager = context.getSystemService(Context.BLUETOOTH_SERVICE) as? BluetoothManager
            val adapter = bluetoothManager?.adapter
            val isBluetoothEnabled = adapter?.isEnabled == true

            return BridgeResponse.success(mapOf(
                "has_permissions" to hasPermissions,
                "missing_permissions" to missingPermissions,
                "bluetooth_enabled" to isBluetoothEnabled,
                "android_version" to Build.VERSION.SDK_INT
            ))
        }
    }

    class RequestPermissions(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val activity = context as FragmentActivity
            val (_, missingPermissions) = checkBluetoothPermissions(context)

            if (missingPermissions.isEmpty()) {
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

            return BridgeResponse.success(mapOf(
                "status" to "requested",
                "permissions" to missingPermissions
            ))
        }
    }
}