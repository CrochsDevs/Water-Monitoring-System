# Wiring Diagram — JSN-SR04T + LCD + Reserved SIM Pins

## Per-Device Wiring (x3: RF01, RF02, RF03)

All 3 Arduinos are wired identically. Only the DEVICE_ID and FIELD_DEPTH differ in code.

---

### Arduino Uno Pinout — Full View

```
                    ARDUINO UNO (top view)
   ┌─────────────────────────────────────────────┐
   │  ┌───────────────────────────────────────┐  │
   │  │  ○ D0/RX    ← Reserved: A7680C TX     │  │
   │  │  ○ D1/TX    → Reserved: A7680C RX     │  │
   │  │  ○ D2       → Reserved: A7680C PWRKEY  │  │
   │  │  ○ D3       - free                     │  │
   │  │  ○ D4       - free                     │  │
   │  │  ○ D5       - free                     │  │
   │  │  ○ D6       - free                     │  │
   │  │  ○ D7       - free                     │  │
   │  │  ○ D8       - free                     │  │
   │  │  ○ D9       → JSN-SR04T TRIG (yellow)  │  │
   │  │  ○ D10      ← JSN-SR04T ECHO (green)   │  │
   │  │  ○ D11      - free                     │  │
   │  │  ○ D12      - free                     │  │
   │  │  ○ D13      → built-in LED             │  │
   │  └───────────────────────────────────────┘  │
   │                                              │
   │  ┌───────────────────────────────────────┐  │
   │  │  ○ A0       - free (battery monitor)  │  │
   │  │  ○ A1       - free                     │  │
   │  │  ○ A2       - free                     │  │
   │  │  ○ A3       - free                     │  │
   │  │  ○ A4/SDA   → LCD SDA                  │  │
   │  │  ○ A5/SCL   → LCD SCL                  │  │
   │  └───────────────────────────────────────┘  │
   │                                              │
   │  ○ 5V        → JSN VCC, LCD VCC             │
   │  ○ 3.3V      - free                          │
   │  ○ GND       → JSN GND, LCD GND             │
   │  ○ Vin       - free (for battery input)      │
   └─────────────────────────────────────────────┘
```

---

### Wiring Table

| Arduino Pin | Connects To | Wire Color (suggested) | Notes |
|:-----------:|-------------|:----------------------:|-------|
| **5V** | JSN-SR04T VCC | Red | Power for sensor |
| **5V** | LCD VCC | Red | Power for LCD |
| **GND** | JSN-SR04T GND | Black | Common ground |
| **GND** | LCD GND | Black | Common ground |
| **D9** | JSN-SR04T TRIG | Yellow | Trigger pulse |
| **D10** | JSN-SR04T ECHO | Green | Echo pulse in |
| **A4** | LCD SDA | Blue | I2C data |
| **A5** | LCD SCL | Blue/White | I2C clock |
| **D2** | *(reserved)* | — | A7680C TX later |
| **D3** | *(reserved)* | — | A7680C RX later |
| **D4** | *(reserved)* | — | A7680C PWRKEY later |

---

### LCD I2C Backpack Jumper Settings

Some LCD backpacks have address jumpers on the back. Default is usually `0x27`. If the LCD is blank, try:

```
   Address jumpers on back of I2C backpack
   ┌──────────────────────────────────────┐
   │  A0 A1 A2                            │
   │  ○  ○  ○   ← all open = 0x27        │
   │  ●  ○  ○   ← A0 closed = 0x3F       │
   └──────────────────────────────────────┘
```

If your LCD doesn't work at `0x27`, change the code to `0x3F` in the sketch.

---

### Breadboard Layout (for 1 device)

```
                         ┌─────────────────────────┐
                         │      ARDUINO UNO         │
                         │                          │
                         │  ┌─ D9 ─────────── TRIG  │
                         │  │  D10 ────────── ECHO  │
                         │  │                       │
                         │  │  A4 ──── SDA ────┐    │
                         │  │  A5 ──── SCL ─┐  │    │
                         │  │               │  │    │
                         │  │  5V ───────┐  │  │    │
                         │  │  GND ──┐   │  │  │    │
                         └──┴────────│───│──│──│────┘
                                     │   │  │  │
                   ┌─────────────────┘   │  │  │
              ┌────┴────┐               │  │  │
              │ JSN-    │               │  │  │
              │ SR04T   │               │  │  │
              │ TRANS-  │               │  │  │
              │ DUCER   │               │  │  │
              │ (on     │               │  │  │
              │ cable)  │               │  │  │
              └─────────┘               │  │  │
                                        │  │  │
                   ┌────────────────────┘  │  │
                   │              ┌────────┘  │
                   │              │           │
              ┌────┴────┐    ┌────┴────┐      │
              │ JSN-    │    │  LCD    │      │
              │ SR04T   │    │  16x2   │      │
              │ CONTROL │    │  I2C    │      │
              │ PCB     │    │ BACKPACK│      │
              │ (inside  │    └─────────┘      │
              │  box)    │                     │
              └─────────┘                     │
                                              │
              D2,D3,D4 = not connected (reserved for A7680C later)
```

---

### 3-Device Setup

```
   USB Hub (powered recommended)
   ┌──────────────────────────────────┐
   │  ┌────────┐ ┌────────┐ ┌────────┐ │
   │  │ RF01   │ │ RF02   │ │ RF03   │ │
   │  │ UNO #1 │ │ UNO #2 │ │ UNO #3 │ │
   │  │ USB    │ │ USB    │ │ USB    │ │
   │  └───┬────┘ └───┬────┘ └───┬────┘ │
   │      │          │          │       │
   └──────┼──────────┼──────────┼───────┘
          │          │          │
          └──────────┴──────────┴─────── PC running usb_bridge.py
                                                   │
                                              Wi-Fi│
                                                   ▼
                                        http://112.206.137.185:8080
                                        /pages/dashboard.php
```

Each Uno connects to the PC via USB. The Python bridge detects all 3 automatically and forwards readings to the dashboard.

---

### Reserving SIM Pins

The A7680C module (arriving in 3-4 days) uses these pins:

| Arduino Pin | A7680C Pin | Function |
|:-----------:|:----------:|----------|
| **D0 (RX)** | **TXD** | Module → Arduino (listen) |
| **D1 (TX)** | **RXD** | Arduino → Module (talk) via 1kΩ+2kΩ voltage divider |
| **D2** | **PWRKEY** | Power on (optional, auto-boot) |
| **5V** | **VCC** | Power (or 4.2V from LM2596) |
| **GND** | **GND** | Ground |

For now, leave these pins unconnected. When the A7680C arrives:

1. Remove the USB cable
2. Connect the A7680C to pins D0, D1, D2, 5V, GND
3. Upload the cellular sketch (`rice_field_monitor_air780e.ino` adapted for A7680C)
4. The Python bridge is no longer needed — data goes directly via 4G LTE

---

### Pin Reference Card (cut out)

```
┌──────────────────────────────────────┐
│ ARDUINO UNO — JSN-SR04T + LCD + SIM  │
├──────────────────────────────────────┤
│                                      │
│  D9  → JSN TRIG   (yellow)          │
│  D10 → JSN ECHO   (green)           │
│  A4  → LCD SDA    (blue)            │
│  A5  → LCD SCL    (blue/white)      │
│                                      │
│  D0/RX ← SIM TX   (reserved)        │
│  D1/TX → SIM RX   (reserved)        │
│  D2    → SIM PWR  (reserved)        │
│                                      │
│  5V   → JSN VCC, LCD VCC            │
│  GND  → JSN GND, LCD GND            │
└──────────────────────────────────────┘
```
