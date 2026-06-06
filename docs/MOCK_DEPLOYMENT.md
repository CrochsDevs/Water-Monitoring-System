# Mock Deployment Wiring — 3 Arduino Unos + USB Bridge to Dashboard

## System Overview

While waiting for the A7680C 4G module, each Arduino sends readings via **USB serial** to a **Python bridge script** running on a PC. The bridge forwards data via Wi-Fi to the dashboard at `http://112.206.137.185:8080`.

```
  Arduino RF01 ──USB──┐
  Arduino RF02 ──USB──┤── PC running bridge.py ──Wi-Fi──> Dashboard
  Arduino RF03 ──USB──┘
```

---

## Per-Device Wiring — Arduino Uno

Each of the 3 Unos is wired identically. SIM module pins are **reserved** — header pins soldered but nothing connected yet.

```
   JSN-SR04T           LCD 16x2 I2C       Reserved for A7680C
   ──────────           ────────────       ─────────────────
   VCC ──→ 5V           VCC ──→ 5V          (not connected yet)
   GND ──→ GND          GND ──→ GND
   TRIG ──→ D9          SDA ──→ A4         D2 ──→ SIM RX (reserved)
   ECHO ──→ D10         SCL ──→ A5         D3 ──→ SIM TX (reserved)
                                            D4 ──→ SIM PWRKEY (reserved)
```

### Visual Pin Map

```
   ARDUINO UNO
   ┌─────────────────────────┐
   │                         │
   │  D0 (RX)  ── (reserved) │
   │  D1 (TX)  ── (reserved) │
   │  D2       ── SIM RX     │  ← reserved, not connected
   │  D3       ── SIM TX     │  ← reserved, not connected
   │  D4       ── SIM PWRKEY │  ← reserved, not connected
   │  D5       ── free       │
   │  D6       ── free       │
   │  D7       ── free       │
   │  D8       ── free       │
   │  D9       ── JSN TRIG   │
   │  D10      ── JSN ECHO   │
   │  D11      ── free       │
   │  D12      ── free       │
   │  D13      ── LED        │
   │                         │
   │  A0       ── battery    │  ← optional, leave for now
   │  A1       ── free       │
   │  A2       ── free       │
   │  A3       ── free       │
   │  A4 (SDA) ── LCD SDA    │
   │  A5 (SCL) ── LCD SCL    │
   └─────────────────────────┘
```

---

## Device Configuration

Each Arduino gets its own device_id and field depth set in the sketch:

| Device | ID | FIELD_DEPTH_CM | Simulates |
|--------|:--:|:--------------:|-----------|
| Arduino 1 | **RF01** | 50.0 cm | Shallow paddy |
| Arduino 2 | **RF02** | 65.0 cm | Medium paddy |
| Arduino 3 | **RF03** | 85.0 cm | Deep field |

---

## USB Bridge Script

The Python bridge (`usb_bridge.py`) reads all connected Arduinos and sends data to the dashboard.

```
  python3 usb_bridge.py
  # Auto-detects /dev/ttyACM0, /dev/ttyACM1, /dev/ttyACM2
```

It expects each Arduino to output lines like:
```
[MEASURE] Distance: 35.2 cm | Water Level: 14.8 cm
```

And it POSTs to:
```
POST http://112.206.137.185:8080/api/reading.php
{
  "device_id": "RF01",
  "water_level_cm": 14.8,
  "distance_cm": 35.2,
  "battery_v": 5.0,
  "signal": 0,
  "reading_mode": "serial_usb"
}
```
