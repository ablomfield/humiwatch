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
$rssettings = mysqli_query($dbconn, "SELECT adminpass FROM settings") or die("Error in Selecting " . mysqli_error($dbconn));
$rowsettings = mysqli_fetch_assoc($rssettings);
$adminpass = $rowsettings["adminpass"];
$dbconn->close();

if (isset($_POST['redirecting'])) {
        $redirecting = $_POST['redirecting'];
    } elseif (isset($_GET['redirecting'])) {
        $redirecting = $_GET['redirecting'];
    } else {
        $redirecting = "";
}
if (isset($_POST['password'])) {
        $strPassword = $_POST['password'];
    } else {
        $strPassword = "";
}
if (password_verify($strPassword,$adminpass) == True) {  
        $error = "Authenticated!";
        $_SESSION['ISLOGGEDIN'] = 1;
        Header("Location: " . $redirecting);        
    } elseif ($strPassword !== "") {
        $error = "Invalid password!";
    } else {
        $error = "";
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8"/>
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
                    grid-template-columns: 1fr 10px;
                    grid-template-rows: 2fr 1fr 1fr 1fr;
                    gap: 1px 1px;
                    grid-template-areas: "header header" "login-label tbl-spacer" "login-password tbl-spacer";
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
                    grid-template-columns: 1fr 10px;
                    grid-template-rows: 1fr 1fr 1fr 1fr;
                    gap: 1px 1px;
                    grid-template-areas: "header header" "login-label tbl-spacer" "login-password tbl-spacer" "login-submit tbl-spacer";
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

            .login-label {
                grid-area: login-label;
                vertical-align:middle;
                text-align: center;
                margin-left: auto;
                margin-right: auto;
            }

            .login-password {
                grid-area: login-password;
                vertical-align:middle;
                text-align: center;
                margin-left: auto;
                margin-right: auto;
            }

            .login-submit {
                grid-area: login-submit;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .tbl-spacer {
                grid-area: tbl-spacer;
                max-width: 1vw;
                vertical-align:middle;
                margin-left: auto;
                margin-right: auto;
            }
        </style>
        <link rel="icon" type="image/icon" href="../favicon.ico">
    </head>
    <body>
        <form method="post">
        <div class="grid-container">
            <div class="header"><img width="100%" src="../humiwatch.png" alt="HumiWatch"></div>
            <div class="login-label">Enter Password</div>
            <div class="login-password"><input type="password" name="password"><br><?php echo($error); ?></div>
            <div class="login-submit"><input type="submit" value="Login"></div>
            <div class="tbl-spacer">&nbsp;</div>
        </div>
        </form>
    </body>
</html>