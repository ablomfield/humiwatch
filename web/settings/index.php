<?php
session_start();
// Check Authenticated
$securitysec="ISLOGGEDIN";
include '../login/checkaccess.php';

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

if (isset($_POST['action'])) {
        $action = $_POST['action'];
    } else {
        $action = "";
}

if ($action == "updatesettings") {
    $tempunits = $_POST["tempunits"];
    if(empty($_POST["pushenable"])) { 
        $pushenable = 0; 
    } else { 
        $pushenable = 1;
    }
    $pushkey = $_POST["pushkey"];
    $pushuser = $_POST["pushuser"];
    $loginterval = $_POST["loginterval"];
    $name0 = $_POST["name0"];
    $name1 = $_POST["name1"];
    $name2 = $_POST["name2"];
	if(empty($_POST["enabled0"])) { 
        $enabled0 = 0; 
    } else { 
        $enabled0 = 1;
    }
    if(empty($_POST["enabled1"])) { 
            $enabled1 = 0; 
        } else { 
            $enabled1 = 1;
        }
    if(empty($_POST["enabled2"])) { 
            $enabled2 = 0; 
        } else { 
            $enabled2 = 1;
        }
    if(empty($_POST["display0"])) { 
            $display0 = 0; 
        } else { 
            $display0 = 1;
        }
    if(empty($_POST["display1"])) { 
            $display1 = 0; 
        } else { 
            $display1 = 1;
        }
    if(empty($_POST["display2"])) { 
            $display2 = 0; 
        } else { 
            $display2 = 1;
        }
    if(empty($_POST["alert0"])) { 
            $alert0 = 0; 
        } else { 
            $alert0 = 1;
        }
    if(empty($_POST["alert1"])) { 
            $alert1 = 0; 
        } else { 
            $alert1 = 1;
        }
    if(empty($_POST["alert2"])) { 
            $alert2 = 0; 
        } else { 
            $alert2 = 1;
        }
    $alertint0 = $_POST["alertint0"];
    $alertint1 = $_POST["alertint1"];
    $alertint2 = $_POST["alertint2"];
    $tmin0 = $_POST["tmin0"];
    $tmax0 = $_POST["tmax0"];
    $hmin0 = $_POST["hmin0"];
    $hmax0 = $_POST["hmax0"];
    $tmin1 = $_POST["tmin1"];
    $tmax1 = $_POST["tmax1"];
    $hmin1 = $_POST["hmin1"];
    $hmax1 = $_POST["hmax1"];
    $tmin2 = $_POST["tmin2"];
    $tmax2 = $_POST["tmax2"];
    $hmin2 = $_POST["hmin2"];
    $hmax2 = $_POST["hmax2"];
    if ($tempunits == "F") {
        $tmin0 = ($tmin0 - 32) / 1.8;
        $tmax0 = ($tmax0 - 32) / 1.8;
        $tmin1 = ($tmin1 - 32) / 1.8;
        $tmax1 = ($tmax1 - 32) / 1.8;
        $tmin2 = ($tmin2 - 32) / 1.8;
        $tmax2 = ($tmax2 - 32) / 1.8;
    }
    $updatesql = "UPDATE sensors SET name = '" . $name0 . "', enabled = '" . $enabled0 . "', display = '" . $display0 . "', alert = '" . $alert0 . "', alertint = '" . $alertint0 . "', tmin = '" . $tmin0 . "', tmax = '" . $tmax0 . "', hmin = '" . $hmin0 . "', hmax = '" . $hmax0 . "' WHERE sensorid = '0'";
    $updaters = mysqli_query($dbconn,$updatesql);
    $updatesql = "UPDATE sensors SET name = '" . $name1 . "', enabled = '" . $enabled1 . "', display = '" . $display1 . "', alert = '" . $alert1 . "', alertint = '" . $alertint1 . "', tmin = '" . $tmin1 . "', tmax = '" . $tmax1 . "', hmin = '" . $hmin1 . "', hmax = '" . $hmax1 . "' WHERE sensorid = '1'";
    $updaters = mysqli_query($dbconn,$updatesql);
    $updatesql = "UPDATE sensors SET name = '" . $name2 . "', enabled = '" . $enabled2 . "', display = '" . $display2 . "', alert = '" . $alert2 . "', alertint = '" . $alertint2 . "', tmin = '" . $tmin2 . "', tmax = '" . $tmax2 . "', hmin = '" . $hmin2 . "', hmax = '" . $hmax2 . "' WHERE sensorid = '2'";
    $updaters = mysqli_query($dbconn,$updatesql);
    $updatesql = "UPDATE settings SET tempunits = '" . $tempunits . "', pushenable = '" . $pushenable . "', pushkey = '" . $pushkey . "', pushuser = '" . $pushuser . "', loginterval = '" . $loginterval . "'";
    $updaters = mysqli_query($dbconn,$updatesql);
    header("Location: /humiwatch");
}

