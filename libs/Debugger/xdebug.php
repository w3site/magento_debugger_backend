<?php 
/**
 * © Tereta Alexander (www.w3site.org), 2014-2015yy
 * All rights reserved.
 *
 * @author Tereta Alexander (www.w3site.org)
 */
?>
<html>
    <head>
        <title></title>
    </head>
    <body>
        <?php if (isset($_GET['XDEBUG_SESSION_START'])) : ?>
            XDebug session started
        <?php else : ?>
            XDebug session stopped
        <?php endif ?>
        <script>
            window.setTimeout(function(){
                window.close();
            }, 10000);
        </script>
    </body>
</html>