// changelog
/// 11060101 - (date est) Based code off of Ladyada's GPS Module Code
///            http://www.ladyada.net/make/gpsshield/download.html 
/// 11061601 - changed it so that gps logs have 3 digits not 2
/// 11072501 - changed it so that gps will log time till first fix
/// 11092701 - added dumb LCD support
/// 12070601 - needed to fix numbers above 100. took the opportunity to improve a few things...
///          - updated to work with arduino 1.0.1 (NewSoftSerial now SoftwareSerial)
/// 12082901 - stopped compass from updating under speeds of 4knots
/// 12083101 - compass didnt seem to keep setting. under speeds of 3knots would BLANK out

// *************
// *** Libraries
#include <SD.h>
#include <avr/sleep.h>
#include <LiquidCrystal.h>
#include <stdio.h>
#include <SoftwareSerial.h> // Old- NewSoftSerial

// **************************
// *** Version ID for Display
#define versionID "2012-08-31"

// ****************************
// *** GPS Module Configuration
/* EXAMPLE

$PSRF103,<msg>,<mode>,<rate>,<cksumEnable>*CKSUM<CR><LF>

<msg> 00=GGA,01=GLL,02=GSA,03=GSV,04=RMC,05=VTG
<mode> 00=SetRate,01=Query
<rate> Output every <rate>seconds, off=00,max=255
<cksumEnable> 00=disable Checksum,01=Enable checksum for specified message
Note: checksum is required

Example 1: Query the GGA message with checksum enabled
$PSRF103,00,01,00,01*25

Example 2: Enable VTG message for a 1Hz constant output with checksum enabled
$PSRF103,05,00,01,01*20

Example 3: Disable VTG message
$PSRF103,05,00,00,01*21

*/
#define SERIAL_SET   "$PSRF100,01,4800,08,01,00*0E\r\n"
// GGA-Global Positioning System Fixed Data, message 103,00
#define GGA_OFF  "$PSRF103,00,00,00,01*24\r\n"
// GLL-Geographic Position-Latitude/Longitude, message 103,01
#define GLL_OFF  "$PSRF103,01,00,00,01*27\r\n"
// GSA-GNSS DOP and Active Satellites, message 103,02
#define GSA_OFF  "$PSRF103,02,00,00,01*26\r\n"
// GSV-GNSS Satellites in View, message 103,03
#define GSV_OFF  "$PSRF103,03,00,00,01*27\r\n"
// RMC-Recommended Minimum Specific GNSS Data, message 103,04
#define RMC_ON   "$PSRF103,04,00,01,01*21\r\n"
// VTG-Course Over Ground and Ground Speed, message 103,05
#define VTG_OFF  "$PSRF103,05,00,00,01*21\r\n"
// Switch Development Data Messages On/Off, message 105
#define DDM_OFF  "$PSRF105,00*3F\r\n"
// the command for turning off WAAS
#define WAAS_OFF   "$PSRF151,00*3E\r\n"     

// ************************
// *** Power Savings Settings
#define SLEEPDELAY 0
#define TURNOFFGPS 0
#define LOG_RMC_FIXONLY 0

// ************************
// *** Serial Configuration
// Use pins 2 and 3 to talk to the GPS. 2 is the TX pin, 3 is the RX pin
//SoftwareSerial gpsSerial =  SoftwareSerial(2, 3);
SoftwareSerial gpsSerial =  SoftwareSerial(3, 2);

// Set the GPSRATE to the baud rate of the GPS module. Most are 4800
// but some are 38400 or other. Check the datasheet!
#define GPSRATE 4800

// *********************
// *** LCD Configuration
#define chipSelect 8
LiquidCrystal lcd(1,0,7,6,5,4);

// ********************
// *** Global Variables
#define BUFFSIZE 90
char buffer[BUFFSIZE];
uint8_t bufferidx = 0;
uint8_t fix = 0; // current fix data
uint8_t i;
File logfile;
uint8_t indices[13];
char datetime[9];

