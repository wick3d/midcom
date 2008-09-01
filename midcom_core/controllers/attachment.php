<?php
/**
 * @package midcom_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Very simple attachment serving by guid.
 *
 * @package midcom_core
 */
class midcom_core_controllers_attachment
{

    public function __construct($instance)
    {
        $this->configuration = $_MIDCOM->configuration;
    }
    
    /**
     * Function serves the attachment by provided guid and exits.
     * @todo: Permission handling
     * @todo: Direct filesystem serving
     * @todo: Configuration options
     */
    public function action_serve($route_id, &$data, $args)
    {
        $att = new midgard_attachment($args['guid']);
        header('Content-type: '.$att->mimetype);
        
        /**
          * If X-Sendfile support is enabled just sending correct headers
          */
        if ($this->configuration->enable_xsendfile)
        {
            $file = '/var/lib/midgard/blobs/midgard/'.$att->location;
            header("X-Sendfile: $file");
        }
        else
        {
            $blob = new midgard_blob($att);
            echo $blob->read_content();
        }
        exit();
    }
}
?>