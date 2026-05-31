# Phase 1 — Bench Testing Protocol

## Component-by-Component Testing for JSN-SR04T Water Level Monitor

> **Goal:** Verify every component works individually before full integration.
> **Power:** USB from PC (Arduino) + 12V power supply or battery (for LM2596/SIM800L tests)
> **Tools needed:** Multimeter, USB cable, jumper wires

---

## Step 1: Test the Arduino Board

**Why first:** If the board is dead, nothing else matters.

1. Plug Arduino into PC via USB
2. Open **Arduino IDE**
3. Go to **File > Examples > 01.Basics > Blink**
4. Click **Upload** (right-arrow button)
5. Watch the board — the built-in LED (labeled "L" or pin 13) should blink once per second

**Result:**

| Observation | Meaning | Next Step |
|-------------|---------|-----------|
| LED blinks | Board works ✅ | Proceed to Step 2 |
| LED doesn't blink | Board or USB cable issue | Try different USB cable, different USB port |
| Upload fails (avrdude error) | Wrong board/port selected | Check Tools > Board = "Arduino Uno", Tools > Port = correct COM port |

---

## Step 2: Set Up the LM2596 Regulators

**Why this order:** You MUST set the correct output voltages **before** connecting them to sensitive electronics. Wrong voltage kills components.

### Setting the 5V Regulator

```
   [12V Battery/Power Supply]
        + ──── LM2596 IN+
        - ──── LM2596 IN-
               LM2596 OUT+ ──── Multimeter (red lead)
               LM2596 OUT- ──── Multimeter (black lead)
```

1. Connect LM2596 #1 input to 12V battery/power supply
2. Connect multimeter to LM2596 output (red to OUT+, black to OUT-)
3. Turn the tiny screw on the blue trimmer potentiometer with a small screwdriver
4. Watch the multimeter until it reads **exactly 5.00V**
5. Label this regulator **"5V — for Arduino + LCD + Sensor"** (use tape or sticker)

### Setting the 4.2V Regulator

1. Repeat the same process with LM2596 #2
2. Adjust trimmer until multimeter reads **exactly 4.20V**
3. Label this regulator **"4.2V — for SIM800L only"**

### Verification

| Regulator | Target | If too high | If too low |
|-----------|:------:|-------------|------------|
| LM2596 #1 | **5.00V ±0.05V** | Turn screw **counter-clockwise** | Turn screw **clockwise** |
| LM2596 #2 | **4.20V ±0.05V** | Turn screw **counter-clockwise** | Turn screw **clockwise** |

> ⚠️ **Never power SIM800L from the 5V regulator.** It needs 4.2V max. 5V+ will damage it over time.

---

## Step 3: Test the JSN-SR04T Sensor

**Why this order:** Simplest sensor to wire and test. Gives you confidence in the ultrasonic measurement before adding complexity.

### Wiring

```
   JSN-SR04T (4-pin header)          Arduino Uno
   ──────────────────────────────────────────────
   VCC  (red wire)       ──→   5V (from USB)
   TRIG (yellow wire)    ──→   D9
   ECHO (green wire)     ──→   D10
   GND  (black wire)     ──→   GND
```

**Note:** The JSN-SR04T has two parts — a small control PCB (4-pin header) and the transducer on a 2-3m cable. Connect the 4-pin header to Arduino. The transducer is the potted epoxy tip at the other end of the cable.

### Upload Test Sketch

In Arduino IDE, **File > New**, paste this, then **Upload**:

```cpp
// JSN-SR04T Quick Test
#define TRIG 9
#define ECHO 10

void setup() {
  Serial.begin(9600);
  pinMode(TRIG, OUTPUT);
  pinMode(ECHO, INPUT);
}

void loop() {
  digitalWrite(TRIG, LOW);
  delayMicroseconds(2);
  digitalWrite(TRIG, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG, LOW);

  unsigned long duration = pulseIn(ECHO, HIGH, 30000);

  if (duration == 0) {
    Serial.println("No echo — check wiring");
  } else {
    float distance = (duration * 0.0343) / 2.0;
    Serial.print("Distance: ");
    Serial.print(distance);
    Serial.println(" cm");
  }
  delay(500);
}
```

