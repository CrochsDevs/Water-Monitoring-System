/**
 * JSN-SR04T Rice Field Water Level Monitor — Water-Only Edition
 * ==============================================================
 * Arduino Uno + JSN-SR04T Waterproof Ultrasonic + SIM800L GSM + LCD 16x2 I2C
 *
 * Features:
 *   - Reads water level every MEASURE_INTERVAL (default: 5 min)
 *   - Displays current level + battery voltage on LCD
 *   - Sends data via SMS (primary) or HTTP POST (optional) every TX_INTERVAL
 *   - SMS command interface: farmer texts "STATUS", "LEVEL", or "BATTERY"
 *   - Low battery protection: skips GSM TX if voltage < threshold
 *   - Error recovery: retries GSM operations on failure
 *   - JSN-SR04T blind zone (25cm) handled — readings below 25cm are rejected
 *
 * DIFFERENCES FROM ORIGINAL (rice_field_monitor.ino):
 *   - Uses JSN-SR04T (IP67 waterproof) instead of HC-SR04 (indoor only)
 *   - No DS18B20 temperature sensor — no temp compensation needed for water-level only
 *   - Blind zone check for JSN-SR04T: ignores distances < 25cm
 *   - All other functionality unchanged
 *
 * Wiring:
 *   See JSN-SR04T_WATER_ONLY_DESIGN.md for full wiring diagram
 *
 * Pin Mapping:
 *   D0 (RX)  <-  SIM800L TX (via voltage divider)
 *   D1 (TX)  ->  SIM800L RX (via voltage divider)
 *   D9       ->  JSN-SR04T TRIG
 *   D10      <-  JSN-SR04T ECHO
 *   A4 (SDA) ->  LCD SDA (I2C)
 *   A5 (SCL) ->  LCD SCL (I2C)
 *   A0       <-  Battery voltage divider (optional)
 */

// ============================================================
// LIBRARIES
// ============================================================
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <SoftwareSerial.h>

// ============================================================
// PIN DEFINITIONS
// ============================================================
#define TRIG_PIN         9
#define ECHO_PIN         10
#define BATTERY_PIN      A0
#define GSM_RX_PIN       2   // Arduino RX (listens to SIM800L TX)
#define GSM_TX_PIN       3   // Arduino TX (talks to SIM800L RX)
#define GSM_PWRKEY_PIN   4   // Optional: SIM800L PWRKEY control

// ============================================================
// SYSTEM CONFIGURATION (Edit these for your deployment)
// ============================================================
// Water level configuration
const float FIELD_DEPTH_CM      = 200.0;  // Distance from sensor to field bottom (cm)
const float ALERT_HIGH_CM       = 15.0;   // Alert if water level > this (too high)
const float ALERT_LOW_CM        = 2.0;    // Alert if water level < this (too dry)
const int   SAMPLE_COUNT        = 5;      // Number of samples to average

// JSN-SR04T-specific limits
const float JSN_BLIND_ZONE_CM   = 25.0;   // Minimum reliable distance for JSN-SR04T

// Timing (milliseconds) — 5-minute cycle for both measurement + HTTP POST
const unsigned long MEASURE_INTERVAL = 300000;   // 5 minutes between measurements
const unsigned long TX_INTERVAL      = 300000;   // 5 minutes between HTTP POST to dashboard
const unsigned long LCD_UPDATE_INT   = 1000;     // LCD refreshes every 1 sec

// Battery protection
const float BATTERY_FULL         = 12.8;   // Voltage when fully charged
const float BATTERY_LOW          = 11.8;   // Voltage threshold - warn
const float BATTERY_CRITICAL     = 11.3;   // Voltage threshold - skip GSM TX
const float R1                   = 10000.0; // Voltage divider resistor 1 (10kΩ)
const float R2                   = 3300.0;  // Voltage divider resistor 2 (3.3kΩ)
const float VREF                 = 5.0;     // Arduino ADC reference voltage

// SIM800L / GSM configuration
const char APN[]                 = "internet";  // APN: "internet" (Smart) or "http.globe.com.ph" (Globe)
const char APN_USER[]            = "";
const char APN_PASS[]            = "";

// SMS configuration
const char ALERT_PHONE[]         = "+639XXXXXXXXX";  // Replace with farmer's phone number
const String DEVICE_ID           = "RF01";           // Unique device identifier

