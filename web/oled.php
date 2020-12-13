<html>
<head>
<script>
var reimg
window.onload=function () {
    reimg=document.getElementById('oled')
    setInterval(function () {
        reimg.src=reimg.src.replace(/\?.*/,function () {
            return '?'+new Date()
        })
    },5000)
}
</script>
<title>HumiWatch - Admin - OLED</title>
</head>
<body>
<img src="oled.png?" id="oled" width="256">
</body>
</html>
