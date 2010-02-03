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
    <script type="text/javascript">
	    var preventCloseGlobal = true;

        function preventCloseGlobalWarning(e)
        {
            if (preventCloseGlobal)
            {
        	   var warningStr = "<?php echo $template['msg']; ?>";
               e.returnValue = warningStr;
            }
        }

        function acceptCloseGlobal()
        {
            preventCloseGlobal = false;
        }
	</script>
</head>
<body
    id="MainBackOffice"
    ondragover="event.preventDefault();"
    onbeforeunload="preventCloseGlobalWarning(event)"
    style="margin: 0; padding: 0;">
<iframe
    src="jar:<?php echo LinkHelper::getUIActionLink('uixul', 'Admin')->setQueryParametre('signedView', 1)->getUrl(); ?>!/index.xul"
    style="border: 0; margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden;" />
</body>
</html>