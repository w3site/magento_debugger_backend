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
        <link rel="stylesheet" href="/?magento_debug=file&magento_debug_file=css/style.css" />
    </head>
    <body>
        <header>
            <img src="/?magento_debug=file&magento_debug_file=images/icon.png" class="logo" />
            <h1 id="heading">Magento Debugger</h1>
            <a class="heading-link" href="http://w3site.org">W3Site.org</a>
        </header>
        <?php if (isset($_GET['XDEBUG_SESSION_START'])) : ?>
            XDebug session has been started.
        <?php else : ?>
            XDebug session has been stopped.
        <?php endif ?>
        This page will be closed in <strong id="seconds">10</strong> sec.
        <script>
            var seconds = 10;
            window.setInterval(function(){
                if (seconds <= 0){
                    window.close();
                }
                seconds = seconds - 1;

                document.getElementById('seconds').innerHTML = seconds;
            }, 1000);
        </script>
    </body>
</html>