# ESP32 Water Level Monitor — Wiring Diagram (WiFi Mode)

## Pin Layout

```
ESP32 Dev Board (30-pin)
┌─────────────────────────────────────────────────────┐
│                                                     │
│  GND   ── JSN GND, LCD GND                          │
│  3.3V  ── LCD VCC (optional, 5V also works)         │
│  5V    ── JSN VCC (powers the sensor)              │
│                                                     │
│  D9    ── JSN TRIG                                   │
│  D10   ── JSN ECHO                                   │
│                                                     │
│  D21   ── LCD SDA                                    │
│  D22   ── LCD SCL                                    │
│                                                     │
│  USB   ── PC (for programming & serial monitor)      │
└─────────────────────────────────────────────────────┘
```

## Quick Reference

| Component | ESP32 Pin | Wire Color |
|-----------|:---------:|:----------:|
| **JSN-SR04T VCC** | **5V** | Red |
| **JSN-SR04T GND** | **GND** | Black |
| **JSN-SR04T TRIG** | **D9** | Blue |
| **JSN-SR04T ECHO** | **D10** | Green |
| **LCD VCC** | **3.3V or 5V** | Red |
| **LCD GND** | **GND** | Black |
| **LCD SDA** | **D21** | Yellow |
| **LCD SCL** | **D22** | White |

## Notes

- JSN-SR04T works at **5V** — ESP32 5V pin powers it safely
- LCD I2C works at **3.3V or 5V** — either works with ESP32
- I2C address is usually **0x27** or **0x3F** — run I2C scanner if LCD doesn't show text
- No voltage dividers needed — ESP32 is 3.3V logic but 5V tolerant on D9/D10
