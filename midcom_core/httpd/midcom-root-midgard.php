<?php
/**
 * @package midcom_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 *
 * MidgardRootFile for running MidCOM 3 under Apache
 */
// Load MidCOM 3
// Note: your MidCOM base directory has to be in PHP include_path
require('midcom_core/framework.php');
$_MIDCOM = new midcom_core_midcom('midgard');

// Call the controller if available
$_MIDCOM->process();

$_MIDCOM->templating->template();

// Read contents from the output buffer and pass to MidCOM rendering
$_MIDCOM->templating->display();
unset($_MIDCOM);
?>