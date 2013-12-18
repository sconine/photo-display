<?php
// Script that browser that drives the "screens" initially loads
?>
<html>
<head><Title>Photo Display Screen</title>
<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
</head>
<body>
 
<p>
  <b>Click</b> to change the <span id="tag">html</span>
</p>
<p>
  to a <span id="text">text</span> node.
</p>
<p>
  This <button name="nada">button</button> does nothing.
</p>
 
<script>
$( "p" ).click(function() {
  var htmlString = $( this ).html();
  $( this ).text( htmlString );
});
</script>
 
</body>
</html>
