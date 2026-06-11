#!/usr/bin/env python3 -u
"""Bridge: Arduino → PC → A7680C USB → 4G LTE → Dashboard"""
import serial, time, sys, json, urllib.request

ARDUINO_PORT = '/dev/ttyACM0'
MODEM_PORT = '/dev/ttyUSB2'
API_URL = 'http://water-monitoring.ddns.net/api/reading.php'

def find_ports():
    import serial.tools.list_ports
    for p in serial.tools.list_ports.comports():
        d = p.device or ''
        if 'ACM' in d or 'USB' in d:
            print(f"  {d} — {p.description or '?'}")

print("Available ports:")
find_ports()

# Open both
try:
    arduino = serial.Serial(ARDUINO_PORT, 9600, timeout=1)
    arduino.dtr = False
    time.sleep(0.3)
    arduino.reset_input_buffer()
    print(f"✅ Arduino on {ARDUINO_PORT}")
except Exception as e:
    print(f"❌ Arduino: {e}")
    sys.exit(1)

try:
    modem = serial.Serial(MODEM_PORT, 115200, timeout=1)
    time.sleep(0.5)
    modem.reset_input_buffer()
    print(f"✅ Modem on {MODEM_PORT}")
except Exception as e:
    print(f"❌ Modem: {e}")
    sys.exit(1)

# Init modem
modem.write(b'AT+CGACT=1,1\r\n')
time.sleep(3)
modem.read(500)

print("🔄 Bridge running. Reading Arduino and forwarding via 4G LTE...")
print("─" * 50)

while True:
    try:
        raw = arduino.read(500)
        if not raw:
            time.sleep(0.3)
            continue
        
        lines = raw.decode(errors='replace').split('\n')
        for line in lines:
            line = line.strip()
            if not line:
                continue
            
            # Look for measurement lines
            if '[MEASURE]' in line:
                print(f"📡 {line}")
                
                # Parse values
                dist = wl = '0'
                if 'Distance:' in line:
                    dist = line.split('Distance:')[1].split('cm')[0].strip()
                if 'Water Level:' in line:
                    wl = line.split('Water Level:')[1].split('cm')[0].strip()
                
                # Build GET URL
                url = f"{API_URL}?device_id=RF01&water_level_cm={wl}&distance_cm={dist}&battery_v=5.0&signal=20&reading_mode=lte"
                
                # Send via modem
                modem.write(b'AT+HTTPINIT\r\n')
                time.sleep(2)
                modem.read(500)
                
                modem.write(f'AT+HTTPPARA="URL","{url}"\r\n'.encode())
                time.sleep(1)
                modem.read(500)
                
                modem.write(b'AT+HTTPACTION=0\r\n')
                time.sleep(10)
                
                resp = b''
                while modem.in_waiting:
                    resp += modem.read(modem.in_waiting)
                    time.sleep(0.3)
                r = resp.decode(errors='replace')
                
                if '200' in r:
                    print(f"✅ → Dashboard ({wl}cm)")
                else:
                    print(f"❌ → {r[:60]}")
                
                modem.write(b'AT+HTTPTERM\r\n')
                time.sleep(1)
                modem.read(500)
            
            elif line:
                print(f"  {line}")
    
    except KeyboardInterrupt:
        break
    except Exception as e:
        print(f"⚠️ {e}")
        time.sleep(1)

arduino.close()
modem.close()
print("\nStopped.")
