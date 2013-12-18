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

function change_media(cnt) {
 $( "#my_media" ).text( 'Media #' + cnt );
 //setTimeout(change_media(cnt++), 2000);
}

change_media(0);

</script>
 
</body>
</html>