// HTTP endpoint — Arduino sends JSON POST to your dashboard server
const char SERVER_URL[]          = "http://your-server.com/api/reading.php";
const int  HTTP_TIMEOUT          = 15000;      // 15s max for HTTP operation
const int  HTTP_RETRY_COUNT      = 2;          // Retry HTTP twice before falling back to SMS

// ============================================================
// GLOBALS
// ============================================================
LiquidCrystal_I2C lcd(0x27, 16, 2);  // I2C address 0x27 (try 0x3F if no display)
SoftwareSerial gsmSerial(GSM_RX_PIN, GSM_TX_PIN);

// State
float currentWaterLevel = 0.0;
float currentDistance   = 0.0;
float batteryVoltage    = 0.0;
int   gsmSignal         = 0;       // RSSI (0-31, 99=unknown)
bool  gsmReady          = false;
bool  alertSent         = false;   // Prevents repeated alerts

unsigned long lastMeasureTime = 0;
unsigned long lastTxTime      = 0;
unsigned long lastLcdUpdate   = 0;

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(9600);     // USB debug serial
  gsmSerial.begin(9600);  // SIM800L default baud rate

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(GSM_PWRKEY_PIN, OUTPUT);
  digitalWrite(GSM_PWRKEY_PIN, HIGH);

  // Initialize LCD
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("Rice Field Mon.");
  lcd.setCursor(0, 1);
  lcd.print("Booting...");

  Serial.println(F("==================================="));
  Serial.println(F("Rice Field Water Level Monitor v1.0"));
  Serial.println(F("--- JSN-SR04T Water-Only Edition ---"));
  Serial.println(F("==================================="));

  // Power up GSM module
  powerUpGSM();

  // Initial sensor reading
  measureWaterLevel();

  // Initial display
  updateLCD();

  lastMeasureTime = millis();
  lastTxTime = millis() + 15000;  // Give GSM 15s first, then send initial status
}

// ============================================================
// MAIN LOOP
// ============================================================
void loop() {
  unsigned long now = millis();

  // 1. Check for incoming SMS commands
  checkSMSCommands();

  // 2. Periodic measurement
  if (now - lastMeasureTime >= MEASURE_INTERVAL) {
    measureWaterLevel();
    lastMeasureTime = now;
  }

  // 3. Periodic LCD update
  if (now - lastLcdUpdate >= LCD_UPDATE_INT) {
    updateLCD();
    lastLcdUpdate = now;
  }

  // 4. Periodic data transmission
  if (now - lastTxTime >= TX_INTERVAL) {
    readBatteryVoltage();

    // Only transmit GSM if battery is above critical level
    if (batteryVoltage >= BATTERY_CRITICAL) {
      sendWaterLevelAlert();
    } else {
      Serial.println(F("[WARN] Battery critical - skipping GSM TX"));
      lcd.setCursor(0, 1);
      lcd.print("BAT LOW!         ");
    }
    lastTxTime = now;
  }

  // 5. Check for alerts between TX intervals (threshold crossing)
  checkThresholdAlerts();

  delay(100);  // Small delay to prevent tight loop
}

// ============================================================
// WATER LEVEL MEASUREMENT (JSN-SR04T)
// ============================================================
void measureWaterLevel() {
  float total = 0.0;
  int   validSamples = 0;

  for (int i = 0; i < SAMPLE_COUNT; i++) {
    float dist = readUltrasonicDistance();

    // JSN-SR04T valid range: 25cm to 400cm
    // - Below 25cm = blind zone (sensor cannot measure accurately)
    // - Above 400cm = out of specified range
    if (dist >= JSN_BLIND_ZONE_CM && dist < 400) {
      total += dist;
      validSamples++;
    } else if (dist > 0) {
      Serial.print(F("[WARN] Reading outside valid range: "));
      Serial.print(dist);
      Serial.println(F(" cm (JSN-SR04T blind zone: 25cm min)"));
    }
    delay(50);  // 50ms between samples
  }

  if (validSamples > 0) {
    currentDistance = total / validSamples;
    // Water level = field depth - measured distance
    currentWaterLevel = FIELD_DEPTH_CM - currentDistance;

    // Sanity check: can't be negative or above max
    if (currentWaterLevel < 0)   currentWaterLevel = 0;
    if (currentWaterLevel > FIELD_DEPTH_CM) currentWaterLevel = FIELD_DEPTH_CM;

    Serial.print(F("[MEASURE] Distance: "));
    Serial.print(currentDistance);
    Serial.print(F(" cm | Water Level: "));
    Serial.print(currentWaterLevel);
    Serial.println(F(" cm"));
  } else {
    Serial.println(F("[ERROR] No valid ultrasonic readings!"));
    currentWaterLevel = -1.0;  // Error indicator
  }
}

