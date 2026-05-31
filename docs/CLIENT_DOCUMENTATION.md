# JSN-SR04T Water Level Monitor — Device Specification & Deployment Guide

## For Client Documentation & Research Paper

---

## 1. System Overview

A solar-powered, standalone IoT system that continuously monitors water level in rice fields, displays it locally on an LCD, and transmits data via SMS (GSM cellular network). Designed for off-grid agricultural deployment with zero external power dependency.

**Sensor:** JSN-SR04T Waterproof Ultrasonic (IP67)
**Controller:** Arduino Uno R3
**Communication:** SIM800L GSM Module (SMS)
**Power:** 100W Solar Panel + 12V 20AH Battery

---

## 2. Water Level Measurement Principle

### How the Sensor Works

The JSN-SR04T is an **ultrasonic distance sensor** mounted at the top of a stilling well, **above the water**, facing downward. It measures water level indirectly:

```
    [JSN-SR04T Transducer]  ← Mounted at the top of the stilling well
         |                        (ALWAYS dry, never submerged)
         |
         ▼  )) 40kHz ultrasonic pulse travels DOWN through air
         |
    ════════════════════════  ← Water surface (reflects sound)
         |
         ↑  (( Echo bounces back UP
         |
    [Sensor receives echo]

    MEASURED DISTANCE = Time-of-flight × Speed of Sound ÷ 2
                      = The air gap between sensor and water surface
```

The sensor **does not touch water**. It measures the air gap, then the Arduino calculates the actual water depth:

```
    WATER DEPTH = TOTAL WELL DEPTH - MEASURED AIR GAP
    
    Example:
      Well depth (sensor to field bottom) = 50cm
      Sensor measures air gap = 35cm
      Water depth = 50cm - 35cm = 15cm
```

### Why This Works