### Test

1. Open **Serial Monitor** (Tools > Serial Monitor or Ctrl+Shift+M)
2. Set baud rate to **9600** (bottom-right of Serial Monitor)
3. Point the transducer at different surfaces:

| Test | Expected Reading |
|------|-----------------|
| Point at open space (>4m) | "No echo" (out of range) |
| Point at wall 50cm away | ~50cm (±2cm) ✅ |
| Point at floor 100cm away | ~100cm (±2cm) ✅ |
| Place hand 10cm from transducer | Reading will be erratic (<25cm = blind zone — this is normal) |
| Point at water surface in a bucket | Clean reading (water reflects ultrasound very well) |

### Troubleshooting

| Symptom | Likely Cause |
|---------|-------------|
| Always reads "No echo" | Wiring wrong — check TRIG/ECHO pins. Or transducer damaged |
| Reading is half/double actual | Wrong speed of sound constant — our code uses 343 m/s which is correct |
| Reading jumps ±10cm | Surface is angled or rippled — this is why we use a stilling well |
| Reading is 0.00cm | No echo received within timeout — check cable connection between PCB and transducer |

---

## Step 4: Test the LCD 16×2 I2C

### Install Library

1. Arduino IDE: **Tools > Manage Libraries**
2. Search for **"LiquidCrystal I2C"**
3. Install the one by **Frank de Brabander**

### Wiring

```
   LCD Backpack (I2C)             Arduino Uno
   ──────────────────────────────────────────────
   VCC                    ──→   5V
   GND                    ──→   GND
   SDA                    ──→   A4
   SCL                    ──→   A5
```

### Upload Test Sketch

```cpp
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// Try 0x27 first. If blank, change to 0x3F.
LiquidCrystal_I2C lcd(0x27, 16, 2);

void setup() {
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("Water Monitor");
  lcd.setCursor(0, 1);
  lcd.print("JSN-SR04T OK");
}

void loop() {}
```

### If Display is Blank

1. Try address **0x3F** instead of 0x27 in the code
2. Run an I2C scanner sketch to detect the address:

<details>
<summary><b>Click to expand: I2C Scanner Sketch</b></summary>

```cpp
#include <Wire.h>
void setup() {
  Serial.begin(9600);
  Wire.begin();
  Serial.println("Scanning...");
}
void loop() {
  for (byte addr = 1; addr < 127; addr++) {
    Wire.beginTransmission(addr);
    if (Wire.endTransmission() == 0) {
      Serial.print("Found at: 0x");
      Serial.println(addr, HEX);
    }
  }
  delay(3000);
}
```
</details>

---

## Step 5: Test the SIM800L GSM Module

**⚠️ CRITICAL SAFETY RULES:**
- **ALWAYS** connect the GSM antenna before powering the module — operating without antenna can permanently damage the RF amplifier
- **DO NOT** connect SIM800L VCC directly to 5V or to the Arduino 5V pin — it runs on 4.2V
- The module draws up to **2A peaks** during transmission — the **1000µF capacitor** is mandatory

### Build the Voltage Divider (for Arduino TX → SIM800L RX)

The Arduino runs at 5V logic, but the SIM800L runs at 3.3V logic. You MUST reduce the voltage:

```
   Arduino D1 (TX) ──┬── 1kΩ resistor ──┬── SIM800L RX
                     │                  │
                     └── 2kΩ resistor ──┴── GND
```

Solder these resistors together or use a small breadboard. This creates a voltage divider: 5V → 3.3V.

### Wiring

```
   SIM800L Module                Connection
   ───────────────────────────────────────────────────────────────
   VCC                   ──→   LM2596 #2 (4.2V output)
   GND                   ──→   Common ground
   TX                    ──→   Arduino D0 (RX) — direct wire (3.3V is safe for Arduino)
   RX                    ──→   Via voltage divider from Arduino D1 (TX)
   PWRKEY                ──→   Arduino D4 (optional)
   ANT                   ──→   External GSM antenna (MANDATORY!)
```

