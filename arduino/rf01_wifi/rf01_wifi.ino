/**
 * ESP32 Water Level Monitor — WiFi
 * JSN-SR04T → LCD → WiFi → GET → Dashboard
 * No cellular module needed.
 */

#include <WiFi.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// ─── CONFIG ───────────────────────────
const String DEVICE_ID      = "RF01";
const float FIELD_DEPTH_CM  = 100.0;
const unsigned long INTERVAL = 30000;  // 30s for testing

const char* WIFI_SSID     = "your_wifi_name";
const char* WIFI_PASSWORD = "your_wifi_password";
// ───────────────────────────────────────

#define TRIG_PIN  9
#define ECHO_PIN  10

LiquidCrystal_I2C lcd(0x27, 16, 2);

unsigned long lastSend = 0;
float lastWaterLevel = 0;

void setup() {
  Serial.begin(115200);
  
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  
  lcd.init();
  lcd.backlight();
  lcd.print("RF01 WiFi...");
  
  // Connect WiFi
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Connecting to WiFi");
  lcd.setCursor(0, 1);
  lcd.print("Connecting...");
  
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 40) {
    delay(500);
    Serial.print(".");
    tries++;
  }
  
  Serial.println();
  lcd.clear();
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("✅ WiFi: " + WiFi.localIP().toString());
    lcd.setCursor(0, 0);
    lcd.print("RF01 WiFi OK");
    lcd.setCursor(0, 1);
    lcd.print(WiFi.localIP().toString());
  } else {
    Serial.println("❌ WiFi failed");
    lcd.print("WiFi FAILED!");
  }
  
  delay(2000);
  lcd.clear();
}

void loop() {
  unsigned long now = millis();
  if (now - lastSend >= INTERVAL) {
    takeReading();
    lastSend = now;
  }
}

void takeReading() {
  // Average 5 samples
  float total = 0;
  int valid = 0;
  
  for (int i = 0; i < 5; i++) {
    float dist = readDistance();
    if (dist >= 25.0 && dist < 400.0) { total += dist; valid++; }
    delay(10);
  }
  
  if (valid == 0) {
    lcd.setCursor(0, 0);
    lcd.print("RF01 ERR!      ");
    return;
  }
  
  float avgDist = total / valid;
  lastWaterLevel = FIELD_DEPTH_CM - avgDist;
  if (lastWaterLevel < 0) lastWaterLevel = 0;
  
  // LCD
  lcd.setCursor(0, 0);
  lcd.print("RF01          ");
  lcd.setCursor(0, 0);
  lcd.print("RF01");
  lcd.setCursor(6, 0);
  lcd.print(lastWaterLevel, 1);
  lcd.print("cm ");
  
  lcd.setCursor(0, 1);
  lcd.print("Sending...     ");
  
  Serial.print("[MEASURE] WL: ");
  Serial.print(lastWaterLevel, 1);
  Serial.println(" cm");
  
  // Send via WiFi
  sendToDashboard();
  
  lcd.setCursor(0, 1);
  lcd.print("Sent OK!       ");
  delay(2000);
  lcd.setCursor(0, 1);
  lcd.print("WiFi Connected ");
}

void sendToDashboard() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[HTTP] WiFi disconnected");
    return;
  }
  
  WiFiClient client;
  
  String url = "/api/reading.php?";
  url += "device_id=" + DEVICE_ID;
  url += "&water_level_cm=" + String(lastWaterLevel, 1);
  url += "&distance_cm=0";
  url += "&battery_v=5.0";
  url += "&signal=0";
  url += "&reading_mode=wifi";
  
  Serial.print("[HTTP] GET " + url + " -> ");
  
  if (client.connect("water-monitoring.ddns.net", 80)) {
    client.print("GET " + url + " HTTP/1.1\r\n");
    client.print("Host: water-monitoring.ddns.net\r\n");
    client.print("Connection: close\r\n\r\n");
    
    unsigned long timeout = millis();
    while (client.available() == 0) {
      if (millis() - timeout > 5000) {
        Serial.println("timeout");
        client.stop();
        return;
      }
    }
    
    String resp;
    while (client.available()) {
      resp += client.readString();
    }
    
    if (resp.indexOf("200 OK") >= 0 || resp.indexOf("\"success\":true") >= 0) {
      Serial.println("✅ sent!");
    } else {
      Serial.println("⚠️ error: " + resp.substring(0, 60));
    }
    
    client.stop();
  } else {
    Serial.println("connection failed");
  }
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
