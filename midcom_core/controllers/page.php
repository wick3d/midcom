<?php
/**
 * @package midcom_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Page management controller
 *
 * @package midcom_core
 */
class midcom_core_controllers_page extends midcom_core_controllers_baseclasses_manage
{
    public function __construct($instance)
    {
        $this->configuration = $_MIDCOM->configuration;
    }
    
    public function load_object($args)
    {
        if (!isset($_MIDGARD['page']))
        {
            throw new midcom_exception_notfound("No Midgard page found");
        }
        
        $this->object = new midgard_page();
        $this->object->get_by_id($_MIDGARD['page']);
    }
    
    public function get_object_url()
    {
        return $_MIDGARD['self'];
    }
    
    public function populate_toolbar()
    {
    }
    
}
?>