float readUltrasonicDistance() {
  // JSN-SR04T uses the same TRIG/ECHO protocol as HC-SR04
  // Key difference: JSN-SR04T min reliable range is ~25cm (blind zone)
  // No temperature compensation needed for water-level monitoring
  // Fixed speed of sound: 343 m/s (~0.0343 cm/µs)

  // Ensure TRIG is low
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);

  // Send 10µs pulse to TRIG
  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  // Read ECHO pulse duration (microseconds)
  // Timeout after 30ms (~5m range) to avoid hanging
  unsigned long duration = pulseIn(ECHO_PIN, HIGH, 30000);

  if (duration == 0) {
    return -1.0;  // Timeout - no echo
  }

  // Speed of sound = 343 m/s = 0.0343 cm/µs
  // Distance = (duration / 2) * speed of sound
  // Note: No temperature compensation. At PH field temps (30-35°C),
  // this introduces ~2.6% error which is acceptable for flood/drought alerts.
  float distance = (duration * 0.0343) / 2.0;

  return distance;
}

// ============================================================
// BATTERY VOLTAGE MONITORING
// ============================================================
void readBatteryVoltage() {
  int raw = analogRead(BATTERY_PIN);
  // Convert ADC (0-1023) to voltage at pin
  float pinVoltage = (raw / 1023.0) * VREF;
  // Reverse voltage divider: V_batt = V_pin * (R1 + R2) / R2
  batteryVoltage = pinVoltage * ((R1 + R2) / R2);

  Serial.print(F("[BATTERY] ADC: "));
  Serial.print(raw);
  Serial.print(F(" | Voltage: "));
  Serial.print(batteryVoltage);
  Serial.println(F(" V"));
}

// ============================================================
// LCD DISPLAY
// ============================================================
void updateLCD() {
  lcd.setCursor(0, 0);
  lcd.print("Water: ");

  if (currentWaterLevel < 0) {
    lcd.print("ERR!   ");
  } else {
    lcd.print(currentWaterLevel, 1);
    lcd.print("cm     ");
  }

  lcd.setCursor(0, 1);
  lcd.print("Bat: ");
  lcd.print(batteryVoltage, 1);
  lcd.print("V ");

  // Signal indicator
  if (gsmReady) {
    lcd.print("GSM:");
    lcd.print(gsmSignal);
    lcd.print(" ");
  } else {
    lcd.print("No GSM ");
  }
}

// ============================================================
// GSM / SIM800L FUNCTIONS
// ============================================================
void powerUpGSM() {
  Serial.println(F("[GSM] Powering up..."));

  // Method 1: Pulse PWRKEY (for standalone module)
  digitalWrite(GSM_PWRKEY_PIN, LOW);
  delay(1200);  // SIM800L requires ~1s low pulse on PWRKEY
  digitalWrite(GSM_PWRKEY_PIN, HIGH);

  gsmSerial.begin(9600);
  delay(3000);  // Wait for module to boot

  // Wait for "Call Ready" or "SMS Ready"
  unsigned long timeout = millis() + 10000;
  while (millis() < timeout) {
    if (gsmSerial.available()) {
      String resp = gsmSerial.readString();
      Serial.print(F("[GSM BOOT] "));
      Serial.println(resp);
      if (resp.indexOf("Call Ready") >= 0 || resp.indexOf("SMS Ready") >= 0) {
        break;
      }
    }
  }

  // Initialize GSM
  gsmReady = initGSM();
}

