#!/usr/bin/env python3
"""
Rice Field Water Monitor — Serial Bridge
=========================================
Reads water level data from Arduino over USB serial and forwards
it to the web dashboard API via HTTP POST.

Usage:
    python3 serial_bridge.py --port /dev/ttyUSB0 --url http://192.168.1.31:8080/api/reading.php

Options:
    --port      Serial port (default: auto-detect)
    --baud      Baud rate (default: 9600)
    --url       Dashboard API URL (default: http://localhost:8080/api/reading.php)
    --device    Device ID (default: RF01)
    --verbose   Print debug output
    --daemon    Run as background daemon

The Arduino must output JSON lines over Serial in this format:
    {"water_level_cm":12.5,"distance_cm":187.5,"battery_v":12.4,"signal":18,"alert":null}
"""

import sys
import json
import time
import argparse
import urllib.request
import urllib.error
import serial
import serial.tools.list_ports


def find_arduino_port():
    """Auto-detect Arduino serial port."""
    ports = list(serial.tools.list_ports.comports())
    for p in ports:
        desc = p.description.lower()
        # Look for common Arduino identifiers
        if any(name in desc for name in ['arduino', 'ch340', 'cp210', 'ftdi', 'usb serial']):
            return p.device
        # Fallback: any USB-serial adapter
        if 'usb' in desc and ('serial' in desc or 'uart' in desc):
            return p.device
    # Last resort: common Linux ports
    for port in ['/dev/ttyUSB0', '/dev/ttyACM0', '/dev/ttyAMA0']:
        try:
            s = serial.Serial(port)
            s.close()
            return port
        except (serial.SerialException, OSError):
            continue
    return None


def post_reading(data, api_url, verbose=False):
    """POST a JSON reading to the dashboard API."""
    try:
        payload = json.dumps(data).encode('utf-8')
        req = urllib.request.Request(
            api_url,
            data=payload,
            headers={'Content-Type': 'application/json'},
            method='POST'
        )
        resp = urllib.request.urlopen(req, timeout=10)
        body = resp.read().decode('utf-8')
        result = json.loads(body)

        if verbose:
            ts = time.strftime('%Y-%m-%d %H:%M:%S')
            print(f"[{ts}] POST {data.get('water_level_cm', '?')}cm → {api_url} → {'OK' if result.get('success') else 'FAIL'}")
        return result.get('success', False)
    except urllib.error.HTTPError as e:
        if verbose:
            print(f"[ERROR] HTTP {e.code}: {e.read().decode('utf-8')[:200]}")
        return False
    except Exception as e:
        if verbose:
            print(f"[ERROR] POST failed: {e}")
        return False


def parse_reading(line, device_id):
    """
    Parse a JSON line from the Arduino.
    The Arduino should output lines like:
        {"water_level_cm":12.5,"distance_cm":187.5,"battery_v":12.4,"signal":18,"alert":null}

    Also accepts partial JSON (e.g. just water level sensor reading).
    """
    line = line.strip()
    if not line:
        return None

    # Try parsing as JSON first
    if line.startswith('{') and line.endswith('}'):
        try:
            data = json.loads(line)
            # Ensure required fields
            if 'water_level_cm' in data and 'distance_cm' in data:
                data.setdefault('device_id', device_id)
                data.setdefault('battery_v', 0)
                data.setdefault('signal', 0)
                data.setdefault('alert', None)
                data.setdefault('reading_mode', 'serial_usb')
                return data
        except json.JSONDecodeError:
            pass

    # Fallback: try to parse structured log lines
    # Format: [MEASURE] Distance: 187.5 cm | Water Level: 12.5 cm
    if 'Water Level:' in line:
        try:
            import re
            wl_match = re.search(r'Water Level:\s*([\d.]+)', line)
            dist_match = re.search(r'Distance:\s*([\d.]+)', line)
            if wl_match:
                return {
                    'device_id': device_id,
                    'water_level_cm': float(wl_match.group(1)),
                    'distance_cm': float(dist_match.group(1)) if dist_match else 0,
                    'battery_v': 0,
                    'signal': 0,
                    'alert': None,
                    'reading_mode': 'serial_usb'
                }
        except (ValueError, AttributeError):
            pass

    return None


def main():
    parser = argparse.ArgumentParser(description='Arduino USB serial → Dashboard bridge')
    parser.add_argument('--port', '-p', help='Serial port (auto-detect if omitted)')
    parser.add_argument('--baud', '-b', type=int, default=9600, help='Baud rate (default: 9600)')
    parser.add_argument('--url', '-u', default='http://localhost:8080/api/reading.php',
                        help='Dashboard API URL')
    parser.add_argument('--device', '-d', default='RF01', help='Device ID (default: RF01)')
    parser.add_argument('--verbose', '-v', action='store_true', help='Verbose output')
    parser.add_argument('--daemon', action='store_true', help='Run continuously')
    args = parser.parse_args()

    # Auto-detect port if not specified
    port = args.port or find_arduino_port()
    if not port:
        print("[FATAL] No Arduino serial port found.")
        print("Specify with --port /dev/ttyUSB0")
        sys.exit(1)

    if args.verbose:
        print(f"[INIT] Port: {port} @ {args.baud} baud")
        print(f"[INIT] API: {args.url}")
        print(f"[INIT] Device: {args.device}")

    # Connect to serial
    try:
        ser = serial.Serial(port, args.baud, timeout=2)
        time.sleep(2)  # Wait for Arduino reset
        if args.verbose:
            print(f"[INIT] Connected to {port}")
    except serial.SerialException as e:
        print(f"[FATAL] Cannot open {port}: {e}")
        sys.exit(1)

    # Main loop
    post_interval = 60  # Don't POST more than once per 60 seconds
    last_post = 0
    last_reading = None
    readings_sent = 0
    start_time = time.time()

    try:
        while True:
            try:
                line = ser.readline().decode('utf-8', errors='replace').strip()
            except serial.SerialException:
                if args.verbose:
                    print("[WARN] Serial error — reconnecting...")
                time.sleep(2)
                try:
                    ser.close()
                    ser = serial.Serial(port, args.baud, timeout=2)
                    time.sleep(2)
                except serial.SerialException as e:
                    print(f"[FATAL] Reconnect failed: {e}")
                    break
                continue

            if not line:
                continue

            if args.verbose and not line.startswith('{'):
                # Print non-JSON debug lines
                print(f"[ARDUINO] {line}")
                continue

            # Try to parse a reading
            reading = parse_reading(line, args.device)
            if reading:
                last_reading = reading

                # Check if it's time to POST
                now = time.time()
                if now - last_post >= post_interval:
                    success = post_reading(reading, args.url, args.verbose)
                    if success:
                        readings_sent += 1
                        last_post = now
                    elif args.verbose:
                        print(f"[WARN] POST failed, will retry next reading")

                # Print JSON reading to stdout for piped usage
                print(json.dumps(reading))

            if not args.daemon:
                # Single-shot mode: send first reading and exit
                break

    except KeyboardInterrupt:
        elapsed = time.time() - start_time
        if args.verbose:
            print(f"\n[STOP] Ran for {elapsed:.0f}s, {readings_sent} readings sent")
    finally:
        ser.close()


if __name__ == '__main__':
    main()
