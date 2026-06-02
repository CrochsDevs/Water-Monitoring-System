# Image Generation Prompt — Finished Product

## Option 1: Realistic Photo (for presentation / thesis paper)

Paste into Midjourney, DALL-E, Stable Diffusion, or any AI image generator:

```
Photorealistic isometric cutaway diagram of a solar-powered automated rice field water level monitoring system installed in a lush green rice paddy in the Philippines. The scene shows a clear sunny day with a blue sky and green rice terraces.

The main structure is a 2-meter tall galvanized iron pipe (GI Pipe 3-inch diameter) firmly cemented into the ground with a concrete footing base at ground level. The pipe is painted with anti-rust gray primer.

MOUNTED ON THE POLE (from top to bottom):

1. SOLAR PANEL (TOP): A small 10-watt monocrystalline solar panel (about 35cm x 25cm) mounted on a metal bracket at the very top of the pipe, tilted at approximately 14 degrees facing south, with two black cables running down from the back of the panel into the junction box below.

2. JUNCTION BOX (MIDDLE): A gray metal IP65-rated weatherproof junction box (approximately 30cm x 25cm x 20cm) bolted securely to the pipe at about chest height (1.5 meters above ground). The box has a hinged door with a latch, cable glands on the bottom for weatherproof cable entry, and a small padlock hasp for security. A small LCD display is visible through a cutout window in the door showing "Water: 15.2cm" and "Bat: 12.4V LTE:18". A small black rubber GSM/LTE antenna (about 10cm long) sticks out from the side of the box. Inside the box (visible through a semi-transparent cutaway view): an Arduino Uno board mounted on standoffs, the Air780E 4G LTE module with a SIM card inserted, two small blue LM2596 voltage regulator boards, a small LCD 16x2 module mounted to the inside of the door, and various colored wires connecting everything neatly.

3. PVC CONDUIT: A 3/4-inch white PVC conduit pipe runs down the outside of the GI pipe from the bottom of the junction box, protecting the sensor cable.

4. STILLING WELL (BOTTOM): A 4-inch diameter white PVC pipe (about 50-85cm long depending on field depth) strapped vertically to the GI pipe with stainless steel clamps. The bottom of the stilling well is submerged in the rice paddy water. Small 5mm drainage holes are visible along the bottom section of the pipe where water enters. Inside the stilling well (shown as a cutaway): the JSN-SR04T ultrasonic transducer is mounted at the top of the well, facing downward toward the water surface. A 40kHz sound wave cone is visualized as faint blue pulses traveling from the sensor down to the water surface. The water surface inside the well is perfectly flat (no ripples), while the water outside the well has small ripples and reflections.

5. SENSOR CABLE: A black 2-meter cable runs from inside the junction box, through the PVC conduit, to the transducer inside the stilling well.

6. BATTERY ENCLOSURE (optional, at bottom): A small black weatherproof box near the base of the pipe housing a 12V 7AH sealed lead-acid battery.

The environment around the installation: green rice plants growing in flooded paddies, water reflects the sky, small mud embankments between fields, a farmer in a straw hat can be seen in the distance checking the system.

The style is a clean, detailed architectural illustration rendering with realistic lighting, slight depth of field blur in the background, warm tropical lighting, and labels pointing to each major component with subtle callout lines.

Lighting: Golden hour warm sunlight from the left side, long shadows to the right, creating a professional and inviting agricultural technology scene.
```

---

## Option 2: Technical Diagram Style (for research paper / planning docs)

