#!/usr/bin/env python3
"""
Quick Serial Monitor — for JSN-SR04T Water Level Monitor

Usage:
    python3 quick_monitor.py              # auto-detect
    python3 quick_monitor.py /dev/ttyACM0  # specific port
"""

import serial
import serial.tools.list_ports as lp
import sys, time
from datetime import datetime

def find_port():
    for p in lp.comports():
        if any(x in (p.device or '') for x in ['ttyACM', 'ttyUSB']):
            return p.device
    return None

port = sys.argv[1] if len(sys.argv) > 1 else find_port()
if not port:
    print("No Arduino found. Specify port: python3 quick_monitor.py /dev/ttyACM0")
    sys.exit(1)

print(f"Opening {port} at 9600 baud...")
s = serial.Serial(port, 9600, timeout=2)
s.dtr = False
time.sleep(2)
s.reset_input_buffer()
print("Listening. Ctrl+C to stop.")
print("-" * 50)

try:
    while True:
        raw = s.readline()
        if raw:
            line = raw.decode('utf-8', errors='replace').strip()
            if line:
                ts = datetime.now().strftime('%H:%M:%S')
                print(f"[{ts}] {line}")
except KeyboardInterrupt:
    print("Stopped.")
    s.close()
