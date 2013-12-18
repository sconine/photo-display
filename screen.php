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
$( "#my_media" ).text( 'this is a test' );
$( "media" ).delay( 50000 );
$( "#my_media" ).text( 'this is a test2' ).delay( 50000 ).text( 'this is a test3' ).delay( 50000 ).text( 'this is a test4' );
setTimeout(alert('timeed alert'), 500000)


</script>
 
</body>
</html>
