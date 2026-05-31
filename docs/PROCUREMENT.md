# JSN-SR04T Water Monitor — Must-Buy Items
## Phased procurement: test first, power second, field-deploy last

---

## Phase 1 — Bench Testing
Buy these first. Get everything running on a table with USB or a 12V power supply.

| # | Item | Qty | Est. ₱ | Purpose |
|---|------|:---:|:------:|---------|
| 1 | **Arduino Uno R3** | 1 | ₱450 | The brain — runs the code |
| 2 | **JSN-SR04T** | 1 | ₱180 | Waterproof ultrasonic sensor |
| 3 | **SIM800L GSM Module** | 1 | ₱250 | Sends SMS alerts |
| 4 | **GSM Antenna 3dBi SMA** | 1 | ₱150 | Required — SIM800L can be damaged without it |
| 5 | **LM2596 Step-Down** 12V→5V, 3A | 1 | ₱120 | Powers Arduino + LCD + sensor |
| 6 | **LM2596 Step-Down** 12V→4.2V, 3A | 1 | ₱120 | Powers SIM800L (dedicated regulator) |
| 7 | **LCD 16×2 with I2C Backpack** | 1 | ₱280 | Shows water level locally |
| 8 | **1000µF 25V Capacitor** | 2 | ₱30 | Stops SIM800L from browning out |
| 9 | **1kΩ + 2kΩ Resistors** | few | ₱10 | Voltage divider (5V → 3.3V for SIM800L) |
| 10 | **DuPont Jumper Wires** M-M + F-M (optional — can strip solid wire instead) | 2 packs | ₱80 | Breadboard connections |
| 11 | **USB Cable for Arduino** (optional — you may already have micro USB) | 1 | ₱50 | Programming the board |
| 12 | **SIM Card + Initial Load** (Globe/Smart) | 1 | ₱150 | For SMS alerts |
| 13 | **Digital Multimeter** (optional — you already have) | — | — | For setting LM2596 voltages correctly |
| 14 | **Soldering Iron + Solder** (optional — you already have) | — | — | For voltage divider + permanent connections |
| | **PHASE 1 TOTAL** | | **~₱1,840** (₱2,470 if buying everything) | |

---

## Phase 2 — Power System (Finalized)
Make it solar-powered and standalone. 
**Revised with 10W panel — saves ₱3,600 per device vs original 100W plan.**