bool initGSM() {
  Serial.println(F("[GSM] Initializing..."));

  // Test AT communication
  if (!sendAT("AT", "OK", 2000)) {
    Serial.println(F("[GSM] AT failed - module not responding"));
    return false;
  }

  // Disable echo
  sendAT("ATE0", "OK", 1000);

  // Check signal quality
  String sigResp = sendATWithResponse("AT+CSQ", 2000);
  if (sigResp.length() > 0) {
    int commaIdx = sigResp.indexOf(',');
    if (commaIdx > 0) {
      String rssiStr = sigResp.substring(sigResp.indexOf(':') + 1, commaIdx);
      rssiStr.trim();
      gsmSignal = rssiStr.toInt();
      Serial.print(F("[GSM] Signal RSSI: "));
      Serial.println(gsmSignal);
    }
  }

  // Set SMS mode to text
  sendAT("AT+CMGF=1", "OK", 1000);

  // Set GSM network (auto)
  sendAT("AT+CNET=0", "OK", 2000);

  // Check network registration
  String regResp = sendATWithResponse("AT+CREG?", 2000);
  if (regResp.indexOf("+CREG: 0,1") >= 0 || regResp.indexOf("+CREG: 0,5") >= 0) {
    Serial.println(F("[GSM] Registered on network"));
  } else {
    Serial.println(F("[GSM] Not registered yet - will retry"));
  }

  // Attach GPRS service
  sendAT("AT+CGATT=1", "OK", 5000);

  Serial.println(F("[GSM] Ready"));
  return true;
}

bool sendAT(const String& cmd, const String& expected, unsigned long timeout) {
  gsmSerial.println(cmd);
  unsigned long start = millis();
  while (millis() - start < timeout) {
    if (gsmSerial.available()) {
      String resp = gsmSerial.readString();
      Serial.print(F("[GSM TX] "));
      Serial.print(cmd);
      Serial.print(F(" -> "));
      Serial.println(resp.substring(0, 80));
      if (resp.indexOf(expected) >= 0) {
        return true;
      }
    }
  }
  Serial.print(F("[GSM] Timeout waiting for: "));
  Serial.println(expected);
  return false;
}

String sendATWithResponse(const String& cmd, unsigned long timeout) {
  gsmSerial.println(cmd);
  unsigned long start = millis();
  String fullResp;
  while (millis() - start < timeout) {
    if (gsmSerial.available()) {
      char c = gsmSerial.read();
      fullResp += c;
    }
  }
  Serial.print(F("[GSM RSP] "));
  Serial.println(fullResp.substring(0, 120));
  return fullResp;
}

// ============================================================
// SMS - SEND ALERT
// ============================================================
void sendWaterLevelAlert() {
  if (!gsmReady) {
    Serial.println(F("[SMS] GSM not ready - attempting re-init"));
    gsmReady = initGSM();
    if (!gsmReady) return;
  }

  readBatteryVoltage();

  // Build JSON data for HTTP
  String jsonData = "{";
  jsonData += "\"device_id\":\"" + DEVICE_ID + "\",";
  jsonData += "\"water_level_cm\":" + String(currentWaterLevel, 1) + ",";
  jsonData += "\"distance_cm\":" + String(currentDistance, 1) + ",";
  jsonData += "\"battery_v\":" + String(batteryVoltage, 1) + ",";
  jsonData += "\"signal\":" + String(gsmSignal) + ",";
  jsonData += "\"uptime_days\":" + String(millis() / 86400000);

  // Determine alert string
  String alertStr = "";
  if (currentWaterLevel >= ALERT_HIGH_CM) {
    alertStr = "high_water";
  } else if (currentWaterLevel <= ALERT_LOW_CM && currentWaterLevel >= 0) {
    alertStr = "low_water";
  } else if (currentWaterLevel < 0) {
    alertStr = "sensor_error";
  }
  if (batteryVoltage > 0 && batteryVoltage < BATTERY_CRITICAL) {
    alertStr = "low_battery";
  }

  if (alertStr.length() > 0) {
    jsonData += ",\"alert\":\"" + alertStr + "\"";
  }
  jsonData += "}";

  // Try HTTP POST first
  Serial.println(F("[HTTP] Sending data to dashboard..."));
  Serial.println(jsonData);

  bool httpSuccess = false;
  for (int retry = 0; retry <= HTTP_RETRY_COUNT && !httpSuccess; retry++) {
    if (retry > 0) {
      Serial.print(F("[HTTP] Retry "));
      Serial.println(retry);
      delay(2000);
    }
    httpSuccess = sendHTTPPOST(jsonData);

    if (httpSuccess) {
      Serial.println(F("[HTTP] Dashboard updated successfully"));
    }
  }

  // If HTTP succeeded, also SMS on alert conditions
  if (httpSuccess) {
    alertSent = true;

    // Still send SMS if there's an active alert (redundant but critical)
    if (alertStr.length() > 0) {
      String smsMsg = buildSMSMessage();
      if (sendSMS(ALERT_PHONE, smsMsg)) {
        Serial.println(F("[SMS] Alert SMS sent"));
      }
    }
  } else {
    // HTTP failed — fall back to SMS completely
    Serial.println(F("[HTTP] Failed — falling back to SMS"));
    String smsMsg = buildSMSMessage();
    if (sendSMS(ALERT_PHONE, smsMsg)) {
      Serial.println(F("[SMS] Sent successfully (fallback)"));
      alertSent = true;
    } else {
      Serial.println(F("[SMS] Failed to send"));
      gsmReady = initGSM();
      if (gsmReady) {
        String smsRetry = buildSMSMessage();
        if (sendSMS(ALERT_PHONE, smsRetry)) {
          Serial.println(F("[SMS] Sent on retry"));
          alertSent = true;
        }
      }
    }
  }
}

