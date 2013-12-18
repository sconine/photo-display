<?php
// Script that browser that drives the "screens" initially loads
?>
<html>
<head><Title>Photo Display Screen</title>
<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
</head>
<body>
<div id="my_media"></div>
<div id="log"></div>


<script>

// Function to change the media
function change_media(cnt, duration) {

 // Make a call to the server to get what we should show
 var request = $.ajax({
  url: "drive_screen.php",
  type: "GET",
  datatype: 'json',
  data: { cnt: cnt, duration: duration },
 });

 request.done(function( data ) {
   $( "#my_media" ).html( data );
   var obj = jQuery.parseJSON( data );
   
   $.each( obj, function( key, value ) {
     alert( key + ": " + value );
    });
   
   
//   $.each(data, function(){
//      $.each(this, function(){
//        console.log(this.address);
//      });
   
 });
 
 request.fail(function( jqXHR, textStatus ) {
   alert( "Request failed: " + textStatus );
 });
 
 
 

 // last thing it does is set itself up to be called again
 cnt++;
 duration = duration + 50;
  setTimeout(function(){change_media(cnt, duration);}, duration);
 return true;
}


// Start the whole thing running
change_media(0, 50);

</script>
</body>
</html>