| # | Item | Qty | Est. ₱ | Purpose | Source |
|---|------|:---:|:------:|---------|--------|
| 15 | **Solar Panel 10W** monocrystalline, 18V Voc | 1 | ₱500 | Primary power source (10W is sufficient — see power budget below) | [Lazada](https://www.lazada.com.ph/tag/solar-panel-10-watts/) |
| 16 | **PWM Solar Charge Controller 5A** 12V | 1 | ₱150 | Regulates charging (5A matches 10W panel) | [Shopee](https://shopee.ph/search?keyword=solar+charge+controller+5a) |
| 17 | **Battery 12V 7AH Sealed Lead Acid** | 1 | ₱500 | Night/cloudy day power (7AH = 5+ days autonomy) | [Shopee](https://shopee.ph/search?keyword=12v+7ah+sealed+battery) |
| 18 | **5A Blade Fuse + Holder** | 1 | ₱40 | Short circuit protection | — |
| 19 | **Solar PV Cable 4mm²** 5m | 1 | ₱300 | Panel to charge controller | [Shopee](https://shopee.ph/search?keyword=solar+cable+4mm2) |
| 20 | **18 AWG Wire Red+Black** 5m each | 2 rolls | ₱200 | 12V distribution wiring | Hardware |
| 21 | **Ring Terminals + Wire Nuts + Heat Shrink** | 1 set | ₱100 | Battery/post connections | Hardware |
| | **PHASE 2 TOTAL** | | **~₱1,790** | *(down from ₱5,290 with 100W plan)* | |

### Power Budget (Why 10W is Enough)

| Component | Power | Duty | Avg Power |
|-----------|:-----:|:----:|:---------:|
| Arduino Uno | 0.25W | 100% | 0.25W |
| LCD 16×2 I2C | 0.25W | 100% | 0.25W |
| JSN-SR04T | 0.075W | ~0.1% | ~0.0001W |
| Air780E (idle) | 0.084W | ~98% | 0.082W |
| Air780E (TX, 5s/5min) | 0.84W | ~2% | 0.017W |
| LM2596 losses ~15% | — | — | ~0.09W |
| **Total** | | | **~0.69W avg** |

| Metric | Value |
|--------|:-----:|
| Daily consumption | 16.6 Wh |
| **10W panel generates** (PH avg 4.5h × 0.7 derating) | **31.5 Wh/day** |
| Surplus for battery charging | 15 Wh/day |
| Battery 7AH at 12V | 84 Wh capacity |
| Autonomy with zero sun | **5+ days** |
| Even on 3 overcast days (30% output) | System breaks even |

---

## Phase 3 — Field Mounting

Deploy it in an actual rice field. Stilling well length will be determined on-site.

| # | Item | Qty | Est. ₱ | Purpose |
|---|------|:---:|:------:|---------|
| 22 | **Metal Junction Box IP65** ~30×25×20cm | 1 | ₱1,200 | Weatherproof enclosure |
| 23 | **GI Pipe 3" Schedule 40** 6m | 1 | ₱800 | Mounting post |
| 24 | **Concrete Mix 20kg** (half bag) | 1 | ₱150 | Post footing |
| 25 | **Cable Glands PG9/PG11/PG16** 5-8 pcs (optional — can seal with silicone) | 1 | ₱150 | Waterproof cable entry |
| 26 | **PVC Conduit 3/4"** 3m (optional — tape cable to post) | 1 | ₱150 | Protect sensor + antenna cables |
| 27 | **Stilling Well 4" PVC Pipe** 2m (cut to length on-site) | 1 | ₱150 | Dampens ripples for stable readings |
| 28 | **Silicone Sealant Clear** (optional — may have one already) | 1 | ₱120 | Seal everything |
| 29 | **Cable Ties UV Black** (optional — may have zip ties) | 1 pack | ₱50 | Cable management |
| | **PHASE 3 TOTAL** | | **~₱2,770** | |

**Note:** Stilling well height depends on field visit. Formula: 
> Well length = Max expected water depth + 25cm blind zone + 10cm safety margin
> 
> *Example: 15cm max depth → 50cm well | 30cm max depth → 65cm well*
> 
> Buy 2m of 4" PVC pipe and cut to size on-site.

---

## Grand Total (Per Device)

| Phase | Cost |
|-------|:----:|
| Phase 1: Bench Testing | ~₱1,840 |
| Phase 2: Power System | ~₱1,790 |
| Phase 3: Field Mounting | ~₱2,770 |
| **TOTAL PER DEVICE** | **~₱6,400** |
| × 3 Devices | **~₱19,200** |

*Savings vs original 100W/20AH plan: ~₱3,600 per device*

---

## Phase 3 — Field Mounting
Deploy it in an actual rice field.

| # | Item | Qty | Est. ₱ | Purpose |
|---|------|:---:|:------:|---------|
| 22 | **Metal Junction Box IP65** ~30×25×20cm | 1 | ₱1,200 | Weatherproof enclosure |
| 23 | **GI Pipe 3" Schedule 40** 6m | 1 | ₱800 | Mounting post |
| 24 | **Concrete Mix 40kg** | 1 | ₱250 | Post footing |
| 25 | **Cable Glands PG9/PG11/PG16** 5-8 pcs (optional — can seal with silicone instead) | 1 | ₱150 | Waterproof cable entry |
| 26 | **PVC Conduit 3/4"** 3m (optional — tape cable to post if needed) | 1 | ₱150 | Protect sensor + antenna cables |
| 27 | **Stilling Well 4" PVC Pipe** 2m | 1 | ₱150 | Dampens ripples for stable readings |
| 28 | **Silicone Sealant Clear** (optional — may have one already) | 1 | ₱120 | Seal everything |
| 29 | **Cable Ties UV Black** (optional — may have zip ties already) | 1 pack | ₱50 | Cable management |
| | **PHASE 3 TOTAL** | | **~₱2,400** (₱2,870 if buying everything) | |

---

## Grand Total

| Scenario | Cost |
|----------|:----:|
| **You, buying only what you don't have** | **~₱8,930** |
| Buying everything from scratch | ~₱10,630 |

---

## The 10 Items You Absolutely Cannot Skip

| # | Item | Why |
|:-:|------|-----|
| 1 | **JSN-SR04T** | HC-SR04 dies outdoors in days — this one is IP67 waterproof |
| 2 | **SIM800L + Antenna** | No antenna = damaged module or no SMS at all |
| 3 | **2x LM2596** | No 5V = nothing runs. No 4.2V = SIM800L burns at 12V |
| 4 | **1000µF capacitor** | Without it, SIM800L resets Arduino every time it sends SMS |
| 5 | **1kΩ + 2kΩ resistors** | Without voltage divider, SIM800L's 3.3V pin gets 5V and dies |
| 6 | **5A fuse** | Short circuit without fuse = fire hazard |
| 7 | **Stilling well** | Without it, wind ripples = erratic readings you can't trust |
| 8 | **Cable glands** | No glands = water runs down cables into the box = everything shorts |
| 9 | **Multimeter** | You can't set LM2596 voltage by eye. Wrong voltage kills components |
| 10 | **Battery 12V 20AH** | No battery = no power at night or on cloudy days |
