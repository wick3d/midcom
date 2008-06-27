<?php
/**
 * @package midcom_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Component interface definition for MidCOM 3
 *
 * The defines the structure of component instance interface class
 *
 * @package midcom_core
 */
abstract class midcom_core_component_baseclass implements midcom_core_component_interface
{
    public $configuration = false;
    
    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    public function initialize()
    {
        $this->on_initialize();
    }

    public function on_initialize()
    {
    }
    
    public function get_node_children(midgard_page $node)
    {
        return array();
    }
}
?>