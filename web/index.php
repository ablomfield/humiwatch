<?php
session_start();
// Retrieve Settings
$yamlsettings = yaml_parse_file('/home/pi/humiwatch/settings.yaml');
$dbserver = $yamlsettings['Database']['ServerName'];
$dbuser = $yamlsettings['Database']['Username'];
$dbpass = $yamlsettings['Database']['Password'];
$dbname = $yamlsettings['Database']['DBName'];

// Create connection
$dbconn = new mysqli($dbserver, $dbuser, $dbpass, $dbname);
if ($dbconn->connect_error) {
    die("Connection failed: " . $dbconn->connect_error);
}
$rssettings = mysqli_query($dbconn, "SELECT * FROM settings") or die("Error in Selecting " . mysqli_error($dbconn));
$rowsettings = mysqli_fetch_assoc($rssettings);
$tempunits = $rowsettings["tempunits"];

$rssensors = mysqli_query($dbconn, "SELECT * FROM sensors") or die("Error in Selecting " . mysqli_error($dbconn));
$senarray = array();
foreach ($rssensors as $i => $row) {      
    foreach ($row as $field => $value) {
        $senarray[$i][$field] = $value; 
    }
}

if(isset($_GET["sensor"])) {
        $sensor = $_GET["sensor"];
    } elseif ($senarray['0']['enabled'] == 1) {
        $sensor = "0";
    } elseif ($senarray['1']['enabled'] == 1) {
        $sensor = "1";
    } elseif ($senarray['2']['enabled'] == 1) {
        $sensor = "2";
    } else {
        die("No enabled sensors!");
}

