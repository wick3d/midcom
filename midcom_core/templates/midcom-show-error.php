<?php
/**
 * HTML5 error page for MidCOM
 *
 * @todo convert to XHTML5 as soon as MidCOM 3 javascripts are compatible with it
 * @package midcom_core
 */
?>
<!DOCTYPE html>
<html>
    <head>
        <title tal:content="midcom_core_exceptionhandler/header"></title>
        <span tal:replace="php: MIDCOM.head.print_elements()" />
        <link rel="stylesheet" type="text/css" href="/midcom-static/midcom_core/midgard/screen.css" media="screen,projection,tv" />
        <link rel="stylesheet" type="text/css" href="/midcom-static/midcom_core/midgard/content.css" media="all" />
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
        <link rel="shortcut icon" href="/midcom-static/midcom_core/midgard/midgard.ico" type="image/vnd.microsoft.icon" />
    </head>
    <body>
        <div id="container">
            <header>
                <div class="grouplogo">
                    <a href="/"><img src="/midcom-static/midcom_core/midgard/midgard.gif" alt="Midgard" width="135" height="138" /></a>
                </div>
            </header>
            <section id="content">
                <!-- beginning of content-text -->
                <div id="content-text">
                    <h1 tal:content="midcom_core_exceptionhandler/header">HTTP Error: 404 not found</h1>
                    
                    <aside>
                        <img src="/midcom-static/midcom_core/midgard/error-200.png" />
                    </aside>
                    
                    <p tal:content="midcom_core_exceptionhandler/message">Lorem ipsum</p>
                </div>
            </section>
        </div>
        <footer>
             <a href="http://www.midgard-project.org/" rel="powered">Midgard CMS</a> power since 1999. 
             <a href="http://www.gnu.org/licenses/lgpl.html" rel="license">Free software</a>.
        </footer>
    </body>
</html>