int angle=0;	
String angle_str;
int len = 0;

// added 7/25/11
// will count time till first fix
uint8_t first_fix = 1;
unsigned long time;

// *** ******************
// *** FUNCTION: parseHex - converts hex to dec
// ***    input c: character input (ascii encoded hex)
// ***    returns: 0-15
uint8_t parseHex(char c) {
  if (c < '0')
    return 0;
  if (c <= '9')
    return c - '0';
  if (c < 'A')
    return 0;
  if (c <= 'F')
    return (c - 'A')+10;
}

// *** ******************
// *** FUNCTION: error - prints an error to the screen
// ***    input  : the error message
// ***    returns: nothing
void error(char errmsg[]) {
  lcd.clear();
  lcd.print("Error:");
  lcd.setCursor(0,1);
  lcd.print(errmsg);
  while (1) {}
}

// *** ******************
// *** FUNCTION: gps_rx
// ***    input  : the string buffer from the GPS unit
void gps_rx(char buf[]){

        // here is the format of the string we've read in...
  	//$GPRMC,155626.370,V,,,,,,,120911,,*22 [[37]]
	//$GPRMC,155627.370,A,3912.4470,N,07640.8169,W,13.61,107.98,120911,,*22 [[67]]
        // index0          1 2         3 4          5 6     7      8      90    
        //  0   155627     Time Stamp
        //  1   A          validity - A-ok, V-invalid
        //  2   3912.44    current Latitude
        //  3   N          North/South
        //  4   07640.81   current Longitude
        //  5   W          East/West
        //  6   13.61      Speed in knots
        //  7   107.98     True course (heading)
        //  8   120911     Date Stamp
        //  9   004.2      Variation
        //  10  W          East/West
        //  11  *70        checksum                
        
	uint8_t cont = 0;
	String strbuf(buf);
	char line1[BUFFSIZE];
	char line2[BUFFSIZE];	
	
	/* quick and dirty valid check. see if length is greater than 40 and consider it valid.. */
	if(strlen(buf)>40) {
		for (int i=0;i<strlen(buf);i++){
			if (buf[i]==','){    // check for the position of the  "," separator
				indices[cont]=i;
				cont++;
			}
			if (buf[i]=='*'){    // ... and the "*"
				indices[12]=i;
				cont++;
			}
		} /* loop through line */

                // Okay! We have a valid fix right now, and we have the indexes of the separation chars
                // This is an excellent time to print information on the screen
                
                lcd.clear();
		
//              // (legacy) print out the current GPS coordinates (ignoring sign)
//              // believe me, this is latitude
//   		lcd.print(strbuf.substring(indices[2]+1,indices[3]));
//              // and this is longitude
//		lcd.setCursor(0,1);
//		lcd.print(strbuf.substring(indices[4]+1,indices[5]));

                
                /// CHECK SPEED >=3knots
                // ghetto check, its greater than 3 knots if the first number > 3
                // but wait! what if 1X knots? or 2X knots? like 20? well then just 
                // ensure that the 2nd number ISNT a period (46 ASCII)
                if((parseHex(strbuf[indices[6]+1]) > 3) || (strbuf[indices[6]+2] != 46))
                {
                  // Analyze the angle...
                  len = indices[8]-indices[7]-1; // 7.85 = len4; 10.77 = len5; 100.01 = len6  
                  
                  angle_str = strbuf.substring(indices[7]+1,indices[8]);
                  
                  if(len==6) {
                    angle = parseHex(strbuf[indices[7]+1])*100 +
                            parseHex(strbuf[indices[7]+2])*10 +
                            parseHex(strbuf[indices[7]+3]);
                  } else if (len==5) {
                    angle = parseHex(strbuf[indices[7]+1])*10 +
                            parseHex(strbuf[indices[7]+2]);
                  } else if (len==4) {
                    angle = parseHex(strbuf[indices[7]+1]);
                  }                     
                } else {
                  // speed < 3 knots 
                  // keep angle
                  // keep angle_str
                  // keep len
                }
                
                // Print the heading (angle)
    		lcd.print(angle_str);
                lcd.setCursor(len,0);
                lcd.print((char)223);  

                // Goto next line and print direction               
                lcd.setCursor(0,1);
                if(angle > 337 || angle < 23) 
                  lcd.print("N");
                else if (angle < 68)
                  lcd.print("NE"); 
                else if (angle < 113)
                  lcd.print("E");
                else if (angle < 158)
                  lcd.print("SE");
                else if (angle < 203)
                  lcd.print("S");
                else if (angle < 248)
                  lcd.print("SW");
                else if (angle < 293)
                  lcd.print("W");
                else if (angle < 338)
                  lcd.print("NW");
                  
                lcd.setCursor(9,0);
                lcd.print("elapsed");  

                // Print the Time Elapsed since Bootup (ignition)
                long hours=0;
                long mins=0;
                long secs=0;
                secs = millis()/1000; //convect milliseconds to seconds
                mins=secs/60; //convert seconds to minutes
                hours=mins/60; //convert minutes to hours
                secs=secs-(mins*60); //subtract the coverted seconds to minutes in order to display 59 secs max
                mins=mins-(hours*60); //subtract the coverted minutes to hours in order to display 59 minutes max
                hours=hours; //subtract the coverted hours to days in order to display 23 hours max
		lcd.setCursor(9,1);
                //Display results
                lcd.print(hours);
                if(mins<10){
		  lcd.setCursor(10,1);
                  lcd.print(":0");
                  lcd.setCursor(12,1);
                  lcd.print(mins);
                } else {
		  lcd.setCursor(10,1);
                  lcd.print(":");
                  lcd.setCursor(11,1);
                  lcd.print(mins);
                }
                if(secs<10){
                  lcd.setCursor(13,1);
                  lcd.print(":0");
                  lcd.setCursor(15,1);
                  lcd.print(secs);
                } else {
                  lcd.setCursor(13,1);
                  lcd.print(":");
                  lcd.setCursor(14,1);
                  lcd.print(secs);
                }
             
  }
}