String buildSMSMessage() {
  String message = "Rice Field " + DEVICE_ID + "\n";
  message += "Water Level: " + String(currentWaterLevel, 1) + " cm\n";
  message += "Distance: " + String(currentDistance, 1) + " cm\n";
  message += "Battery: " + String(batteryVoltage, 1) + "V\n";
  message += "Signal: " + String(gsmSignal) + "/31\n";

  if (currentWaterLevel >= ALERT_HIGH_CM) {
    message += "ALERT: Water level HIGH!\n";
  }
  if (currentWaterLevel <= ALERT_LOW_CM && currentWaterLevel >= 0) {
    message += "ALERT: Water level LOW!\n";
  }
  if (batteryVoltage < BATTERY_LOW) {
    message += "ALERT: Battery low!\n";
  }

  message += String(millis() / 86400000) + "d uptime";
  return message;
}

// ============================================================
// HTTP - POST TO DASHBOARD (GPRS mode)
// ============================================================
bool sendHTTPPOST(const String& jsonData) {
  // 1. Open GPRS bearer
  if (!sendAT("AT+SAPBR=3,1,\"CONTYPE\",\"GPRS\"", "OK", 3000)) {
    Serial.println(F("[HTTP] Failed to set bearer type"));
    return false;
  }

  // 2. Set APN
  String apnCmd = "AT+SAPBR=3,1,\"APN\",\"";
  apnCmd += APN;
  apnCmd += "\"";
  if (!sendAT(apnCmd.c_str(), "OK", 3000)) {
    Serial.println(F("[HTTP] Failed to set APN"));
    return false;
  }

  // 3. Open bearer connection
  if (!sendAT("AT+SAPBR=1,1", "OK", 10000)) {
    Serial.println(F("[HTTP] Failed to open bearer"));
    return false;
  }

  // 4. Get IP address (optional, confirms GPRS is up)
  String ipResp = sendATWithResponse("AT+SAPBR=2,1", 3000);
  if (ipResp.indexOf("0.0.0.0") >= 0) {
    Serial.println(F("[HTTP] No IP assigned — GPRS may not be connected"));
    sendAT("AT+SAPBR=0,1", "OK", 2000);
    return false;
  }

  // 5. Initialize HTTP
  if (!sendAT("AT+HTTPINIT", "OK", 3000)) {
    Serial.println(F("[HTTP] HTTP init failed"));
    sendAT("AT+SAPBR=0,1", "OK", 2000);
    return false;
  }

  // 6. Set bearer ID
  if (!sendAT("AT+HTTPPARA=\"CID\",1", "OK", 3000)) {
    Serial.println(F("[HTTP] Failed to set CID"));
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+SAPBR=0,1", "OK", 2000);
    return false;
  }

  // 7. Set URL
  String urlCmd = "AT+HTTPPARA=\"URL\",\"";
  urlCmd += SERVER_URL;
  urlCmd += "\"";
  if (!sendAT(urlCmd.c_str(), "OK", 3000)) {
    Serial.println(F("[HTTP] Failed to set URL"));
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+SAPBR=0,1", "OK", 2000);
    return false;
  }

  // 8. Set content type
  if (!sendAT("AT+HTTPPARA=\"CONTENT\",\"application/json\"", "OK", 3000)) {
    Serial.println(F("[HTTP] Failed to set content type"));
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+SAPBR=0,1", "OK", 2000);
    return false;
  }

  // 9. Set data length and send payload
  String dataLen = "AT+HTTPDATA=" + String(jsonData.length()) + "," + String(HTTP_TIMEOUT);
  if (!sendAT(dataLen.c_str(), "DOWNLOAD", 5000)) {
    Serial.println(F("[HTTP] Failed to start data upload"));
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+SAPBR=0,1", "OK", 2000);
    return false;
  }

  // Send the JSON data
  gsmSerial.print(jsonData);
  delay(200);
  gsmSerial.write(26);  // Ctrl+Z to signal end of data
  delay(100);

  // Wait for OK after data
  unsigned long start = millis();
  bool dataAccepted = false;
  while (millis() - start < 10000) {
    if (gsmSerial.available()) {
      String resp = gsmSerial.readString();
      if (resp.indexOf("OK") >= 0) {
        dataAccepted = true;
        break;
      }
    }
  }

  if (!dataAccepted) {
    Serial.println(F("[HTTP] Data not accepted by module"));
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+SAPBR=0,1", "OK", 2000);
    return false;
  }

  // 10. Execute HTTP POST action
  Serial.println(F("[HTTP] Sending POST..."));
  String postResp = sendATWithResponse("AT+HTTPACTION=1", 15000);

  // Check for HTTP response code
  if (postResp.indexOf("+HTTPACTION: 1,200") >= 0 || postResp.indexOf("+HTTPACTION: 1,201") >= 0) {
    Serial.println(F("[HTTP] Server returned 200/201 OK"));
    // Read response body (for debugging)
    String body = sendATWithResponse("AT+HTTPREAD", 5000);
    Serial.print(F("[HTTP] Response: "));
    Serial.println(body.substring(0, 80));

    // Cleanup
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+SAPBR=0,1", "OK", 2000);
    return true;
  } else {
    Serial.print(F("[HTTP] Server response: "));
    Serial.println(postResp.substring(0, 60));
    // Cleanup
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+SAPBR=0,1", "OK", 2000);
    return false;
  }
}

