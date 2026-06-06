/**
 * JSN-SR04T Water Level Monitor — USB Bridge Test Sketch
 * =========================================================
 * For mock deployment while waiting for A7680C 4G module.
 * Sends readings over USB serial → Python bridge → Dashboard.
 *
 * SIM module pins (D2, D3, D4) are RESERVED — do not connect yet.
 *
 * Configure per device:
 *   DEVICE_ID   = "RF01", "RF02", or "RF03"
 *   FIELD_DEPTH = 50.0,  65.0,    85.0
 */

#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// ─── CONFIGURE PER DEVICE ───────────────────────────
const String DEVICE_ID      = "RF01";       // Change per Arduino
const float FIELD_DEPTH_CM  = 50.0;         // Change per Arduino
// ────────────────────────────────────────────────────

#define TRIG_PIN  9
#define ECHO_PIN  10

// Reserved for A7680C (do not connect yet):
#define SIM_RX     2
#define SIM_TX     3
#define SIM_PWRKEY 4

LiquidCrystal_I2C lcd(0x27, 16, 2);

unsigned long lastRead = 0;
const unsigned long READ_INTERVAL = 5000;  // 5 seconds for testing

void setup() {
  Serial.begin(9600);
  
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  
  // Reserved SIM pins — set as inputs with pull-ups
  pinMode(SIM_RX, INPUT_PULLUP);
  pinMode(SIM_TX, INPUT_PULLUP);
  pinMode(SIM_PWRKEY, INPUT_PULLUP);
  
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print(DEVICE_ID);
  lcd.setCursor(0, 1);
  lcd.print("Booting...");
  delay(1000);
  lcd.clear();
  
  Serial.println("[BOOT] " + DEVICE_ID + " ready — USB bridge mode");
  Serial.println("[BOOT] Field depth: " + String(FIELD_DEPTH_CM) + " cm");
}

void loop() {
  unsigned long now = millis();
  
  if (now - lastRead >= READ_INTERVAL) {
    takeReading();
    lastRead = now;
  }
}

void takeReading() {
  // Average 5 samples
  float total = 0;
  int valid = 0;
  
  for (int i = 0; i < 5; i++) {
    float dist = readDistance();
    if (dist >= 25.0 && dist < 400.0) {
      total += dist;
      valid++;
    }
    delay(10);
  }
  
  if (valid == 0) {
    Serial.println("[MEASURE] Distance: ERROR | Water Level: ERROR");
    lcd.setCursor(0, 0);
    lcd.print(DEVICE_ID + " ERR!       ");
    return;
  }
  
  float avgDist = total / valid;
  float waterLevel = FIELD_DEPTH_CM - avgDist;
  if (waterLevel < 0) waterLevel = 0;
  if (waterLevel > FIELD_DEPTH_CM) waterLevel = FIELD_DEPTH_CM;
  
  // Output for Python bridge
  Serial.print("[MEASURE] Distance: ");
  Serial.print(avgDist, 1);
  Serial.print(" cm | Water Level: ");
  Serial.print(waterLevel, 1);
  Serial.println(" cm");
  
  // Update LCD
  lcd.setCursor(0, 0);
  lcd.print(DEVICE_ID + "          ");
  lcd.setCursor(0, 0);
  lcd.print(DEVICE_ID);
  
  lcd.setCursor(6, 0);
  lcd.print(waterLevel, 1);
  lcd.print("cm   ");
  
  lcd.setCursor(0, 1);
  lcd.print("Dist: ");
  lcd.print(avgDist, 1);
  lcd.print("cm   ");
}

float readDistance() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);
  
  unsigned long duration = pulseIn(ECHO_PIN, HIGH, 30000);
  
  if (duration == 0) return -1;
  return (duration * 0.0343) / 2.0;
}
