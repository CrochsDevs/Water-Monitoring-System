#!/usr/bin/env python3 -u
"""
Module Tester Monitor — ESP32 + A7680C/UART
Shows live results as you swap modules.

Usage:
    python3 tools/module_monitor.py /dev/ttyUSB0
"""
import serial, time, sys

port = sys.argv[1] if len(sys.argv) > 1 else '/dev/ttyUSB0'

print("╔══════════════════════════════════════════╗")
print("║     A7680C Module Tester                ║")
print("║  Sends AT every 5s via UART2 (GPIO16/17)║")
print("║  Swap modules and watch for results      ║")
print("╚══════════════════════════════════════════╝")
print()

s = serial.Serial(port, 115200, timeout=2)
time.sleep(3)
s.reset_input_buffer()

while True:
    try:
        line = s.readline().decode(errors='replace').strip()
        if not line:
            continue
        
        # Color-code markers
        if "✅" in line:
            print(f"✅ {line}")
        elif "⚠️" in line:
            print(f"⚠️  {line}")
        elif "❌" in line:
            print(f"❌ {line}")
        elif "OK found" in line:
            print(f"✅ {line}")
        elif "Garbled" in line:
            print(f"⚠️  {line}")
        elif "No response" in line:
            print(f"❌ {line}")
        elif "---" in line:
            print(f"\n{line}")
        else:
            print(f"  {line}")
            
    except KeyboardInterrupt:
        print("\nStopped.")
        break
    except Exception as e:
        pass

s.close()
