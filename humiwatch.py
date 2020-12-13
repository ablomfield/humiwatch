# -*- coding:UTF-8 -*-

#-------------Import Settings-----------------#
import yaml
yamlfile = open("settings.yaml")
yamlsettings = yaml.load(yamlfile, Loader=yaml.FullLoader)

#--------------Driver Library-----------------#
import RPi.GPIO as GPIO
import OLED_Driver as OLED
import time
from datetime import datetime
from datetime import timedelta
import pymysql
import Adafruit_DHT

#---------------Image Library-----------------#
from PIL  import Image
from PIL import ImageDraw
from PIL import ImageFont
from PIL import ImageColor

#--------------Define Settings----------------#
DHT_SENSOR = Adafruit_DHT.DHT22
strVersion = "0.1"
nextupdate = datetime.now()

#-------------Display Functions---------------#
def Start_Splash():
    image = Image.new("RGB", (OLED.SSD1351_WIDTH, OLED.SSD1351_HEIGHT), "BLACK")
    draw = ImageDraw.Draw(image)
    logohw = Image.open("humiwatch.png")
    image.paste(logohw,(0,0))
    font = ImageFont.truetype('cambriab.ttf',12)
    draw.text((12, 110), 'Version ' + strVersion, fill = "WHITE", font = font)
    image.save("web/oled.png")
    OLED.Display_Image(image)

def Display_Stats(strTime,strLabel,strTemp,strHum,strTCol,strHCol):
    image = Image.new("RGB", (OLED.SSD1351_WIDTH, OLED.SSD1351_HEIGHT), "BLACK")
    draw = ImageDraw.Draw(image)
    icontemp = Image.open("icon-temp.png")
    iconhum = Image.open("icon-hum.png")
    datefont = ImageFont.truetype('cambriab.ttf',16)
    statfont = ImageFont.truetype('cambriab.ttf',24)
    draw.text((60, 6), strTime, fill = "WHITE", font = datefont)
    image.paste(icontemp,(10,36))
    draw.text((50, 30), strTemp, fill = strTCol,font = statfont)
    image.paste(iconhum,(10,72))
    draw.text((50, 66), strHum, fill = strHCol, font = statfont)
    draw.text((14, 105), strLabel, fill = "WHITE", font = datefont)
    image.save("web/oled.png")
    OLED.Display_Image(image)

#-----------Connect Database-------------#
#  DB settings pulled from settings.yaml #
#         (see settings.example)         #
#----------------------------------------#
dbServerName    = yamlsettings["Database"]["ServerName"]
dbUser          = yamlsettings["Database"]["Username"]
dbPassword      = yamlsettings["Database"]["Password"]
dbName          = yamlsettings["Database"]["DBName"]
charSet         = "utf8mb4"
cusrorType      = pymysql.cursors.DictCursor