- Water surface is an **excellent acoustic reflector** — flat and dense
- Ultrasonic level sensing is standard in tanks, wells, and industrial applications
- The stilling well (4" PVC pipe) dampens surface ripples for stable readings
- The sensor has a **25cm minimum distance** (blind zone) — the well is designed so water never enters this zone

---

## 3. Stilling Well Design

### Purpose

The stilling well is the most critical mechanical component. It:
1. **Dampens water surface ripples** from wind/irrigation
2. **Protects the sensor** from debris and direct sun
3. **Creates a stable measurement surface** for accurate readings
4. **Houses the transducer** in a controlled environment

### Construction

| Component | Specification |
|-----------|--------------|
| Material | PVC Pipe, Schedule 40 |
| Diameter | 4 inches (100mm) |
| Color | White or gray (standard PVC) |
| Drainage holes | 5mm diameter, every 10cm on lower section |
| Top cap | Rubber seal with cable entry gland |
| Mounting | Strapped to GI post with stainless steel clamps |

### How to Determine Well Length

The well length depends on your field's maximum expected water depth:

```
    ┌──────────────────────────────────┐
    │  JSN-SR04T Transducer            │
    │                                  │
    │  ← 25cm BLIND ZONE               │  ← Sensor cannot read closer than 25cm
    │     (keep water away from here)  │
    │                                  │
    │  ← 10cm SAFETY MARGIN            │  ← Extra room for unexpected high water
    │                                  │
    ════════════════════════════════════  ← MAXIMUM EXPECTED WATER LEVEL
    │                                  │
    │  ← ACTUAL WATER DEPTH ZONE       │  ← Normal operating range (0cm to max)
    │     (where water fluctuates)     │
    │                                  │
    ════════════════════════════════════  ← Field bottom / well bottom
```

**Formula:**

```
    Well Length = Max Expected Water Depth + 10cm Safety Margin + 25cm Blind Zone

    Also ensure: Max Expected Water Depth < (Well Length - 35cm)
```

### Reference Table

| Field Type | Max Water Depth | Well Length | FIELD_DEPTH_CM in Code | Sensor Distance at Dry | Sensor Distance at Max Water |
|------------|:--------------:|:-----------:|:---------------------:|:---------------------:|:---------------------------:|
| Shallow paddy | 15cm | **50cm** | 50.0 | 50cm | 35cm |
| Medium paddy | 30cm | **65cm** | 65.0 | 65cm | 50cm |
| Deep field | 50cm | **85cm** | 85.0 | 85cm | 70cm |
| Flood-prone | 100cm | **135cm** | 135.0 | 135cm | 120cm |
| Canal/drainage | 150cm | **185cm** | 185.0 | 185cm | 170cm |

### Verification Check

After determining your well length, verify the sensor can always read accurately:

| Check | Calculation | Must Be True |
|-------|------------|:------------:|
| Sensor can measure empty field | Well Length >= 25cm | ✅ Always true for these lengths |
| Sensor can measure at max water | Well Length - Max Depth >= 25cm | ✅ (we added 10cm safety + 25cm blind zone) |
| Water never reaches sensor | Max Depth + Safety Margin <= Well Length - 25cm | ✅ |

---

## 4. Physical Deployment Layout

### Side View (Not to Scale)

```
                    ☀️  ← 100W Solar Panel, facing South, ~14° tilt
                    |
   ┌────────────────────┐
   │   IP65 JUNCTION    │  ← Bolted to GI post at chest height
   │   BOX              │     Contains: Arduino, SIM800L, LCD,
   │   (30×25×20cm)     │     LM2596 regulators, capacitor, battery monitor
   │                    │
   │   [LCD display]    │  ← Visible through cutout in box door
   └─────────┬──────────┘
             │  ← JSN-SR04T cable (2-3m) in PVC conduit
             │    routed down the GI pipe
             │
   ══════════╪═══════════════════════  ← Ground level (rice field mud)
             │
      ┌──────┴────────────────┐
      │  GI PIPE 3" (steel)   │  ← 6m length
      │  (Mounting post)      │
      │                       │
      │  ┌──────────────────┐ │
      │  │ STILLING WELL    │ │  ← 4" PVC pipe, strapped to GI pipe
      │  │                  │ │     with stainless steel clamps
      │  │  ┌──────────┐   │ │
      │  │  │ JSN-SR04T│   │ │  ← Transducer at top of well, DRY, facing DOWN
      │  │  │ TRANSD.  │   │ │     Epoxy-potted, IP67
      │  │  └────┬─────┘   │ │
      │  │       ▼         │ │
      │  │  ═══════════════ │ │  ← Water surface inside the well
      │  │  ║  ║  ║  ║ ║  │ │     (drainage holes at bottom let water in)
      │  │  ║  ║  ║  ║ ║  │ │
      │  └──────────────────┘ │
      │                       │
      │  ┌────────────────┐   │
      │  │ CONCRETE       │   │  ← 40kg bag, 2-3ft below ground
      │  │ FOOTING        │   │     Let cure 48 hours before loading
      │  └────────────────┘   │
      └───────────────────────┘
```

### Where Each Component Lives

| Component | Location | Environment |
|-----------|----------|:-----------:|
| Arduino Uno R3 | Inside IP65 junction box | Dry |
| SIM800L GSM Module | Inside IP65 junction box | Dry |
| LM2596 Step-Down Regulators (×2) | Inside IP65 junction box | Dry |
| LCD 16×2 Display | Inside IP65 junction box door | Dry (visible) |
| JSN-SR04T Control PCB | Inside IP65 junction box | Dry |
| JSN-SR04T Transducer | Inside stilling well, above water | **Splash-proof only** (never submerged) |
| JSN-SR04T Cable (2-3m) | Inside PVC conduit along GI pipe | Protected |
| GSM Antenna (3dBi) | On top of or beside junction box | Outdoor |
| Solar Panel (100W) | Above box on GI post, South-facing | Outdoor |
| PWM Solar Charge Controller (10A) | Inside junction box or externally | Dry |
| Lead-Acid Battery (12V 20AH) | Ground-level enclosure or buried | Weatherproof box |
| Stilling Well (4" PVC) | Strapped to GI pipe, extends into water | Semi-submerged (bottom only) |
| GI Pipe (3") Post | Cemented 2-3ft into ground | Outdoor |
| Concrete Footing (40kg) | Below ground | Buried |

---

## 5. Device Configuration for 3 Units

For a deployment of 3 monitoring devices, each unit has its own configuration:

| Parameter | Device 1 (RF01) | Device 2 (RF02) | Device 3 (RF03) |
|-----------|:---------------:|:---------------:|:---------------:|
| DEVICE_ID | RF01 | RF02 | RF03 |
| Location | [Field location A] | [Field location B] | [Field location C] |
| Well Depth (FIELD_DEPTH_CM) | Per field measurement | Per field measurement | Per field measurement |
| Alert High (ALERT_HIGH_CM) | 15cm | 15cm | 15cm |
| Alert Low (ALERT_LOW_CM) | 2cm | 2cm | 2cm |
| Farmer Phone (ALERT_PHONE) | +639XXXXXXXXX | +639XXXXXXXXX | +639XXXXXXXXX |
| SIM Card | Globe/Smart prepaid | Globe/Smart prepaid | Globe/Smart prepaid |

Each unit is **independent** — it has its own Arduino, its own SIM card, its own solar power, and its own stilling well. They communicate directly with the farmer's phone via SMS. No central server is required for basic operation.

---

## 6. Data Format (for Dashboard Integration)

When the Arduino sends water level data, the SMS message follows this format:

```
Rice Field RF01
Water Level: 15.0 cm
Distance: 35.0 cm
Battery: 12.4V
Signal: 18/31
ALERT: Water level HIGH!
5d uptime
```

For web dashboard integration, the Arduino can alternatively send HTTP POST to a server:

```json
{
  "device_id": "RF01",
  "water_level_cm": 15.0,
  "distance_cm": 35.0,
  "battery_v": 12.4,
  "signal": 18,
  "uptime_days": 5,
  "alert": "high"
}
```

The web dashboard would:
- Store these readings in a database
- Display real-time water levels for all 3 devices
- Show historical charts (Chart.js)
- Send email/push notifications for alerts
- Manage user accounts and device registration

---

## 7. Power Budget (Per Unit)

| Component | Avg Current | Duty Cycle | Avg Power |
|-----------|:-----------:|:----------:|:---------:|
| Arduino Uno | 50mA | 100% | 0.6W |
| JSN-SR04T | 15mA | 0.1% (pulsed) | ~0.01W |
| SIM800L | 20mA idle / 2A burst | 2% (TX every 30min) | ~0.5W avg |
| LCD 16×2 | 50mA | 50% (with sleep) | 0.3W |
| **TOTAL** | | | **~1.4W average** |

- **Battery runtime** (no sun): ~7 days
- **Solar recovery**: 2-3 good sun days after overcast weather
- System runs indefinitely in normal conditions

---

## 8. Bill of Materials (Per Unit)

| Category | Items | Est. Cost (₱) |
|----------|-------|:-------------:|
| Power System | 100W solar panel, charge controller 10A, battery 12V 20AH | ₱4,650 |
| Power Regulation | 2× LM2596 step-down (5V + 4.2V) | ₱240 |
| Controller & Sensor | Arduino Uno R3 + JSN-SR04T waterproof sensor | ₱630 |
| Communication | SIM800L + GSM antenna + SIM card + load | ₱550 |
| Display | LCD 16×2 with I2C backpack | ₱280 |
| Enclosure & Mounting | IP65 junction box, GI pipe 3", concrete, cable glands, PVC conduit, stilling well | ₱2,750 |
| Wiring & Connectors | Solar cable, 18 AWG wire, DuPont jumpers, capacitor, resistors, fuse, terminals | ₱835 |
| Consumables & Tools | Silicone, cable ties, paint, bolts, multimeter, soldering iron | ₱720 |
| **TOTAL PER UNIT** | | **~₱10,655** |

**For 3 units:** ~₱31,965 total (bulk discounts may apply)

---

## 9. Deployment Checklist (Per Unit)

### Bench Testing (Lab)
- [ ] Assemble Arduino + JSN-SR04T on breadboard
- [ ] Upload sketch, verify serial output
- [ ] Test SIM800L AT commands via serial monitor
- [ ] Test SMS sending/receiving (STATUS, LEVEL, BATTERY commands)
- [ ] Test LCD display
- [ ] Calibrate: known water height vs sensor reading
- [ ] Run continuous test for 24 hours

### Integration
- [ ] Assemble in IP65 junction box
- [ ] Connect battery + charge controller
- [ ] Verify LM2596 outputs: 5.00V ±0.05V and 4.20V ±0.05V
- [ ] Test with 12V power supply
- [ ] Verify battery voltage readings (A0 divider)
- [ ] Run 48-hour test, log all readings

### Field Deployment
- [ ] Dig hole (2-3ft deep) at field edge
- [ ] Install GI pipe with concrete footing (cure 48 hours)
- [ ] Mount junction box to post
- [ ] Mount solar panel (South-facing, ~14° tilt)
- [ ] Drill drainage holes in stilling well bottom
- [ ] Mount JSN-SR04T transducer inside stilling well, facing down
- [ ] Route cables through PVC conduit → junction box
- [ ] Wire solar panel → charge controller (observe polarity)
- [ ] Insert SIM card with valid load
- [ ] Power on system, verify boot sequence
- [ ] Send test SMS to farmer's phone
- [ ] Verify readings at several known water levels
- [ ] Mark GPS coordinates of installation

### Post-Deployment Verification
- [ ] Check system after 24 hours
- [ ] Check system after 72 hours
- [ ] Verify battery voltage stays above 12V overnight
- [ ] Confirm scheduled SMS transmissions (every 30 min)
- [ ] Compare readings during irrigation/rain events

---

## 10. Configuration Constants (Arduino Sketch)

The following parameters must be set per device before deployment:

```cpp
// In rice_field_monitor_jsn_sr04t.ino:

const float FIELD_DEPTH_CM      = 50.0;    // CHANGE THIS: your well length in cm
const float ALERT_HIGH_CM       = 15.0;    // Alert if water exceeds this
const float ALERT_LOW_CM        = 2.0;     // Alert if water drops below this

const char ALERT_PHONE[]        = "+639XXXXXXXXX";  // CHANGE THIS: farmer's number
const String DEVICE_ID          = "RF01";           // CHANGE THIS: unique per device

const unsigned long MEASURE_INTERVAL = 300000;   // 5 min between readings
const unsigned long TX_INTERVAL      = 1800000;  // 30 min between SMS sends
```

---

*Document prepared for client review and research paper documentation.*
*Project: Automated Rice Field Water Level Monitoring System*
*Sensor: JSN-SR04T (IP67 Waterproof Ultrasonic)*
*May 2026*