```
   1000µF Capacitor (for SIM800L power smoothing)
   ────────────────────────────────────────────────
   + side (long leg)    ──→   SIM800L VCC (as close to module as possible)
   - side (short leg)   ──→   SIM800L GND
```

### Upload Test Sketch

```cpp
#include <SoftwareSerial.h>

SoftwareSerial gsm(2, 3);  // RX = D2, TX = D3

void setup() {
  Serial.begin(9600);
  gsm.begin(9600);
  Serial.println("SIM800L Ready — type AT commands");
}

void loop() {
  if (gsm.available()) Serial.write(gsm.read());
  if (Serial.available()) gsm.write(Serial.read());
}
```

### Test Procedure

Open Serial Monitor. Type these commands one at a time, pressing Enter after each:

| Command | Expected Response | What It Tests | If It Fails |
|---------|------------------|---------------|-------------|
| `AT` | `OK` | Module is alive | Check 4.2V power, antenna, wiring |
| `AT+CSQ` | `+CSQ: 12,0` | Signal strength (0-31) | Move antenna near window, check coverage |
| `AT+CCID` | A 20-digit number | SIM card detected | Check SIM is inserted properly |
| `AT+CREG?` | `+CREG: 0,1` | Registered on network | Check SIM has load, try different location |
| `AT+CMGF=1` | `OK` | SMS text mode | Module should accept this |

### If SIM800L Doesn't Respond

| Symptom | Likely Cause | Fix |
|---------|-------------|-----|
| No response at all | Power issue | Measure voltage at SIM800L VCC/GND — must be 4.2V |
| Module gets hot | Over-voltage | You're feeding 5V — check LM2596 output |
| Garbled text | Baud rate mismatch | Try `gsm.begin(115200)` instead of 9600 |
| "AT" returns "AT" (echo) | Echo is on | Type `ATE0` then Enter, then `AT` again |
| Connects then resets | Current spike, weak capacitor | Move 1000µF cap closer to SIM800L pins |

### Send a Test SMS

Once `AT+CMGF=1` returns OK, send an SMS:

1. Type: `AT+CMGS="+639XXXXXXXXX"` (your phone number, with country code)
2. Wait for `>` prompt
3. Type: `Test SMS from Rice Monitor RF01`
4. Hold **Ctrl** and press **Z** (this sends Ctrl+Z = 0x1A)
5. Wait 5-10 seconds. Should show `+CMGS: XXX` and `OK`

Check your phone — you should receive the SMS.

### SMS Command Test

Once the full sketch is uploaded later, you can test commands by texting the SIM number:

| Text This | Expected Reply |
|-----------|---------------|
| `STATUS` | `RF RF01: Level=15.2cm, Bat=12.4V, Signal=18/31` |
| `LEVEL` | Same as STATUS |
| `BATTERY` | `Battery: 12.4V OK` |
| `HELP` | `Commands: STATUS, LEVEL, BATTERY, HELP` |

---

## Step 6: Full Integration Test

Once every component passes individually, wire them all together and upload the full sketch.

### Wiring Diagram (Complete)

```
   COMPONENT                    ARDUINO PIN
   ──────────────────────────────────────────────────
   LM2596 #1 (5V) OUT+    ──→   Arduino VIN (or 5V pin)
   LM2596 #2 (4.2V) OUT+  ──→   SIM800L VCC (+ 1000µF cap)
   Ground (common)         ──→   All GND connections
   
   JSN-SR04T VCC           ──→   Arduino 5V
   JSN-SR04T TRIG          ──→   D9
   JSN-SR04T ECHO          ──→   D10
   JSN-SR04T GND           ──→   GND
   
   SIM800L TX              ──→   D0 (RX)
   SIM800L RX              ──→   Via voltage divider ← D1 (TX)
   SIM800L PWRKEY          ──→   D4
   SIM800L GND             ──→   GND
   SIM800L ANT             ──→   External GSM antenna
   
   LCD SDA                 ──→   A4
   LCD SCL                 ──→   A5
   LCD VCC                 ──→   Arduino 5V
   LCD GND                 ──→   GND
   
   A0 (optional)           ──→   Battery voltage divider (leave unconnected for bench test)
```

