#!/usr/bin/env python3
"""
Serial Monitor — JSN-SR04T Water Level Monitor
Reads data from Arduino via USB serial and displays it with timestamps.

Usage:
    python3 serial_monitor.py              # auto-detect port
    python3 serial_monitor.py /dev/ttyACM0  # specify port
    python3 serial_monitor.py --log data.csv  # log to file
"""

import serial
import serial.tools.list_ports
import time
import sys
import argparse
from datetime import datetime

# ─── Configuration ──────────────────────────────────────────
BAUD_RATE = 9600
RECONNECT_DELAY = 3

# ─── Print helpers (always flush for piped output) ──────────
def p(*args, **kw):
    kw['flush'] = True
    print(*args, **kw)


# ─── Find Arduino Port ──────────────────────────────────────
def find_arduino_port():
    ports = list(serial.tools.list_ports.comports())
    # Look for Arduino by USB vendor ID
    for p in ports:
        vid_str = f"{p.vid:04X}" if p.vid else ""
        if vid_str in ('2341', '2A03'):  # Arduino VID
            return p.device
    # Common Arduino USB chips
    for p in ports:
        desc = (p.description or '').lower()
        if any(x in desc for x in ['arduino', 'ch340', 'ch341', 'cp210']):
            return p.device
    # TTY paths
    for p in ports:
        dev = p.device or ''
        if dev.startswith('/dev/ttyACM') or dev.startswith('/dev/ttyUSB'):
            return dev
    return None


def open_serial(port, baud):
    """Open serial port, reset Arduino, flush boot garbage."""
    ser = serial.Serial()
    ser.port = port
    ser.baudrate = baud
    ser.timeout = 3
    ser.dtr = True
    ser.open()
    time.sleep(0.1)
    ser.dtr = False       # DTR low triggers Arduino reset
    time.sleep(2.5)        # wait for bootloader + sketch startup
    ser.reset_input_buffer()
    return ser


# ─── Parse lines into structured format ─────────────────────
def parse_line(text):
    if '[MEASURE]' in text:
        # Format: [MEASURE] Distance: 160.0 cm  OK
        # Or:     [MEASURE] Distance: 35.2 cm | Water Level: 14.8 cm
        parts = text.split('|')
        # Extract distance from first part
        dist_part = parts[0].replace('[MEASURE]', '').replace('Distance:', '').replace('cm', '').strip()
        # Extract any warnings/status at end
        dist_clean = dist_part.split()[0] if dist_part.split() else '?'
        if len(parts) > 1:
            level = parts[1].replace('Water Level:', '').replace('cm', '').strip()
            return ('measurement', {'dist': dist_clean, 'level': level})
        return ('measurement', {'dist': dist_clean, 'level': '?'})
    if '[BATTERY]' in text:
        parts = text.split('|')
        volt = parts[1].replace('Voltage:', '').replace('V', '').strip() if len(parts) > 1 else '?'
        return ('battery', volt)
    if '[ERROR]' in text or '[WARN]' in text:
        return ('error', text)
    if '[ALERT]' in text:
        return ('alert', text)
    if '[SMS]' in text or '[GSM]' in text:
        return ('comm', text)
    return ('info', text)


# ─── Main ────────────────────────────────────────────────────
def monitor(port=None, log_file=None):
    import sys as _sys
    log = None
    if log_file:
        log = open(log_file, 'a')
        log.write(f"\n--- [{datetime.now()}] Session started ---\n")
        log.flush()

    if not port:
        port = find_arduino_port()
        if not port:
            p("No Arduino found. Specify port or plug it in.")
            p("  python3 serial_monitor.py /dev/ttyACM0")
            _sys.exit(1)

    ser = None
    while True:
        try:
            if ser is None:
                ser = open_serial(port, BAUD_RATE)
                p(f"Connected to {port} @ {BAUD_RATE} baud")
                p("─" * 50)

            raw = ser.readline()
            if not raw:
                continue

            line = raw.decode('utf-8', errors='replace').strip()
            if not line:
                continue

            now = datetime.now()
            ts = now.strftime('%H:%M:%S')

            # Format output
            kind = parse_line(line)
            tag = kind[0]

            if tag == 'measurement':
                d = kind[1]['dist']
                w = kind[1]['level']
                p(f"  {ts}  {d} cm   water: {w} cm  💧")
            elif tag == 'battery':
                _, v = kind
                p(f"  {ts}  🔋 {v} V")
            elif tag == 'error':
                p(f"  {ts}  ❌ {kind[1]}")
            elif tag == 'alert':
                p(f"  {ts}  ⚠️  {kind[1]}")
            elif tag == 'comm':
                p(f"  {ts}  📡 {kind[1]}")
            else:
                p(f"  {ts}  {line}")

            # Log
            if log:
                log.write(f"[{now.isoformat()}] {line}\n")
                log.flush()

        except KeyboardInterrupt:
            p("\n─" * 50)
            p("Stopped by user")
            if ser:
                ser.close()
            if log:
                log.write(f"--- [{datetime.now()}] Session ended ---\n")
                log.close()
            _sys.exit(0)

        except (serial.SerialException, OSError) as e:
            p(f"\nConnection lost ({e}). Reconnecting in {RECONNECT_DELAY}s...")
            if ser:
                try:
                    ser.close()
                except Exception:
                    pass
                ser = None
            time.sleep(RECONNECT_DELAY)

        except Exception as e:
            p(f"\nError: {e}")
            if ser:
                try:
                    ser.close()
                except Exception:
                    pass
                ser = None
            time.sleep(RECONNECT_DELAY)


# ─── Entry ───────────────────────────────────────────────────
if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='JSN-SR04T Serial Monitor')
    parser.add_argument('port', nargs='?', help='Serial port (e.g. /dev/ttyACM0)')
    parser.add_argument('--log', '-l', metavar='FILE', help='Log output to file')
    args = parser.parse_args()

    p("╔══════════════════════════════════════════╗")
    p("║  JSN-SR04T Water Level — Serial Monitor ║")
    p("╚══════════════════════════════════════════╝")
    monitor(args.port, args.log)
