<?php
/**
 * @package midcom_core
 *
 */
?>
<h1>About Midgard CMS</h1>

<p>
    Midgard is a Free Software framework for interactive web application development. It has
    been developed and maintained by an international community since 1999. 
    <a href="http://www.midgard-project.org/">Read more</a>.
</p>

<table>
    <tbody>
        <tr>
            <th>MidCOM</th>
            <td tal:content="midcom_core/versions/midcom">3</td>
        </tr>
        <tr>
            <th>Midgard</th>
            <td tal:content="midcom_core/versions/midgard">2</td>
        </tr>
        <tr>
            <th>PHP</th>
            <td tal:content="midcom_core/versions/php">5</td>
        </tr>
    </tbody>
</table>

<h2>Credits</h2>

<ul class="developers" tal:repeat="author midcom_core/authors">
    <li class="vcard">
        <h3 class="fn" tal:content="author/name">Alice</h3>
        <span class="email" tal:content="author/email">alice@example.net</span>
    </li>
</ul>

<div class="logos">
    <a href="http://www.gnu.org/licenses/lgpl.html"><img src="/midcom-static/midcom_core/midgard/lgplv3.png" alt="LGPLv3" /></a>
</div>