$rsreadings = mysqli_query($dbconn, "SELECT * FROM sensors WHERE sensorid='" . $sensor ."'") or die("Error in Selecting " . mysqli_error($dbconn));
$rowreadings = mysqli_fetch_assoc($rsreadings);
$readtime = $rowreadings["realtime"];
$readts =  new DateTime($readtime);
$temp = $rowreadings["realt"];
$hum = $rowreadings["realh"];
if ($tempunits == "F") {
        $strTemp = number_format(round(($temp * 1.8 + 32),1),1) . "°F";
    } else {
        $strTemp = number_format($temp,1) . "°C";
}
$strHum = number_format(round($hum,1),1) . "%";
$strTimeStamp = $readts->format('H:i:s');
$tmin = $rowreadings["tmin"];
$tmax = $rowreadings["tmax"];
$hmin = $rowreadings["hmin"];
$hmax = $rowreadings["hmax"];
$sensorname = $rowreadings["name"];
if ($temp < $tmin || $temp > $tmax) {
        $strTempCol = "RED";
    } else {
        $strTempCol = "GREEN";
}
if ($hum < $hmin || $hum > $hmax) {
        $strHumCol = "RED";
    } else {
        $strHumCol = "GREEN";
}
$dbconn->close();
#Set Next Sensor
if ($sensor == 2 && $senarray['0']['display'] == 1) {
        $nextsensor = "0";
    } elseif ($sensor == 2 && $senarray['0']['display'] == 0) {
        $nextsensor = "1";
    } elseif ($sensor == 1 && $senarray['2']['display'] == 1) {
        $nextsensor = "2";
    } elseif ($sensor == 1 && $senarray['2']['display'] == 0) {
        $nextsensor = "1";
    } elseif ($sensor == 0 && $senarray['1']['display'] == 1) {
        $nextsensor = "1";
    } elseif ($sensor == 0 && $senarray['2']['display'] == 1) {
        $nextsensor = "2";
    } else {
        $nextsensor = "0";
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8"/>
        <meta http-equiv="refresh" content="5;URL='?sensor=<?php echo($nextsensor); ?>'">
        <link rel="apple-touch-icon" href="/humiwatch/apple-touch-icon.png">
        <title>HumiWatch</title>
        <style>
            @media (max-width: 999px) {
                .html {
                    font-size: 24px
                }

                .grid-container {
                    display: grid;
                    width: 80%;
                    margin: auto;
                    grid-template-columns: 1fr 1fr 10px;
                    grid-template-rows: 2fr 1fr 1fr 1fr 1fr 1fr;
                    gap: 1px 1px;
                    grid-template-areas: "header header header" "temp-icon temp-data tbl-spacer" "temp-icon temp-label tbl-spacer" "hum-icon hum-data tbl-spacer" "hum-icon hum-label tbl-spacer" "footer footer settings";
                }
            }

            @media (min-width: 1000px) {
                .html {
                    font-size: 32px
                }

                .grid-container {
                    display: grid;
                    width: 400px;
                    margin: auto;
                    grid-template-columns: 1fr 1fr 10px;
                    grid-template-rows: 1fr 1fr 1fr 1fr 1fr 1fr;
                    gap: 1px 1px;
                    grid-template-areas: "header header header" "temp-icon temp-data tbl-spacer" "temp-icon temp-label tbl-spacer" "hum-icon hum-data tbl-spacer" "hum-icon hum-label tbl-spacer" "footer footer settings";
                }
            }

            body {
                background-color: silver;
                font-family: Arial;
            }

            .header {
                grid-area: header;
                text-align: center;
                font-size: 3rem;
                vertical-align:middle;
                margin-left: auto;
                margin-right: auto;
            }

            .temp-icon {
                grid-area: temp-icon;
                display: block;
                vertical-align:middle;
                margin-left: auto;
                margin-right: auto;
            }

            .temp-data {
                grid-area: temp-data;
                background: <?php echo($strTempCol); ?>;
                border-radius: 10px;
                text-align: center;
                padding: 15px;                
                font-size: 4rem;
                vertical-align:middle;
            }

            .temp-label {
                grid-area: temp-label;
                text-align: center;
                vertical-align:middle;
                margin-left: auto;
                margin-right: auto;                
            }

            .hum-icon {
                grid-area: hum-icon;
                display: block;
                vertical-align:middle;
                margin-left: auto;
                margin-right: auto;
            }

            .hum-data {
                grid-area: hum-data;
                background: <?php echo($strHumCol); ?>;
                border-radius: 10px;
                padding: 15px;
                text-align: center;
                font-size: 4rem;
                vertical-align:middle;
            }

            .hum-label {
                grid-area: hum-label;
                text-align: center;
                vertical-align:middle;
                margin-left: auto;
                margin-right: auto;
            }

            .tbl-spacer {
                grid-area: tbl-spacer;
                max-width: 1vw;
                vertical-align:middle;
                margin-left: auto;
                margin-right: auto;
            }

            .footer {
                grid-area: footer;
                text-align: center;
                vertical-align:middle;
                margin-left: auto;
                margin-right: auto;
            }

            .settings {
                grid-area: settings;
                display: block;
                vertical-align:middle;
                margin-left: auto;
                margin-right: auto;
            }
        </style>
        <link rel="icon" type="image/icon" href="favicon.ico">
    </head>
    <body>
        <div class="grid-container">
            <div class="header"><img width="100%" src="humiwatch.png" alt="HumiWatch"></div>
            <div class="temp-icon"><img src="icon-temp.png"></div>
            <div class="temp-data"><?php echo($strTemp); ?></div>
            <div class="temp-label"><p>Temperature</p></div>
            <div class="hum-icon"><img src="icon-hum.png"></div>
            <div class="hum-data"><?php echo($strHum); ?></div>
            <div class="hum-label"><p>Humidity</p></div>
            <div class="tbl-spacer">&nbsp;</div>
            <div class="footer"><?php echo($sensorname); ?><br>Updated at <?php echo($strTimeStamp); ?></div>
            <div class="settings"><a href="settings"><img height="25vw" src="settings.png"></a></div>
        </div>
    </body>
</html>