bool sendSMS(const String& number, const String& message) {
  gsmSerial.print("AT+CMGS=\"");
  gsmSerial.print(number);
  gsmSerial.println("\"");

  delay(500);  // Wait for ">" prompt

  // Check for '>' prompt
  unsigned long start = millis();
  bool promptReceived = false;
  while (millis() - start < 5000) {
    if (gsmSerial.available()) {
      char c = gsmSerial.read();
      if (c == '>') {
        promptReceived = true;
        break;
      }
    }
  }

  if (!promptReceived) {
    Serial.println(F("[SMS] No '>' prompt received"));
    return false;
  }

  // Send message
  gsmSerial.print(message);
  delay(100);
  gsmSerial.write(26);  // Ctrl+Z to send
  delay(100);

  // Wait for response
  start = millis();
  while (millis() - start < 10000) {
    if (gsmSerial.available()) {
      String resp = gsmSerial.readString();
      Serial.print(F("[SMS RSP] "));
      Serial.println(resp);
      if (resp.indexOf("+CMGS:") >= 0 || resp.indexOf("OK") >= 0) {
        return true;
      }
      if (resp.indexOf("ERROR") >= 0) {
        return false;
      }
    }
  }
  return false;
}

// ============================================================
// SMS - COMMAND RECEPTION (Farmer queries)
// ============================================================
void checkSMSCommands() {
  if (!gsmReady) return;

  // Check for new SMS
  gsmSerial.println("AT+CMGL=\"REC UNREAD\",1");

  unsigned long start = millis();
  while (millis() - start < 3000) {
    if (gsmSerial.available()) {
      String resp = gsmSerial.readString();

      if (resp.indexOf("+CMGL:") >= 0) {
        Serial.println(F("[SMS CMD] Incoming command detected"));

        // Extract message body (after last line break before OK)
        int idx = resp.indexOf("\r\n\r\n");
        if (idx > 0) {
          String body = resp.substring(idx + 4);
          body.trim();
          // Remove trailing "OK"
          int okIdx = body.indexOf("OK");
          if (okIdx >= 0) body = body.substring(0, okIdx);
          body.trim();

          body.toUpperCase();
          Serial.print(F("[SMS CMD] Command: "));
          Serial.println(body);

          // Handle commands
          String reply;
          if (body.indexOf("STATUS") >= 0 || body.indexOf("LEVEL") >= 0) {
            reply = "RF " + DEVICE_ID + ": Level=" + String(currentWaterLevel, 1) + "cm, Bat=" + String(batteryVoltage, 1) + "V, Signal=" + String(gsmSignal) + "/31";
          } else if (body.indexOf("BATTERY") >= 0 || body.indexOf("BAT") >= 0) {
            reply = "Battery: " + String(batteryVoltage, 1) + "V";
            if (batteryVoltage < BATTERY_LOW) reply += " LOW";
            else if (batteryVoltage >= BATTERY_FULL) reply += " FULL";
            else reply += " OK";
          } else if (body.indexOf("HELP") >= 0) {
            reply = "Commands: STATUS, LEVEL, BATTERY, HELP";
          } else {
            reply = "Unknown cmd. Send HELP for list.";
          }

          // Send reply to the sender
          // Extract sender number
          int numStart = resp.indexOf("\"REC UNREAD\",\"") + 15;
          int numEnd = resp.indexOf("\"", numStart);
          String sender = resp.substring(numStart, numEnd);
          Serial.print(F("[SMS CMD] Reply to: "));
          Serial.println(sender);
          sendSMS(sender, reply);
        }

        // Delete all read messages to keep inbox clean
        gsmSerial.println("AT+CMGDA=\"DEL READ\"");
        delay(500);
        while (gsmSerial.available()) gsmSerial.read();
      }
    }
  }
}

