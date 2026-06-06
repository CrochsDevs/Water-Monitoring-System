#!/usr/bin/env python3 -u
"""
USB Bridge — Reads Arduino(s) via USB serial and forwards to dashboard API.

Usage:
    python3 usb_bridge.py                          # auto-detect all Arduinos
    python3 usb_bridge.py /dev/ttyACM0             # single Arduino
    python3 usb_bridge.py --api http://.../api/reading.php

Each Arduino must output lines like:
    [MEASURE] Distance: 35.2 cm | Water Level: 14.8 cm
    [BATTERY] ADC: 512 | Voltage: 5.0 V
"""

import serial, serial.tools.list_ports, sys, time, json, urllib.request
from datetime import datetime

API_URL = "http://112.206.137.185:8080/api/reading.php"
DEVICE_MAP = {}  # port -> device_id (auto-detected from Arduino's serial output)

def find_arduinos():
    ports = []
    for p in serial.tools.list_ports.comports():
        dev = p.device or ''
        if dev.startswith('/dev/ttyACM') or dev.startswith('/dev/ttyUSB'):
            ports.append(dev)
    return sorted(ports)

def post_to_dashboard(device_id, water_level, distance, battery, signal):
    data = json.dumps({
        "device_id": device_id,
        "water_level_cm": round(water_level, 1),
        "distance_cm": round(distance, 1),
        "battery_v": round(battery, 1),
        "signal_strength": signal,
        "reading_mode": "serial_usb"
    }).encode()
    try:
        req = urllib.request.Request(API_URL, data=data,
            headers={"Content-Type": "application/json"})
        resp = urllib.request.urlopen(req, timeout=5)
        body = resp.read().decode()
        return True, body
    except Exception as e:
        return False, str(e)

def parse_line(line, device_id):
    """Extract measurements from Arduino serial output."""
    # [MEASURE] Distance: 35.2 cm | Water Level: 14.8 cm
    if '[MEASURE]' in line:
        if 'Distance:' in line:
            dist_part = line.split('Distance:')[1].strip()
            dist = dist_part.split('cm')[0].strip()
        else:
            dist = '0'
        if 'Water Level:' in line:
            level_part = line.split('Water Level:')[1].strip()
            level = level_part.split('cm')[0].strip()
        else:
            level = '0'
        return {
            'device_id': device_id,
            'water_level': float(level),
            'distance': float(dist),
            'battery': 5.0,
            'signal': 0
        }
    return None

def main():
    global API_URL
    ports_to_try = []
    
    # Parse args
    args = [a for a in sys.argv[1:] if not a.startswith('--')]
    if '--api' in sys.argv:
        idx = sys.argv.index('--api')
        API_URL = sys.argv[idx + 1] if idx + 1 < len(sys.argv) else API_URL
    
    if args:
        ports_to_try = args
    else:
        ports_to_try = find_arduinos()
    
    if not ports_to_try:
        print("No Arduinos found. Specify port: python3 usb_bridge.py /dev/ttyACM0")
        sys.exit(1)
    
    # Open all ports
    serials = {}
    for port in ports_to_try:
        try:
            s = serial.Serial(port, 9600, timeout=1)
            s.dtr = False
            time.sleep(0.5)
            s.reset_input_buffer()
            serials[port] = s
            DEVICE_MAP[port] = f"RF{len(serials):02d}"
            print(f"✅ {port} → {DEVICE_MAP[port]}")
        except Exception as e:
            print(f"❌ {port}: {e}")
    
    if not serials:
        print("No ports could be opened.")
        sys.exit(1)
    
    print(f"📡 Dashboard: {API_URL}")
    print(f"🔄 Bridge running ({len(serials)} device(s)). Ctrl+C to stop.")
    print("─" * 60)
    
    try:
        while True:
            for port, s in serials.items():
                try:
                    raw = s.read(500)
                    if not raw:
                        continue
                    
                    lines = raw.decode('utf-8', errors='replace').split('\n')
                    for line in lines:
                        line = line.strip()
                        if not line:
                            continue
                        
                        ts = datetime.now().strftime('%H:%M:%S')
                        device_id = DEVICE_MAP[port]
                        parsed = parse_line(line, device_id)
                        
                        if parsed:
                            ok, resp = post_to_dashboard(
                                parsed['device_id'],
                                parsed['water_level'],
                                parsed['distance'],
                                parsed['battery'],
                                parsed['signal']
                            )
                            if ok:
                                print(f"  [{ts}] {parsed['device_id']}: {parsed['water_level']}cm → ✅ Dashboard")
                            else:
                                print(f"  [{ts}] {parsed['device_id']}: {parsed['water_level']}cm → ❌ {resp[:60]}")
                        else:
                            # Just echo debug output
                            if line and not line.startswith('.'):
                                print(f"  [{ts}] {device_id} | {line[:80]}")
                                
                except serial.SerialException:
                    print(f"⚠️  {port} disconnected")
                    serials.pop(port, None)
                except Exception as e:
                    print(f"⚠️  {port}: {e}")
            
            time.sleep(0.5)
    
    except KeyboardInterrupt:
        print("\nStopping bridge...")
        for s in serials.values():
            s.close()
        print("Done.")

if __name__ == '__main__':
    main()