### Before Uploading

Edit these lines in `rice_field_monitor_jsn_sr04t.ino`:

```cpp
const float FIELD_DEPTH_CM = 50.0;       // Change to your test pipe/container depth
const char ALERT_PHONE[]   = "+639XXXXXXXXX";  // Your phone number
const String DEVICE_ID     = "RF01";            // Label for this unit
```

### Power On Sequence

1. Connect Arduino USB (for Serial Monitor + power to LCD/sensor)
2. Connect 12V battery to BOTH LM2596 regulators
3. Wait for boot sequence in Serial Monitor

### Expected Boot Output

```
===================================
Rice Field Water Level Monitor v1.0
--- JSN-SR04T Water-Only Edition ---
===================================
[GSM] Powering up...
[GSM] Initializing...
[GSM] Signal RSSI: 15
[GSM] Registered on network
[GSM] Ready
[MEASURE] Distance: 35.2 cm | Water Level: 14.8 cm
```

### LCD Should Display

```
Line 1: Water: 14.8cm
Line 2: Bat: 5.0V GSM:15
```

### Verification Checklist

| Check | How to Test | Pass Criteria |
|-------|-------------|:------------:|
| JSN-SR04T reads correctly | Hold cardboard at known distance | Reading ≤ ±2cm of actual distance |
| LCD updates every 1 second | Watch display change | Numbers refresh every second |
| LCD shows correct values | Compare Serial Monitor to LCD | Same values on both |
| GSM registers on network | Wait 10-15s after boot | Shows "GSM Ready" and signal number |
| SMS command: STATUS | Text STATUS to SIM number | Receive reply with water level |
| SMS command: BATTERY | Text BATTERY | Receive battery voltage |
| Automatic SMS alert | Hold object under sensor to simulate LOW water | Receive SMS within 30 min (TX_INTERVAL) |
| Serial commands work | Type "M" in Serial Monitor | Triggers immediate measurement |

---

## Common Issues & Quick Fixes

| Issue | Possible Cause | Fix |
|-------|---------------|-----|
| Arduino resets when GSM sends | 1000µF cap too far from SIM800L | Move cap to within 2cm of SIM800L VCC/GND |
| No I2C LCD display | Wrong address | Change `0x27` to `0x3F` in the code |
| LCD shows blocks/squares | Contrast not set | Turn the small screw on the back of the I2C backpack |
| JSN-SR04T reads 0 | Sensor in blind zone | Move sensor so distance to surface > 25cm |
| JSN-SR04T reads erratic | Loose connection | Check jumper wires are fully seated |
| SIM800L won't register | Weak signal or no load | Move antenna near window, check SIM has load |
| No SMS received | SIM out of load | Add load via GCash or load retailer |
| Battery reads 0.0V | A0 not connected | Normal on bench — leave A0 floating or connect to 5V via voltage divider |

---

## Full System Test (24-Hour Run)

After all checks pass, run the system continuously for 24 hours:

1. Set up a test container (bucket or pipe) with water
2. Mount JSN-SR04T at top of test pipe facing down
3. Let the system run on USB + 12V power
4. Check every few hours:
   - LCD still shows correct water level
   - SMS commands still respond
   - No unexpected resets
5. After 24h, verify all readings were logged (check debug output)

### Pass/Fail Criteria

| Criteria | Result |
|----------|:------:|
| All components pass individual tests | ✅ Ready for Phase 2 (solar + battery) |
| Any component fails | ❌ Replace that component before proceeding |
| System runs 24h without crash | ✅ Ready for field deployment |
| System resets or hangs within 24h | ❌ Debug power issue (likely GSM current spike) |

---

*Document revision: May 2026*
*Project: JSN-SR04T Automated Rice Field Water Level Monitoring System*