```
Technical schematic illustration of a solar-powered water level monitoring station for agricultural irrigation. Front-view elevation drawing on a clean white background with engineering blueprint aesthetic.

The structure is a vertical assembly mounted on a single galvanized steel pipe (3-inch diameter, 2 meters tall) anchored in a concrete base underground.

Components labeled with callout numbers:

1. "10W SOLAR PANEL" - Small rectangular photovoltaic panel (35cm × 25cm) mounted at 14-degree tilt on top bracket. Generates ~31.5 Wh/day.

2. "IP65 JUNCTION BOX" - Gray metal enclosure (30×25×20cm) at 1.5m height containing: Arduino Uno microcontroller, Air780E 4G LTE module, LM2596 voltage regulators (12V→5V and 12V→4.2V), LCD 16×2 display. Door has display window and latch. GSM antenna protrudes from side.

3. "PVC CONDUIT 3/4"" - White plastic conduit running from junction box down along the pipe, protecting the 2-meter sensor cable.

4. "STILLING WELL" - 4-inch diameter PVC pipe (adjustable length: 50-85cm) strapped to main pole. Perforated bottom section with 5mm drainage holes allows water entry while dampening surface ripples.

5. "JSN-SR04T ULTRASONIC SENSOR" - Waterproof IP67 ultrasonic transducer mounted inside the top of the stilling well, facing downward. Measures distance to water surface via 40kHz sound pulses. Minimum range: 25cm (blind zone). Maximum range: 400cm.

6. "WATER SURFACE" - The interface inside the stilling well where ultrasonic pulses reflect. Water level = well depth - measured distance.

7. "CONCRETE FOOTING" - 20kg concrete base (approximately 30cm diameter, 40cm deep) anchoring the structure.

8. "12V 7AH BATTERY" - Sealed lead-acid battery in protective enclosure at base. Provides 5+ days of autonomy.

Dimension lines showing: total height (2m), above-ground height (1.5m to junction box), stilling well length (variable), and underground depth (40cm).

The illustration is black line art on white background with blue accent highlights for water elements, green for plant environment, and orange for electrical components. Professional engineering drafting style suitable for academic publication.

Scale: 1:20 with measurement annotations in centimeters.
```

---

## Option 3: Component Labeling Guide

Use this alongside the image to describe each part:

```
┌──────────────────────────────────────────────────┐
│           FINISHED PRODUCT — LEGEND               │
├──────────────────────────────────────────────────┤
│                                                    │
│  A. [SOLAR PANEL 10W]                              │
│     Mono-crystalline, 18V Voc, 35×25cm             │
│     Generates ~31.5 Wh/day in PH sun               │
│     Tilted ~14° facing South                        │
│                                                    │
│  B. [IP65 JUNCTION BOX]                            │
│     Metal, 30×25×20cm, weatherproof                │
│     Contains: Arduino Uno, Air780E 4G module,      │
│     LM2596 regulators (5V + 4.2V), LCD display     │
│     GSM/LTE antenna on side                        │
│                                                    │
│  C. [PVC CONDUIT 3/4"]                             │
│     Protects sensor cable from elements            │
│     Runs from box down to stilling well            │
│                                                    │
│  D. [STILLING WELL 4" PVC]                         │
│     Length: 50-85cm (field-dependent)              │
│     5mm drainage holes at bottom                   │
│     Dampens water ripples for stable readings      │
│                                                    │
│  E. [JSN-SR04T TRANSDUCER]                         │
│     Waterproof IP67, epoxy-potted                  │
│     Mounted inside well, facing DOWN               │
│     Measures air gap to water surface              │
│     25cm blind zone, 400cm max range               │
│                                                    │
│  F. [WATER SURFACE]                                │
│     Sound reflects off this surface                │
│     Protected from wind ripples by stilling well   │
│                                                    │
│  G. [CONCRETE FOOTING]                             │
│     20kg mix, ~40cm deep                           │
│     Cures 48 hours before loading                  │
│                                                    │
│  H. [12V 7AH BATTERY]                              │
│     Sealed lead-acid                               │
│     5+ days autonomy without sun                   │
│     In weatherproof enclosure at base              │
│                                                    │
└──────────────────────────────────────────────────┘
```

---

## Where to Generate

| Platform | Best For | Recommended Prompt |
|----------|----------|:------------------:|
| **Midjourney** | Thesis cover, presentation slides | Option 1 (realistic) |
| **DALL-E 3** | Quick prototyping | Option 1 (realistic) |
| **Stable Diffusion** | Customization | Option 1 (realistic) |
| **Claude/GPT image gen** | In-chat generation | Option 1 (realistic) |
| **draw.io / Lucidchart** | Technical diagrams for paper | Option 2 (technical) |
| **Manual labeling** | Print-ready figure | Option 3 (labels) |