// ============================================================
// THRESHOLD ALERT CHECKING
// ============================================================
void checkThresholdAlerts() {
  if (currentWaterLevel < 0) return;  // Error reading, skip

  bool highAlert  = (currentWaterLevel >= ALERT_HIGH_CM);
  bool lowAlert   = (currentWaterLevel <= ALERT_LOW_CM) && (currentWaterLevel >= 0);

  if ((highAlert || lowAlert) && !alertSent) {
    // Send immediate alert outside of regular TX schedule
    Serial.println(F("[ALERT] Threshold crossed - sending immediate alert"));
    sendWaterLevelAlert();
    alertSent = true;
  }

  // Reset alert flag when water level returns to normal
  if (!highAlert && !lowAlert) {
    alertSent = false;
  }
}

// ============================================================
// DEBUG / SERIAL COMMANDS (via USB serial monitor)
// ============================================================
void serialEvent() {
  if (Serial.available()) {
    String cmd = Serial.readStringUntil('\n');
    cmd.trim();
    cmd.toUpperCase();

    if (cmd == "MEASURE" || cmd == "M") {
      measureWaterLevel();
      updateLCD();
    } else if (cmd == "SMS" || cmd == "SEND") {
      sendWaterLevelAlert();
    } else if (cmd == "BAT" || cmd == "BATTERY") {
      readBatteryVoltage();
    } else if (cmd == "GSM" || cmd == "INIT") {
      gsmReady = initGSM();
    } else if (cmd == "HELP" || cmd == "H") {
      Serial.println(F("--- Serial Commands ---"));
      Serial.println(F("MEASURE/M  - Take a reading"));
      Serial.println(F("SMS/SEND   - Send alert SMS"));
      Serial.println(F("BAT/BATTERY - Read battery"));
      Serial.println(F("GSM/INIT   - Re-init GSM"));
      Serial.println(F("HELP/H     - This menu"));
    } else if (cmd.length() > 0) {
      // Passthrough to GSM module for debugging
      gsmSerial.println(cmd);
      delay(1000);
      while (gsmSerial.available()) {
        Serial.write(gsmSerial.read());
      }
    }
  }
}