void setup() {
  WDTCSR |= (1 << WDCE) | (1 << WDE);
  WDTCSR = 0;

  // initialize the LCD screen, and display a rev message while booting
  lcd.begin(16,2);  
  lcd.setCursor(0, 0);
  lcd.print("rev: ");
  lcd.setCursor(5, 0);
  lcd.print(versionID);
  
  // make sure that the default chip select pin is set to
  // output, even if you don't use it:
  pinMode(10, OUTPUT);
  
  // see if the card is present and can be initialized:
  if (!SD.begin(chipSelect)) {
    error("Card init. fail");
  }

  // figure out what file to use
  strcpy(buffer, "GPSLG000.TXT");
  for (i = 0; i < 999; i++) {
    buffer[5] = '0' + i/100;
    buffer[6] = '0' + (i%100)/10;
    buffer[7] = '0' + i%10;
    // create if does not exist, do not open existing, write, sync after write
    if (! SD.exists(buffer)) {
      break;
    }
  }

  logfile = SD.open(buffer, FILE_WRITE);
  if( ! logfile ) {
    error("New File Err");
  }
  
  // print the filename on the next line
  lcd.setCursor(0,1);
  lcd.print(buffer);
  
  // connect to the GPS and configure it as desired
  // Using: RMC-Recommended Minimum Specific GNSS Data, message 103,04
  gpsSerial.begin(GPSRATE);
  gpsSerial.print(SERIAL_SET);
  delay(250);
  gpsSerial.print(DDM_OFF);
  delay(250);
  gpsSerial.print(GGA_OFF);
  delay(250);
  gpsSerial.print(GLL_OFF);
  delay(250);
  gpsSerial.print(GSA_OFF);
  delay(250);
  gpsSerial.print(GSV_OFF);
  delay(250);
  gpsSerial.print(RMC_ON);
  delay(250);
  gpsSerial.print(VTG_OFF);
  delay(250);
  gpsSerial.print(WAAS_OFF);
}