try:
    #---------Display Startup----------#
    OLED.Device_Init()
    Start_Splash()
    OLED.Delay(2000)

    while (True):
        #------Retrieve Settings-------#
        DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword, db=dbName, charset=charSet,cursorclass=cusrorType)
        objsettings = DBConn.cursor()
        objsettings.execute("SELECT * FROM settings")
        rssettings = objsettings.fetchone()
        tempunits = rssettings["tempunits"]
        pushenable = rssettings["pushenable"]
        pushkey = rssettings["pushkey"]
        pushuser = rssettings["pushuser"]
        rssettings.clear
        objsettings.close
        DBConn.close

        #------Initialize Pushover-----#
        if pushenable == 1:
            from pushover import Pushover
            po = Pushover(pushkey)
            po.user(pushuser)

        #--------Check Sensor 1--------#
        now = datetime.now()
        strTime = now.strftime("%-I:%M %p")
        DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword, db=dbName, charset=charSet,cursorclass=cusrorType)
        objSensor = DBConn.cursor()
        objSensor.execute("SELECT * FROM sensors WHERE sensorid = 1")
        rsSensor = objSensor.fetchone()
        sensorname = rsSensor["name"]
        sensorenabled = rsSensor["enabled"]
        enabled1 = rsSensor["enabled"]
        display = rsSensor["display"]
        alert = rsSensor["alert"]
        alertint = rsSensor["alertint"]
        gpiopin = rsSensor["gpiopin"]
        calm = rsSensor["calm"]
        cala = rsSensor["cala"]
        tmin = rsSensor["tmin"]
        tmax = rsSensor["tmax"]
        talertstat = rsSensor["talertstat"]
        talertval = rsSensor["talertval"]
        talerttime = rsSensor["talerttime"]
        talertnext = rsSensor["talertnext"]
        hmin = rsSensor["hmin"]
        hmax = rsSensor["hmax"]
        halertstat = rsSensor["halertstat"]
        halertval = rsSensor["halertval"]
        halerttime = rsSensor["halerttime"]
        halertnext = rsSensor["halertnext"]
        rsSensor.clear
        objSensor.close
        DBConn.close
        if sensorenabled == 1:
            h1, t1 = Adafruit_DHT.read_retry(DHT_SENSOR, gpiopin)
            h1 = h1 * calm + cala
            if tempunits == "F":
                strT1 = str(round(t1 * 1.8 + 32,1)) + "°" + tempunits
                strTMin = str(round(tmin * 1.8 + 32,1))
                strTMax = str(round(tmax * 1.8 + 32,1))
            else:
                strT1 = str(t1) + "°" + tempunits
                strTMin = str(tmin)
                strTMax = str(tmax)
            strH1 = str(round(h1,1)) + "%"
            strHMin = str(round(hmin,1)) + "%"
            strHMax = str(round(hmax,1)) + "%"
            if t1 < tmin or t1 > tmax:
                strTCol = "RED"
                if alert == 1 and now > talertnext:
                    DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                    objalert = DBConn.cursor()
                    objalert.execute("UPDATE sensors SET talertstat = '1', talertval = '" + str(t1) + "', talerttime = '" + str(now) + "', talertnext = '" + str(datetime.now() + timedelta(seconds=alertint)) + "' WHERE sensorid ='1'")
                    DBConn.commit()
                    objalert.close
                    DBConn.close
                    msg = po.msg("Sensor '" + sensorname + "' is in alert! Current temperature of " + strT1 + " is outside the threshold of " + strTMin + " to " + strTMax + ".")
                    msg.set("title", "HumiWatch Alert (Temperature)")
                    po.send(msg)
            else:
                strTCol = "GREEN"
                if alert == 1 and talertstat == 1 and now > (talerttime + timedelta(seconds=300)):
                        DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                        objalert = DBConn.cursor()
                        objalert.execute("UPDATE sensors SET talertstat = '0', talertval = '0', talerttime = '" + str(now) + "', talertnext = '" + str(now) + "' WHERE sensorid ='1'")
                        DBConn.commit()
                        objalert.close
                        DBConn.close
                        msg = po.msg("Sensor '" + sensorname + "' is no longer in alert. Current temperature of " + strT1 + " is inside the threshold of " + strTMin + " to " + strTMax + ".")
                        msg.set("title", "HumiWatch Alert (Temperature)")
                        po.send(msg)
            if h1 < hmin or h1 > hmax:
                strHCol = "RED"
                if alert == 1 and now > halertnext:
                    DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                    objalert = DBConn.cursor()
                    objalert.execute("UPDATE sensors SET halertstat = '1', halertval = '" + str(h1) + "', halerttime = '" + str(now) + "', halertnext = '" + str(datetime.now() + timedelta(seconds=alertint)) + "' WHERE sensorid ='1'")
                    DBConn.commit()
                    objalert.close
                    DBConn.close
                    msg = po.msg("Sensor '" + sensorname + "' is in alert! Current humidity of " + strH1 + " is outside the threshold of " + strHMin + " to " + strHMax + ".")
                    msg.set("title", "HumiWatch Alert (Humidity)")
                    po.send(msg)
            else:
                strHCol = "GREEN"
                if alert == 1 and halertstat == 1 and now > (halerttime + timedelta(seconds=300)):
                        DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                        objalert = DBConn.cursor()
                        objalert.execute("UPDATE sensors SET halertstat = '0', halertval = '0', halerttime = '" + str(now) + "', halertnext = '" + str(now) + "' WHERE sensorid ='1'")
                        DBConn.commit()
                        objalert.close
                        DBConn.close
                        msg = po.msg("Sensor '" + sensorname + "' is no longer in alert. Current humidity of " + strH1 + " is inside the threshold of " + strHMin + " to " + strHMax + ".")
                        msg.set("title", "HumiWatch Alert (Humidity)")
                        po.send(msg)
            if display == 1:
                Display_Stats(strTime,sensorname,strT1,strH1,strTCol,strHCol)
                time.sleep(3)
            DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
            objrealtime = DBConn.cursor()
            objrealtime.execute("UPDATE sensors SET realtime='" + str(now) + "', realt='" + str(t1) + "', realh='" + str(h1) + "' WHERE sensorid=1")
            DBConn.commit()
            objrealtime.close

        #--------Check Sensor 2--------#
        now = datetime.now()
        strTime = now.strftime("%-I:%M %p")
        DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword, db=dbName, charset=charSet,cursorclass=cusrorType)
        objSensor = DBConn.cursor()
        objSensor.execute("SELECT * FROM sensors WHERE sensorid = 2")
        rsSensor = objSensor.fetchone()
        sensorname = rsSensor["name"]
        sensorenabled = rsSensor["enabled"]
        enabled2 = rsSensor["enabled"]
        display = rsSensor["display"]
        alert = rsSensor["alert"]
        alertint = rsSensor["alertint"]
        gpiopin = rsSensor["gpiopin"]
        calm = rsSensor["calm"]
        cala = rsSensor["cala"]
        tmin = rsSensor["tmin"]
        tmax = rsSensor["tmax"]
        talertstat = rsSensor["talertstat"]
        talertval = rsSensor["talertval"]
        talerttime = rsSensor["talerttime"]
        talertnext = rsSensor["talertnext"]
        hmin = rsSensor["hmin"]
        hmax = rsSensor["hmax"]
        halertstat = rsSensor["halertstat"]
        halertval = rsSensor["halertval"]
        halerttime = rsSensor["halerttime"]
        halertnext = rsSensor["halertnext"]
        rsSensor.clear
        objSensor.close
        DBConn.close
        if sensorenabled == 1:
            h2, t2 = Adafruit_DHT.read_retry(DHT_SENSOR, gpiopin)
            h2 = h2 * calm + cala
            if tempunits == "F":
                strT2 = str(round(t2 * 1.8 + 32,1)) + "°" + tempunits
                strTMin = str(round(tmin * 1.8 + 32,1))
                strTMax = str(round(tmax * 1.8 + 32,1))
            else:
                strT2 = str(t2) + "°" + tempunits
                strTMin = str(tmin)
                strTMax = str(tmax)
            strH2 = str(round(h2,1)) + "%"
            strHMin = str(round(hmin,1)) + "%"
            strHMax = str(round(hmax,1)) + "%"
            if t2 < tmin or t2 > tmax:
                strTCol = "RED"
                if alert == 1 and now > talertnext:
                    DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                    objalert = DBConn.cursor()
                    objalert.execute("UPDATE sensors SET talertstat = '1', talertval = '" + str(t2) + "', talerttime = '" + str(now) + "', talertnext = '" + str(datetime.now() + timedelta(seconds=alertint)) + "' WHERE sensorid ='2'")
                    DBConn.commit()
                    objalert.close
                    DBConn.close
                    msg = po.msg("Sensor '" + sensorname + "' is in alert! Current temperature of " + strT2 + " is outside the threshold of " + strTMin + " to " + strTMax + ".")
                    msg.set("title", "HumiWatch Alert (Temperature)")
                    po.send(msg)
            else:
                strTCol = "GREEN"
                if alert == 1 and talertstat == 1 and now > (talerttime + timedelta(seconds=300)):
                        DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                        objalert = DBConn.cursor()
                        objalert.execute("UPDATE sensors SET talertstat = '0', talertval = '0', talerttime = '" + str(now) + "', talertnext = '" + str(now) + "' WHERE sensorid ='2'")
                        DBConn.commit()
                        objalert.close
                        DBConn.close
                        msg = po.msg("Sensor '" + sensorname + "' is no longer in alert. Current temperature of " + strT2 + " is inside the threshold of " + strTMin + " to " + strTMax + ".")
                        msg.set("title", "HumiWatch Alert (Temperature)")
                        po.send(msg)
            if h2 < hmin or h2 > hmax:
                strHCol = "RED"
                if alert == 1 and now > halertnext:
                    DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                    objalert = DBConn.cursor()
                    objalert.execute("UPDATE sensors SET halertstat = '1', halertval = '" + str(h2) + "', halerttime = '" + str(now) + "', halertnext = '" + str(datetime.now() + timedelta(seconds=alertint)) + "' WHERE sensorid ='2'")
                    DBConn.commit()
                    objalert.close
                    DBConn.close
                    msg = po.msg("Sensor '" + sensorname + "' is in alert! Current humidity of " + strH2 + " is outside the threshold of " + strHMin + " to " + strHMax + ".")
                    msg.set("title", "HumiWatch Alert (Humidity)")
                    po.send(msg)
            else:
                strHCol = "GREEN"
                if alert == 1 and halertstat == 1 and now > (halerttime + timedelta(seconds=300)):
                        DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                        objalert = DBConn.cursor()
                        objalert.execute("UPDATE sensors SET halertstat = '0', halertval = '0', halerttime = '" + str(now) + "', halertnext = '" + str(now) + "' WHERE sensorid ='2'")
                        DBConn.commit()
                        objalert.close
                        DBConn.close
                        msg = po.msg("Sensor '" + sensorname + "' is no longer in alert. Current humidity of " + strH2 + " is inside the threshold of " + strHMin + " to " + strHMax + ".")
                        msg.set("title", "HumiWatch Alert (Humidity)")
                        po.send(msg)
            if display == 1:
                Display_Stats(strTime,sensorname,strT2,strH2,strTCol,strHCol)
                time.sleep(3)
            DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
            objrealtime = DBConn.cursor()
            objrealtime.execute("UPDATE sensors SET realtime='" + str(now) + "', realt='" + str(t2) + "', realh='" + str(h2) + "' WHERE sensorid=2")
            DBConn.commit()
            objrealtime.close

        #------Calculate Average-------#
        #-----------Sensor 0-----------#
        now = datetime.now()
        strTime = now.strftime("%-I:%M %p")
        DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword, db=dbName, charset=charSet,cursorclass=cusrorType)
        objSensor = DBConn.cursor()
        objSensor.execute("SELECT * FROM sensors WHERE sensorid = 0")
        rsSensor = objSensor.fetchone()
        sensorname = rsSensor["name"]
        sensorenabled = rsSensor["enabled"]
        enabled0 = rsSensor["enabled"]
        display = rsSensor["display"]
        alert = rsSensor["alert"]
        alertint = rsSensor["alertint"]
        gpiopin = rsSensor["gpiopin"]
        calm = rsSensor["calm"]
        cala = rsSensor["cala"]
        tmin = rsSensor["tmin"]
        tmax = rsSensor["tmax"]
        talertstat = rsSensor["talertstat"]
        talertval = rsSensor["talertval"]
        talerttime = rsSensor["talerttime"]
        talertnext = rsSensor["talertnext"]
        hmin = rsSensor["hmin"]
        hmax = rsSensor["hmax"]
        halertstat = rsSensor["halertstat"]
        halertval = rsSensor["halertval"]
        halerttime = rsSensor["halerttime"]
        halertnext = rsSensor["halertnext"]
        rsSensor.clear
        objSensor.close
        DBConn.close
        if sensorenabled == 1:
            t0 = (t1 + t2) / 2
            h0 = (h1 + h2) / 2
            if tempunits == "F":
                strT0 = str(round(t0 * 1.8 + 32,1)) + "°" + tempunits
                strTMin = str(round(tmin * 1.8 + 32,1))
                strTMax = str(round(tmax * 1.8 + 32,1))
            else:
                strT0 = str(t0) + "°" + tempunits
                strTMin = str(tmin)
                strTMax = str(tmax)
            strH0 = str(round(h0,1)) + "%"
            strHMin = str(round(hmin,1)) + "%"
            strHMax = str(round(hmax,1)) + "%"
            if t0 < tmin or t0 > tmax:
                strTCol = "RED"
                if alert == 1 and now > talertnext:
                    DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                    objalert = DBConn.cursor()
                    objalert.execute("UPDATE sensors SET talertstat = '1', talertval = '" + str(t0) + "', talerttime = '" + str(now) + "', talertnext = '" + str(datetime.now() + timedelta(seconds=alertint)) + "' WHERE sensorid ='0'")
                    DBConn.commit()
                    objalert.close
                    DBConn.close
                    msg = po.msg("Sensor '" + sensorname + "' is in alert! Current temperature of " + strT0 + " is outside the threshold of " + strTMin + " to " + strTMax + ".")
                    msg.set("title", "HumiWatch Alert (Temperature)")
                    po.send(msg)
            else:
                strTCol = "GREEN"
                if alert == 1 and talertstat == 1 and now > (talerttime + timedelta(seconds=300)):
                        DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                        objalert = DBConn.cursor()
                        objalert.execute("UPDATE sensors SET talertstat = '0', talertval = '0', talerttime = '" + str(now) + "', talertnext = '" + str(now) + "' WHERE sensorid ='0'")
                        DBConn.commit()
                        objalert.close
                        DBConn.close
                        msg = po.msg("Sensor '" + sensorname + "' is no longer in alert. Current temperature of " + strT0 + " is inside the threshold of " + strTMin + " to " + strTMax + ".")
                        msg.set("title", "HumiWatch Alert (Temperature)")
                        po.send(msg)
            if h0 < hmin or h0 > hmax:
                strHCol = "RED"
                if alert == 1 and now > halertnext:
                    DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                    objalert = DBConn.cursor()
                    objalert.execute("UPDATE sensors SET halertstat = '1', halertval = '" + str(h1) + "', halerttime = '" + str(now) + "', halertnext = '" + str(datetime.now() + timedelta(seconds=alertint)) + "' WHERE sensorid ='0'")
                    DBConn.commit()
                    objalert.close
                    DBConn.close
                    msg = po.msg("Sensor '" + sensorname + "' is in alert! Current humidity of " + strH0 + " is outside the threshold of " + strHMin + " to " + strHMax + ".")
                    msg.set("title", "HumiWatch Alert (Humidity)")
                    po.send(msg)
            else:
                strHCol = "GREEN"
                if alert == 1 and halertstat == 1 and now > (halerttime + timedelta(seconds=300)):
                        DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
                        objalert = DBConn.cursor()
                        objalert.execute("UPDATE sensors SET halertstat = '0', halertval = '0', halerttime = '" + str(now) + "', halertnext = '" + str(now) + "' WHERE sensorid ='0'")
                        DBConn.commit()
                        objalert.close
                        DBConn.close
                        msg = po.msg("Sensor '" + sensorname + "' is no longer in alert. Current humidity of " + strH0 + " is inside the threshold of " + strHMin + " to " + strHMax + ".")
                        msg.set("title", "HumiWatch Alert (Humidity)")
                        po.send(msg)
            Display_Stats(strTime,sensorname,strT0,strH0,strTCol,strHCol)
            time.sleep(3)
            DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
            objrealtime = DBConn.cursor()
            objrealtime.execute("UPDATE sensors SET realtime='" + str(now) + "', realt='" + str(t0) + "', realh='" + str(h0) + "' WHERE sensorid=0")
            DBConn.commit()
            objrealtime.close

        #-------Insert Readings--------#
        if now > nextupdate:
            DBConn = pymysql.connect(host=dbServerName, user=dbUser, password=dbPassword,db=dbName, charset=charSet,cursorclass=cusrorType)
            objreadings = DBConn.cursor()
            if enabled0 == 1:
                objreadings.execute("INSERT INTO readings (readtime, sensor, temp, hum) VALUES('" + str(now) + "', '0', '" + str(t0) + "', '" + str(h0) + "')")
                DBConn.commit()
            if enabled1 == 1:
                objreadings.execute("INSERT INTO readings (readtime, sensor, temp, hum) VALUES('" + str(now) + "', '1', '" + str(t1) + "', '" + str(h1) + "')")
                DBConn.commit()
            if enabled2 == 1:
                objreadings.execute("INSERT INTO readings (readtime, sensor, temp, hum) VALUES('" + str(now) + "', '2', '" + str(t1) + "', '" + str(h2) + "')")
                DBConn.commit()
            objreadings.close
            DBConn.close
            nextupdate = datetime.now() + timedelta(minutes=5)

except Exception as e:
    print("Exeception occured:{}".format(e))
finally:
    print("\r\nShutting down HumiWatch")
    OLED.Clear_Screen()
    GPIO.cleanup()