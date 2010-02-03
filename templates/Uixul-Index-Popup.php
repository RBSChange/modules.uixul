<?php
if (headers_sent() == false)
{
    header("Content-type: text/html");
}
?>
<?xml version="1.0" encoding="UTF-8"?>
<html style="margin: 0; padding: 0;">
<head>
    <title><?php echo $template['title']; ?></title>
    <?php echo $template['scripts']; ?>
    <?php echo $template['styles']; ?>
    <script type="text/javascript">
	    var changeWindow = null;
	    
	    $(document).ready(function(){
            initOpenChange();
		});
        
        function initOpenChange()
        {	
        	$(".popup-warning").hide();
        	
        	changeWindow = openChangeWindow();
        	
        	if (!changeWindow)
        	{
        		blink(".popup-warning");
        	}
        }
        
        function blink(elt)
        {
        	$(elt).fadeIn("fast").fadeOut("fast").fadeIn("fast").fadeOut("fast").fadeIn("fast");
        }
        
        function forceOpenChangeWindow()
        {
        	if ($("#popup-checkbox").attr("checked") != true)
        	{
        		return true;
        	}
        	
        	changeWindow = openChangeWindow();
        	
        	if (!changeWindow)
        	{
        		return true;
        	}
        	
        	return false;
        }
        
        function openChangeWindow()
        {
        	return window.open(
    	    	"<?php echo $template['url']; ?>",
                "<?php echo $template['title']; ?>",
                "menubar=no,toolbar=no,location=no,personalbar=no,resizable=yes,scrollbars=yes,status=no,fullscreen=yes"
            );
        }
	</script>
</head>
<body style="margin: 0; padding: 0;">
	<div class="form-login">
        <h1><a href="<?php echo $template['url']; ?>" onclick="return forceOpenChangeWindow();" title="RBS CHANGE"><img src="<?php echo $template['logo']; ?>" alt="RBS CHANGE" /></a></h1>
        <h2><?php echo $template['welcome']; ?></h2>        
        <ul class="form-link">
    		<li class="popup-warning">
    			<h3><?php echo $template['warninglabel']; ?></h3>
    			<h4><?php echo $template['warningdesc']; ?></h4>
    			<a class="no-white" href="<?php echo $template['url']; ?>" onclick="return forceOpenChangeWindow();"><?php echo $template['warninglink']; ?></a><br/><br/>
    			<input type="checkbox" id="popup-checkbox" checked="true" style="margin-left: 12px;"/><label for="popup-checkbox" style="margin-left: -10px;"><?php echo $template['warningcheck']; ?></label>
    		</li>
    	</ul>    
    	<p class="copyright">RBS Change&trade; &copy; 2007 Ready Business System</p>
    </div>
</body>
</html>