$rssensors = mysqli_query($dbconn, "SELECT * FROM sensors") or die("Error in Selecting " . mysqli_error($dbconn));
$senarray = array();
foreach ($rssensors as $i => $row) {      
    foreach ($row as $field => $value) {
        $senarray[$i][$field] = $value; 
    }
}

$rssettings = mysqli_query($dbconn, "SELECT * FROM settings") or die("Error in Selecting " . mysqli_error($dbconn));
$rowsettings = mysqli_fetch_assoc($rssettings);
$tempunits = $rowsettings["tempunits"];
$loginterval = $rowsettings["loginterval"];
$pushenable = $rowsettings["pushenable"];
$pushkey = $rowsettings["pushkey"];
$pushuser = $rowsettings["pushuser"];
$name0 = $senarray['0']['name'];
$name1 = $senarray['1']['name'];
$name2 = $senarray['2']['name'];
$enabled0 = $senarray['0']['enabled'];
$enabled1 = $senarray['1']['enabled'];
$enabled2 = $senarray['2']['enabled'];
$display0 = $senarray['0']['display'];
$display1 = $senarray['1']['display'];
$display2 = $senarray['2']['display'];
$realt0 = $senarray['0']['realt'];
$realt1 = $senarray['1']['realt'];
$realt2 = $senarray['2']['realt'];
$realh0 = $senarray['0']['realh'];
$realh1 = $senarray['1']['realh'];
$realh2 = $senarray['2']['realh'];
$alert0 = $senarray['0']['alert'];
$alert1 = $senarray['1']['alert'];
$alert2 = $senarray['2']['alert'];
$alertint0 = $senarray['0']['alertint'];
$alertint1 = $senarray['1']['alertint'];
$alertint2 = $senarray['2']['alertint'];
$talertstat0 = $senarray['0']['talertstat'];
$talertstat1 = $senarray['1']['talertstat'];
$talertstat2 = $senarray['2']['talertstat'];
$halertstat0 = $senarray['0']['halertstat'];
$halertstat1 = $senarray['1']['halertstat'];
$halertstat2 = $senarray['2']['halertstat'];
$talerttime0 = $senarray['0']['talerttime'];
$talerttime1 = $senarray['1']['talerttime'];
$talerttime2 = $senarray['2']['talerttime'];
$halerttime0 = $senarray['0']['halerttime'];
$halerttime1 = $senarray['1']['halerttime'];
$halerttime2 = $senarray['2']['halerttime'];
$talertnext0 = $senarray['0']['talertnext'];
$talertnext1 = $senarray['1']['talertnext'];
$talertnext2 = $senarray['2']['talertnext'];
$halertnext0 = $senarray['0']['halertnext'];
$halertnext1 = $senarray['1']['halertnext'];
$halertnext2 = $senarray['2']['halertnext'];
$tmin0 = $senarray['0']['tmin'];
$tmax0 = $senarray['0']['tmax'];
$hmin0 = $senarray['0']['hmin'];
$hmax0 = $senarray['0']['hmax'];
$tmin1 = $senarray['1']['tmin'];
$tmax1 = $senarray['1']['tmax'];
$hmin1 = $senarray['1']['hmin'];
$hmax1 = $senarray['1']['hmax'];
$tmin2 = $senarray['2']['tmin'];
$tmax2 = $senarray['2']['tmax'];
$hmin2 = $senarray['2']['hmin'];
$hmax2 = $senarray['2']['hmax'];
$dbconn->close();
if ($tempunits == "F") {
    $tmin0 = round($tmin0 * 1.8 + 32,1);
    $tmax0 = round($tmax0 * 1.8 + 32,1);
    $tmin1 = round($tmin1 * 1.8 + 32,1);
    $tmax1 = round($tmax1 * 1.8 + 32,1);
    $tmin2 = round($tmin2 * 1.8 + 32,1);
    $tmax2 = round($tmax2 * 1.8 + 32,1);
    $realt0 = round($realt0 * 1.8 + 32,1);
    $realt1 = round($realt1 * 1.8 + 32,1);
    $realt2 = round($realt2 * 1.8 + 32,1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>HumiWatch</title>
<style>
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

.footer {
    grid-area: footer;
    text-align: center;
    vertical-align:middle;
    margin-left: auto;
    margin-right: auto;
}

table.hwtable th {
    background-color: #000000;
    text-align: center;
    color: white;
    padding: 5px;
}
table.hwtable th {
    background-color: #000000;
    text-align: center;
    color: white;
    padding: 5px;
}

table.hwtable tr:nth-child(even) td {
    background-color: silver;
    text-align: left;
    color: black;
    padding: 5px;
}

table.hwtable tr:nth-child(odd) td { 
    background-color: whitesmoke;
    text-align: left;
    color: black;
    padding: 5px;
}

table.hwtable tr td.graycell {
    background-color: DimGray;
    color: white;
    text-align: left;
}

table.hwtable tr td.whitecell {
    background-color: white;
}

table.hwtable tr td.edit {
    background-color: white;
    text-align: right;
}

table.hwtable tr td.delete {
    background-color: white;
    text-align: left;
}
</style>
<link rel="icon" type="image/icon" href="favicon.ico">
</head>
<body>
<form method="post">
<input type="hidden" name="action" value="updatesettings">
<table class="hwtable" width="1000">
    <tr>
        <td colspan="2" class="graycell">General Settings</td>
    </tr>
    <tr>
        <td class="whitecell">Temperature Units</td>
        <td class="whitecell">
            <select name="tempunits">
                <option value="C"<?php if ($tempunits == "C") {echo(" selected");}?>>Celsius</option>
                <option value="F"<?php if ($tempunits == "F") {echo(" selected");}?>>Fahrenheit</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="whitecell">Log Inteval (s)</td>
        <td class="whitecell"><input type="text" name="loginterval" value="<?php echo $loginterval; ?>"></td>
    </tr>    
    <tr>
        <td class="whitecell">Settings Password</td>
        <td class="whitecell"><input type="password" name="adminpass" value=""></td>
    </tr>
    <tr>
        <td colspan="2" class="graycell">Pushover Settings</td>
    </tr>
    <tr>
        <td class="whitecell">Pushover Enabled</td>
        <td class="whitecell"><input type="checkbox" name="pushenable" value="1"<?php if ($pushenable == 1) {echo(" checked");}?>></td>
    </tr>
    <tr>
        <td class="whitecell">Pushover Key</td>
        <td class="whitecell"><input type="text" name="pushkey" value="<?php echo $pushkey; ?>" size="64"></td>
    </tr>
    <tr>
        <td class="whitecell">Pushover User</td>
        <td class="whitecell"><input type="text" name="pushuser" value="<?php echo $pushuser; ?>" size="64"></td>
    </tr>
    <tr>
        <td colspan="2" class="graycell">Sensor 1</td>
    </tr>
    <tr>
        <td class="whitecell">Name</td>
        <td class="whitecell"><input type="text" name="name1" value="<?php echo $name1; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Enabled</td>
        <td class="whitecell"><input type="checkbox" name="enabled1" value="1"<?php if ($enabled1 == 1) {echo(" checked");}?>></td>
    </tr>
    <tr>
        <td class="whitecell">Display</td>
        <td class="whitecell"><input type="checkbox" name="display1" value="1"<?php if ($display1 == 1) {echo(" checked");}?>></td>
    </tr>
    <tr>
        <td class="whitecell">Alert</td>
        <td class="whitecell"><input type="checkbox" name="alert1" value="1"<?php if ($alert1 == 1) {echo(" checked");}?>></td>
    </tr>    
    <tr>
        <td class="whitecell">Status</td>
        <td class="whitecell">
            <table width="100%">
                <tr>
                    <td width="40px" class="whitecell"><img src="../icon-temp.png" width="32px"></td>
                    <td class="whitecell"><?php
                        echo round($realt1,1) . "°" . $tempunits . "<br>";
                        if ($talertstat1 == 1) {
                            echo "<small><font color='red'>In alert since " . $talerttime1 . ".<br>";
                            $interval = $talertnext1->diff($now());
                            echo "Next alert at " . $talertnext1 . " (" . $interval->h . ":" . $interval->i . ").</small>";
                        } else {
                            echo "<small>Not in alert since " . $talerttime1 . ".</small>";
                        }
                    ?>
                    </td>
                </tr>
                <tr>
                    <td width="40px" class="whitecell"><img src="../icon-hum.png" width="32px"></td>
                    <td class="whitecell"><?php
                        echo round($realh1,1) . "%<br>";
                        if ($halertstat1 == 1) {
                            echo "<small><font color='red'>In alert since " . $halerttime1 . ".<br>";
                            $interval = $halertnext1->diff($now());
                            echo "Next alert at " . $halertnext1 . " (" . $interval->h . ":" . $interval->i . ").</small>";
                        } else {
                            echo "<small>Not in alert since " . $halerttime1 . ".</small>";
                        }
                    ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="whitecell">Alert Interval (s)</td>
        <td class="whitecell"><input type="text" name="alertint1" value="<?php echo $alertint1; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Min Tempature</td>
        <td class="whitecell"><input type="text" name="tmin1" value="<?php echo $tmin1; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Max Temperature</td>
        <td class="whitecell"><input type="text" name="tmax1" value="<?php echo $tmax1; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Min Humidity</td>
        <td class="whitecell"><input type="text" name="hmin1" value="<?php echo $hmin1; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Max Humidity</td>
        <td class="whitecell"><input type="text" name="hmax1" value="<?php echo $hmax1; ?>"></td>
    </tr>
    <tr>
        <td colspan="2" class="graycell">Sensor 2</td>
    </tr>
    <tr>
        <td class="whitecell">Name</td>
        <td class="whitecell"><input type="text" name="name2" value="<?php echo $name2; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Enabled</td>
        <td class="whitecell"><input type="checkbox" name="enabled2" value="1"<?php if ($enabled2 == 1) {echo(" checked");}?>></td>
    </tr>
    <tr>
        <td class="whitecell">Display</td>
        <td class="whitecell"><input type="checkbox" name="display2" value="1"<?php if ($display2 == 1) {echo(" checked");}?>></td>
    </tr>
    <tr>
        <td class="whitecell">Alert</td>
        <td class="whitecell"><input type="checkbox" name="alert2" value="1"<?php if ($alert2 == 1) {echo(" checked");}?>></td>
    </tr>    
    <tr>
        <td class="whitecell">Alert Interval (s)</td>
        <td class="whitecell"><input type="text" name="alertint2" value="<?php echo $alertint2; ?>"></td>
    </tr>    
    <tr>
        <td class="whitecell">Min Tempature</td>
        <td class="whitecell"><input type="text" name="tmin2" value="<?php echo $tmin2; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Max Temperature</td>
        <td class="whitecell"><input type="text" name="tmax2" value="<?php echo $tmax2; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Min Humidity</td>
        <td class="whitecell"><input type="text" name="hmin2" value="<?php echo $hmin2; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Max Humidity</td>
        <td class="whitecell"><input type="text" name="hmax2" value="<?php echo $hmax2; ?>"></td>
    </tr>
    <tr>
        <td colspan="2" class="graycell">Average Sensor - Only active when sensor mode is combined and both sensors are enabled</td>
    </tr>
    <tr>
        <td class="whitecell">Name</td>
        <td class="whitecell"><input type="text" name="name0" value="<?php echo $name0; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Enabled</td>
        <td class="whitecell"><input type="checkbox" name="enabled0" value="1"<?php if ($enabled0 == 1) {echo(" checked");}?>></td>
    </tr>
    <tr>
        <td class="whitecell">Display</td>
        <td class="whitecell"><input type="checkbox" name="display0" value="1"<?php if ($display0 == 1) {echo(" checked");}?>></td>
    </tr>
    <tr>
        <td class="whitecell">Alert</td>
        <td class="whitecell"><input type="checkbox" name="alert0" value="1"<?php if ($alert0 == 1) {echo(" checked");}?>></td>
    </tr>    
    <tr>
        <td class="whitecell">Alert Interval (s)</td>
        <td class="whitecell"><input type="text" name="alertint0" value="<?php echo $alertint0; ?>"></td>
    </tr>    
    <tr>
        <td class="whitecell">Min Tempature</td>
        <td class="whitecell"><input type="text" name="tmin0" value="<?php echo $tmin0; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Max Temperature</td>
        <td class="whitecell"><input type="text" name="tmax0" value="<?php echo $tmax0; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Min Humidity</td>
        <td class="whitecell"><input type="text" name="hmin0" value="<?php echo $hmin0; ?>"></td>
    </tr>
    <tr>
        <td class="whitecell">Max Humidity</td>
        <td class="whitecell"><input type="text" name="hmax0" value="<?php echo $hmax0; ?>"></td>
    </tr>        
    <tr>
        <td class="whitecell">&nbsp;</td>
    <td class="whitecell"><input type="submit" value="Update Settings"></td>
    </tr>
</table>
</form>
</body>
</html>