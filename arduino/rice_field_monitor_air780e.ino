/**
 * JSN-SR04T Rice Field Water Level Monitor — Air780E 4G Edition
 * ==============================================================
 * Arduino Uno + JSN-SR04T Waterproof Ultrasonic + Air780E 4G LTE + LCD 16x2 I2C
 *
 * Features:
 *   - Reads water level every 5 minutes
 *   - Displays current level + battery voltage on LCD
 *   - Sends data via HTTP POST to dashboard (4G LTE) every 5 minutes
 *   - Falls back to SMS if HTTP fails
 *   - SMS command interface: farmer texts "STATUS", "LEVEL", or "BATTERY"
 *   - Low battery protection: skips GSM TX if voltage < threshold
 *   - JSN-SR04T blind zone (25cm) handled
 *
 * DIFFERENCES FROM SIM800L VERSION:
 *   - Uses Air780E 4G LTE module instead of SIM800L (2G GSM)
 *   - Baud rate: 115200 default — MUST change to 9600 (AT+IPR=9600)
 *   - Wiring: Air780E auto-boots on power (no PWRKEY pin needed)
 *   - Bearer setup: AT+CGDCONT instead of AT+SAPBR
 *   - All HTTP/SMS AT commands are 3GPP standard (same as SIM800L)
 *
 * Wiring — Air780E Pinout (from top, 1-10):
 *    1  VCC  ──→  LM2596 #2 set to 4.2V
 *    2  GND  ──→  Common ground
 *    3  TXD  ──→  Arduino D0 (RX) — direct (3.3V OK for Arduino)
 *    4  RXD  ──→  Via voltage divider ← Arduino D1 (TX) (5V→3.3V)
 *    5  RTS  ──→  Not connected
 *    6  CTS  ──→  Not connected
 *    7  DTR  ──→  Not connected
 *    8  DCD  ──→  Not connected
 *    9  RING ──→  Not connected
 *   10  RESET ──→  Not connected (auto-boots on power)
 *
 * Voltage divider for RX (Arduino 5V → Air780E 3.3V):
 *    Arduino D1 (TX) ──┬── 1kΩ ──┬── Air780E pin 4 (RXD)
 *                      │         │
 *                      └── 2kΩ ──┴── GND
 *
 * Pin Mapping:
 *   D0 (RX)  <-  Air780E pin 3 (TXD)
 *   D1 (TX)  ->  Air780E pin 4 (RXD) via voltage divider
 *   D4       ->  Not used (Air780E auto-boots)
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
#define MODULE_RX_PIN    2   // Arduino RX (listens to Air780E TX)
#define MODULE_TX_PIN    3   // Arduino TX (talks to Air780E RX)
#define PWRKEY_PIN       4   // Not used — Air780E auto-boots on power

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

// Air780E / 4G LTE configuration
const int   AIR_BAUD             = 115200;  // Air780E default baud rate
                                            // Change to 9600 if you reconfigured the module
const char APN[]                 = "internet";  // APN: "internet" (Smart) or "http.globe.com.ph" (Globe)
const char APN_USER[]            = "";
const char APN_PASS[]            = "";

// SMS configuration
const char ALERT_PHONE[]         = "+639XXXXXXXXX";  // Replace with farmer's phone number
const String DEVICE_ID           = "RF01";           // Unique device identifier

// HTTP endpoint — Arduino sends JSON POST to your dashboard server
const char SERVER_URL[]          = "http://your-server.com/api/reading.php";
const int  HTTP_TIMEOUT          = 30000;      // 30s max for HTTP operation (4G can be slower to connect)
const int  HTTP_RETRY_COUNT      = 2;          // Retry HTTP twice before falling back to SMS

// ============================================================
// GLOBALS
// ============================================================
LiquidCrystal_I2C lcd(0x27, 16, 2);  // I2C address 0x27 (try 0x3F if no display)
SoftwareSerial moduleSerial(MODULE_RX_PIN, MODULE_TX_PIN);  // Air780E communication

// State
float currentWaterLevel = 0.0;
float currentDistance   = 0.0;
float batteryVoltage    = 0.0;
int   moduleSignal      = 0;       // RSSI (0-31, 99=unknown)
bool  moduleReady       = false;
bool  alertSent         = false;   // Prevents repeated alerts

unsigned long lastMeasureTime = 0;
unsigned long lastTxTime      = 0;
unsigned long lastLcdUpdate   = 0;

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(9600);     // USB debug serial
  moduleSerial.begin(AIR_BAUD);  // Air780E baud rate

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(PWRKEY_PIN, OUTPUT);
  digitalWrite(PWRKEY_PIN, LOW);  // Not used, keep low

  // Initialize LCD
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("Rice Field Mon.");
  lcd.setCursor(0, 1);
  lcd.print("Air780E 4G...");

  Serial.println(F("======================================="));
  Serial.println(F("Rice Field Water Level Monitor v1.0"));
  Serial.println(F("--- Air780E 4G LTE Edition ---"));
  Serial.println(F("======================================="));

  // Power up 4G module
  powerUpModule();

  // Initial sensor reading
  measureWaterLevel();

  // Initial display
  updateLCD();

  lastMeasureTime = millis();
  lastTxTime = millis() + 30000;  // Give module 30s to register on LTE first
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

    // Only transmit if battery is above critical level
    if (batteryVoltage >= BATTERY_CRITICAL) {
      sendWaterLevelAlert();
    } else {
      Serial.println(F("[WARN] Battery critical - skipping TX"));
      lcd.setCursor(0, 1);
      lcd.print("BAT LOW!         ");
    }
    lastTxTime = now;
  }

  // 5. Check for alerts between TX intervals (threshold crossing)
  checkThresholdAlerts();

  delay(100);
}

// ============================================================
// WATER LEVEL MEASUREMENT (JSN-SR04T)
// ============================================================
void measureWaterLevel() {
  float total = 0.0;
  int   validSamples = 0;

  for (int i = 0; i < SAMPLE_COUNT; i++) {
    float dist = readUltrasonicDistance();
    if (dist >= JSN_BLIND_ZONE_CM && dist < 400) {
      total += dist;
      validSamples++;
    } else if (dist > 0) {
      Serial.print(F("[WARN] Reading outside valid range: "));
      Serial.print(dist);
      Serial.println(F(" cm (JSN-SR04T blind zone: 25cm min)"));
    }
    delay(50);
  }

  if (validSamples > 0) {
    currentDistance = total / validSamples;
    currentWaterLevel = FIELD_DEPTH_CM - currentDistance;
    if (currentWaterLevel < 0)   currentWaterLevel = 0;
    if (currentWaterLevel > FIELD_DEPTH_CM) currentWaterLevel = FIELD_DEPTH_CM;

    Serial.print(F("[MEASURE] Distance: "));
    Serial.print(currentDistance);
    Serial.print(F(" cm | Water Level: "));
    Serial.print(currentWaterLevel);
    Serial.println(F(" cm"));
  } else {
    Serial.println(F("[ERROR] No valid ultrasonic readings!"));
    currentWaterLevel = -1.0;
  }
}

float readUltrasonicDistance() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  unsigned long duration = pulseIn(ECHO_PIN, HIGH, 30000);

  if (duration == 0) {
    return -1.0;
  }

  float distance = (duration * 0.0343) / 2.0;
  return distance;
}

// ============================================================
// BATTERY VOLTAGE MONITORING
// ============================================================
void readBatteryVoltage() {
  int raw = analogRead(BATTERY_PIN);
  float pinVoltage = (raw / 1023.0) * VREF;
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

  if (moduleReady) {
    lcd.print("LTE:");
    lcd.print(moduleSignal);
    lcd.print(" ");
  } else {
    lcd.print("No 4G ");
  }
}

// ============================================================
// Air780E — POWER UP & INIT
// ============================================================
void powerUpModule() {
  Serial.println(F("[MODULE] Powering up Air780E..."));
  Serial.println(F("[MODULE] Auto-boot on power — waiting for startup..."));

  moduleSerial.begin(AIR_BAUD);
  delay(5000);  // Air780E takes ~3-5s to boot on power

  // Flush boot messages
  unsigned long timeout = millis() + 8000;
  while (millis() < timeout) {
    if (moduleSerial.available()) {
      String resp = moduleSerial.readString();
      Serial.print(F("[MODULE BOOT] "));
      Serial.println(resp.substring(0, 80));
      // Look for "RDY" or "+CPIN: READY" or "Call Ready" or "SMS Ready"
      if (resp.indexOf("RDY") >= 0 || resp.indexOf("READY") >= 0) {
        Serial.println(F("[MODULE] Boot detected"));
        break;
      }
    }
  }

  // Initialize module
  moduleReady = initModule();
}

bool initModule() {
  Serial.println(F("[MODULE] Initializing..."));

  // Test AT communication
  if (!sendAT("AT", "OK", 3000)) {
    Serial.println(F("[MODULE] AT failed - check wiring or baud rate"));
    return false;
  }

  // Disable echo
  sendAT("ATE0", "OK", 1000);

  // Check signal quality
  String sigResp = sendATWithResponse("AT+CSQ", 3000);
  if (sigResp.length() > 0) {
    int commaIdx = sigResp.indexOf(',');
    if (commaIdx > 0) {
      String rssiStr = sigResp.substring(sigResp.indexOf(':') + 1, commaIdx);
      rssiStr.trim();
      moduleSignal = rssiStr.toInt();
      Serial.print(F("[MODULE] Signal RSSI: "));
      Serial.println(moduleSignal);
    }
  }

  // Set SMS mode to text
  sendAT("AT+CMGF=1", "OK", 1000);

  // Check network registration
  String regResp = sendATWithResponse("AT+CREG?", 3000);
  if (regResp.indexOf("+CREG: 0,1") >= 0 || regResp.indexOf("+CREG: 0,5") >= 0) {
    Serial.println(F("[MODULE] Registered on LTE network"));
  } else {
    Serial.println(F("[MODULE] Not registered yet - will retry"));
  }

  Serial.println(F("[MODULE] Ready"));
  return true;
}

// ============================================================
// Air780E — SEND DATA TO DASHBOARD (HTTP POST over 4G LTE)
// ============================================================
void sendWaterLevelAlert() {
  if (!moduleReady) {
    Serial.println(F("[HTTP] Module not ready - re-initializing"));
    moduleReady = initModule();
    if (!moduleReady) return;
  }

  readBatteryVoltage();

  // Build JSON data for HTTP
  String jsonData = "{";
  jsonData += "\"device_id\":\"" + DEVICE_ID + "\",";
  jsonData += "\"water_level_cm\":" + String(currentWaterLevel, 1) + ",";
  jsonData += "\"distance_cm\":" + String(currentDistance, 1) + ",";
  jsonData += "\"battery_v\":" + String(batteryVoltage, 1) + ",";
  jsonData += "\"signal\":" + String(moduleSignal) + ",";
  jsonData += "\"uptime_days\":" + String(millis() / 86400000);

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

  // Try HTTP POST first — Air780E uses AT+CGDCONT instead of AT+SAPBR
  Serial.println(F("[HTTP] Sending data to dashboard via 4G..."));
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

  if (httpSuccess) {
    alertSent = true;
    if (alertStr.length() > 0) {
      String smsMsg = buildSMSMessage();
      if (sendSMS(ALERT_PHONE, smsMsg)) {
        Serial.println(F("[SMS] Alert SMS sent"));
      }
    }
  } else {
    // HTTP failed — fall back to SMS
    Serial.println(F("[HTTP] Failed — falling back to SMS"));
    String smsMsg = buildSMSMessage();
    if (sendSMS(ALERT_PHONE, smsMsg)) {
      Serial.println(F("[SMS] Sent successfully (fallback)"));
      alertSent = true;
    } else {
      Serial.println(F("[SMS] Failed to send"));
      moduleReady = initModule();
      if (moduleReady) {
        if (sendSMS(ALERT_PHONE, buildSMSMessage())) {
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
  message += "Signal: " + String(moduleSignal) + "/31\n";

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
// HTTP — POST TO DASHBOARD (Air780E 4G LTE)
// ============================================================
bool sendHTTPPOST(const String& jsonData) {
  // Air780E uses AT+CGDCONT / AT+CGACT for PDP context (instead of AT+SAPBR)

  // 1. Set APN — activate PDP context
  String cgdCont = "AT+CGDCONT=1,\"IP\",\"";
  cgdCont += APN;
  cgdCont += "\"";
  if (!sendAT(cgdCont.c_str(), "OK", 5000)) {
    Serial.println(F("[HTTP] Failed to set PDP context"));
    return false;
  }

  // 2. Activate PDP context
  if (!sendAT("AT+CGACT=1,1", "OK", 15000)) {
    Serial.println(F("[HTTP] Failed to activate PDP context"));
    return false;
  }

  // 3. Get IP address (confirms LTE data is up)
  String ipResp = sendATWithResponse("AT+CGDCONT?", 3000);
  if (ipResp.indexOf("0.0.0.0") >= 0) {
    Serial.println(F("[HTTP] No IP assigned"));
    sendAT("AT+CGACT=0,1", "OK", 5000);
    return false;
  }

  // 4. Initialize HTTP service
  if (!sendAT("AT+HTTPINIT", "OK", 3000)) {
    Serial.println(F("[HTTP] HTTP init failed"));
    sendAT("AT+CGACT=0,1", "OK", 5000);
    return false;
  }

  // 5. Set bearer profile ID
  if (!sendAT("AT+HTTPPARA=\"CID\",1", "OK", 3000)) {
    Serial.println(F("[HTTP] Failed to set CID"));
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+CGACT=0,1", "OK", 5000);
    return false;
  }

  // 6. Set URL
  String urlCmd = "AT+HTTPPARA=\"URL\",\"";
  urlCmd += SERVER_URL;
  urlCmd += "\"";
  if (!sendAT(urlCmd.c_str(), "OK", 3000)) {
    Serial.println(F("[HTTP] Failed to set URL"));
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+CGACT=0,1", "OK", 5000);
    return false;
  }

  // 7. Set content type
  if (!sendAT("AT+HTTPPARA=\"CONTENT\",\"application/json\"", "OK", 3000)) {
    Serial.println(F("[HTTP] Failed to set content type"));
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+CGACT=0,1", "OK", 5000);
    return false;
  }

  // 8. Set data length — wait for "DOWNLOAD" prompt
  String dataLen = "AT+HTTPDATA=" + String(jsonData.length()) + "," + String(HTTP_TIMEOUT);
  if (!sendAT(dataLen.c_str(), "DOWNLOAD", 5000)) {
    Serial.println(F("[HTTP] Failed to start data upload"));
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+CGACT=0,1", "OK", 5000);
    return false;
  }

  // 9. Send the JSON payload
  moduleSerial.print(jsonData);
  delay(200);
  moduleSerial.write(26);   // Ctrl+Z to signal end
  delay(100);

  // Wait for OK
  unsigned long start = millis();
  bool dataAccepted = false;
  while (millis() - start < 15000) {
    if (moduleSerial.available()) {
      String resp = moduleSerial.readString();
      if (resp.indexOf("OK") >= 0) {
        dataAccepted = true;
        break;
      }
    }
  }

  if (!dataAccepted) {
    Serial.println(F("[HTTP] Data not accepted by module"));
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+CGACT=0,1", "OK", 5000);
    return false;
  }

  // 10. Execute HTTP POST
  Serial.println(F("[HTTP] Sending POST..."));
  String postResp = sendATWithResponse("AT+HTTPACTION=1", 30000);

  // Check response code — 200 or 201 means success
  if (postResp.indexOf("+HTTPACTION: 1,200") >= 0 || postResp.indexOf("+HTTPACTION: 1,201") >= 0) {
    Serial.println(F("[HTTP] Server returned 200/201 OK"));
    // Read response body
    String body = sendATWithResponse("AT+HTTPREAD", 5000);
    Serial.print(F("[HTTP] Response: "));
    Serial.println(body.substring(0, 80));

    // Cleanup
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+CGACT=0,1", "OK", 5000);
    return true;
  } else {
    Serial.print(F("[HTTP] Server response: "));
    Serial.println(postResp.substring(0, 60));
    // Cleanup
    sendAT("AT+HTTPTERM", "OK", 2000);
    sendAT("AT+CGACT=0,1", "OK", 5000);
    return false;
  }
}

// ============================================================
// SMS — SEND
// ============================================================
bool sendSMS(const String& number, const String& message) {
  moduleSerial.print("AT+CMGS=\"");
  moduleSerial.print(number);
  moduleSerial.println("\"");

  delay(500);

  unsigned long start = millis();
  bool promptReceived = false;
  while (millis() - start < 5000) {
    if (moduleSerial.available()) {
      char c = moduleSerial.read();
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

  moduleSerial.print(message);
  delay(100);
  moduleSerial.write(26);  // Ctrl+Z
  delay(100);

  start = millis();
  while (millis() - start < 10000) {
    if (moduleSerial.available()) {
      String resp = moduleSerial.readString();
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
// SMS — COMMAND RECEPTION
// ============================================================
void checkSMSCommands() {
  if (!moduleReady) return;

  moduleSerial.println("AT+CMGL=\"REC UNREAD\",1");

  unsigned long start = millis();
  while (millis() - start < 3000) {
    if (moduleSerial.available()) {
      String resp = moduleSerial.readString();

      if (resp.indexOf("+CMGL:") >= 0) {
        Serial.println(F("[SMS CMD] Incoming command detected"));

        int idx = resp.indexOf("\r\n\r\n");
        if (idx > 0) {
          String body = resp.substring(idx + 4);
          body.trim();
          int okIdx = body.indexOf("OK");
          if (okIdx >= 0) body = body.substring(0, okIdx);
          body.trim();
          body.toUpperCase();

          Serial.print(F("[SMS CMD] Command: "));
          Serial.println(body);

          String reply;
          if (body.indexOf("STATUS") >= 0 || body.indexOf("LEVEL") >= 0) {
            reply = "RF " + DEVICE_ID + ": Level=" + String(currentWaterLevel, 1) + "cm, Bat=" + String(batteryVoltage, 1) + "V, Signal=" + String(moduleSignal) + "/31";
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

          int numStart = resp.indexOf("\"REC UNREAD\",\"") + 15;
          int numEnd = resp.indexOf("\"", numStart);
          String sender = resp.substring(numStart, numEnd);
          sendSMS(sender, reply);
        }

        moduleSerial.println("AT+CMGDA=\"DEL READ\"");
        delay(500);
        while (moduleSerial.available()) moduleSerial.read();
      }
    }
  }
}

// ============================================================
// THRESHOLD ALERT CHECKING
// ============================================================
void checkThresholdAlerts() {
  if (currentWaterLevel < 0) return;

  bool highAlert  = (currentWaterLevel >= ALERT_HIGH_CM);
  bool lowAlert   = (currentWaterLevel <= ALERT_LOW_CM) && (currentWaterLevel >= 0);

  if ((highAlert || lowAlert) && !alertSent) {
    Serial.println(F("[ALERT] Threshold crossed - sending immediate alert"));
    sendWaterLevelAlert();
    alertSent = true;
  }

  if (!highAlert && !lowAlert) {
    alertSent = false;
  }
}

// ============================================================
// AT COMMAND HELPERS
// ============================================================
bool sendAT(const String& cmd, const String& expected, unsigned long timeout) {
  moduleSerial.println(cmd);
  unsigned long start = millis();
  while (millis() - start < timeout) {
    if (moduleSerial.available()) {
      String resp = moduleSerial.readString();
      Serial.print(F("[MOD TX] "));
      Serial.print(cmd);
      Serial.print(F(" -> "));
      Serial.println(resp.substring(0, 80));
      if (resp.indexOf(expected) >= 0) {
        return true;
      }
    }
  }
  Serial.print(F("[MOD] Timeout waiting for: "));
  Serial.println(expected);
  return false;
}

String sendATWithResponse(const String& cmd, unsigned long timeout) {
  moduleSerial.println(cmd);
  unsigned long start = millis();
  String fullResp;
  while (millis() - start < timeout) {
    if (moduleSerial.available()) {
      char c = moduleSerial.read();
      fullResp += c;
    }
  }
  Serial.print(F("[MOD RSP] "));
  Serial.println(fullResp.substring(0, 120));
  return fullResp;
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
      moduleReady = initModule();
    } else if (cmd == "HELP" || cmd == "H") {
      Serial.println(F("--- Serial Commands ---"));
      Serial.println(F("MEASURE/M  - Take a reading"));
      Serial.println(F("SMS/SEND   - Send alert SMS"));
      Serial.println(F("BAT/BATTERY - Read battery"));
      Serial.println(F("GSM/INIT   - Re-init module"));
      Serial.println(F("HELP/H     - This menu"));
    } else if (cmd.length() > 0) {
      // Passthrough to Air780E for debugging
      moduleSerial.println(cmd);
      delay(1000);
      while (moduleSerial.available()) {
        Serial.write(moduleSerial.read());
      }
    }
  }
}
