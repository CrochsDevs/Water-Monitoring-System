# Phase 2 — Minimal Buy List

## What the Client Must Buy to Proceed

Only the structural and power items that can't be substituted. Everything else (concrete, paint, cable ties, silicone, etc.) can be sourced locally later.

---

### Must Buy Now — 7 Items

| # | Item | Spec | Why Essential | Est. ₱ |
|---|------|------|---------------|:------:|
| 1 | **GI Pipe 3" Schedule 40** | 6m length | **The backbone** — holds the junction box, solar panel, and stilling well. Everything mounts on this. Without it, nothing stands. | ₱800 |
| 2 | **IP65 Metal Junction Box** | ~30×25×20cm | **Weatherproof housing** — protects Arduino, Air780E, regulators, LCD from rain, dust, and sun. | ₱1,200 |
| 3 | **Solar Panel 10W** | Monocrystalline, 12V | **Power source** — keeps battery charged. No panel = no standalone operation. | ₱500 |
| 4 | **PWM Charge Controller 5A** | 12V | **Battery protection** — prevents overcharging and killing the battery. Required for solar. | ₱150 |
| 5 | **Battery 12V 7AH** | Sealed lead acid | **Energy storage** — runs the system at night and on cloudy days. Without this, the system only works while the sun shines. | ₱500 |
| 6 | **Stilling Well 4" PVC Pipe** | 2m length (cut to size on-site) | **Accurate readings** — dampens water ripples that would otherwise give erratic sensor data. The difference between a working and unreliable system. | ₱150 |
| 7 | **Solar PV Cable 4mm² + Fuse + Ring Terminals** | 5m + 5A blade fuse + connectors | **Wiring** — needed to connect panel → charge controller → battery. Can't test power without wire and fuse. | ₱440 |
| | **SUBTOTAL** | | | **₱3,740** |

### Mounting Hardware — Also Buy Now

These attach everything to the pipe. Cheap but essential for assembly.

| # | Item | Qty | Est. ₱ | Search Term |
|---|------|:---:|:------:|-------------|
| 8 | **Stainless steel pipe clamps 3.5"** (90mm) | 6 | ₱150 | "304 stainless steel pipe clamp 3 inch" on Shopee |
| 9 | **Solar panel Z-bracket** or **U-bolt 3"** | 1 set | ₱150 | "solar panel z bracket adjustable tilt" or "U-bolt 3 inch galvanized" at hardware |
| 10 | **Stainless steel M6 bolts + nuts** | 8 pcs | ₱60 | "stainless steel hex bolt M6" at hardware |
| 11 | **PVC saddle clamp 4"** (for stilling well) | 3 | ₱90 | "4 inch PVC pipe clamp saddle" at hardware |
| 12 | **Small plastic tool box** (for battery) | 1 | ₱150 | Any hardware store |
| | **MOUNTING SUBTOTAL** | | **₱600** | |
| | **GRAND TOTAL** | | **₱4,340** | |

---

### Can Source Later — Not Urgent

| Item | Reason It Can Wait |
|------|--------------------|
| Concrete mix (₱150) | Can brace the pipe with rocks/stakes temporarily |
| PVC conduit 3/4" (₱150) | Tape cable to the pole temporarily |
| Cable glands (₱150) | Drill hole + silicone sealant works as temporary fix |
| Anti-rust paint (₱150) | Pipe won't rust noticeably in the first month |
| Silicone sealant (₱120) | May already have some |
| Cable ties (₱50) | May already have zip ties |
| Padlock & bolts (₱80) | Can use twist ties temporarily |
| 18 AWG wire (₱200) | Already have DuPont jumpers from Phase 1 |

---

### What You Can Already Do With Phase 1 Parts Alone

Even before Phase 2 items arrive, you can:
- ✅ Test JSN-SR04T readings (done)
- ✅ Test LCD display (done)
- ✅ Test Air780E AT commands (baud change to 9600)
- ✅ Test HTTP POST to dashboard (via USB bridge to laptop)
- ✅ Upload full sketch and verify all logic
- ✅ Simulate water level changes with your hand

### What Phase 2 Unlocks

Once these 7 items arrive:
- ✅ System runs completely standalone (no USB to PC)
- ✅ Solar panel charges battery automatically
- ✅ Data posts to dashboard via 4G LTE every 5 minutes
- ✅ SMS alerts sent if thresholds are breached
- ✅ Can deploy in the field (just need concrete + pipe bracing)