void loop() {
  //Serial.println(Serial.available(), DEC);
  char c;
  uint8_t sum;

  // read one 'line'
  if (gpsSerial.available()) {
    c = gpsSerial.read();
    //Serial.print(c, BYTE);
    if (bufferidx == 0) {
      while (c != '$')
        c = gpsSerial.read(); // wait till we get a $
    }
    buffer[bufferidx] = c;

    //Serial.print(c, BYTE);
    if (c == '\n') {
      //putstring_nl("EOL");
      //Serial.print(buffer);
      buffer[bufferidx+1] = 0; // terminate it

      if (buffer[bufferidx-4] != '*') {
        // no checksum?
        //Serial.print('*', BYTE);
        bufferidx = 0;
        return;
      }
      // get checksum
      sum = parseHex(buffer[bufferidx-3]) * 16;
      sum += parseHex(buffer[bufferidx-2]);

      // check checksum
      for (i=1; i < (bufferidx-4); i++) {
        sum ^= buffer[i];
      }
      if (sum != 0) {
        //putstring_nl("Cxsum mismatch");
        //Serial.print('~', BYTE);
        bufferidx = 0;
        return;
      }
      // got good data!

      if (strstr(buffer, "GPRMC")) {
        // find out if we got a fix
        char *p = buffer;
        p = strchr(p, ',')+1;
        p = strchr(p, ',')+1;       // skip to 3rd item

        if (p[0] == 'V') {
          fix = 0;
        } else {
          fix = 1;
          
          if(first_fix == 1) {
            // print to serial
            //Serial.print("First fix after ");
            time = millis();
            //Serial.print(time);
            //Serial.println("seconds!");
            
            // print to log
            logfile.print("startup_delay,");
            logfile.println(time);           
            
            first_fix = 0;
          }
        }
      }
      if (LOG_RMC_FIXONLY) {
        if (!fix) {
          //Serial.print('_', BYTE);
          bufferidx = 0;
          return;
        }
      }
      // rad. lets log it!
	  gps_rx(buffer);

      // Bill Greiman - need to write bufferidx + 1 bytes to getCR/LF
      bufferidx++;

      logfile.write((uint8_t *) buffer, bufferidx);
      logfile.flush();

      bufferidx = 0;

      // turn off GPS module?
      if (TURNOFFGPS) {
        //digitalWrite(powerPin, HIGH);
      }

      sleep_sec(SLEEPDELAY);
      //digitalWrite(powerPin, LOW);
      return;
    }
    bufferidx++;
    if (bufferidx == BUFFSIZE-1) {
       //Serial.print('!', BYTE);
       bufferidx = 0;
    }
  } else {

  }

}

uint32_t parsedecimal(char *str) {
  uint32_t d = 0;
  
  while (str[0] != 0) {
   if ((str[0] > '9') || (str[0] < '0'))
     return d;
   d *= 10;
   d += str[0] - '0';
   str++;
  }
  return d;
}

void sleep_sec(uint8_t x) {
  while (x--) {
     // set the WDT to wake us up!
    WDTCSR |= (1 << WDCE) | (1 << WDE); // enable watchdog & enable changing it
    WDTCSR = (1<< WDE) | (1 <<WDP2) | (1 << WDP1);
    WDTCSR |= (1<< WDIE);
    set_sleep_mode(SLEEP_MODE_PWR_DOWN);
    sleep_enable();
    sleep_mode();
    sleep_disable();
  }
}

SIGNAL(WDT_vect) {
  WDTCSR |= (1 << WDCE) | (1 << WDE);
  WDTCSR = 0;
}

/* End code */
