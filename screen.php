<?php
// Script that browser that drives the "screens" initially loads
?>
<html>
<head><Title>Photo Display Screen</title>
<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
</head>
<body>
 
 <div id="my_media"></div>
 

 
<script>

// Function to change the media
function change_media(cnt, duration) {
 $( "#my_media" ).text( 'Media #' + cnt );
 cnt++;
 duration = duration + 50;
 setTimeout(function(){change_media(cnt, duration);}, duration);
}

change_media(0, 50);

</script>
 
</body>
</html>
