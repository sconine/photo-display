<?php
// Script that browser that drives the "screens" initially loads
?>
<html>
<head><Title>Photo Display Screen</title>
<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
</head>
<body>
<div class="my_media">Media will go here</div>

<script>

//setTimeout(alert('timeed alert'), 500000);
var htmlString = $("my_media").html();
alert(htmlString);

</script>

</body>
