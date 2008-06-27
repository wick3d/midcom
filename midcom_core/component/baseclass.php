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
        // Load children for PROPFIND purposes
        $children = array();
        $mc = midgard_page::new_collector('up', $_MIDCOM->context->page['id']);
        $mc->set_key_property('name');
        $mc->add_value_property('title');
        $mc->execute(); 
        $pages = $mc->list_keys();
        foreach ($pages as $name => $array)
        {
            if (empty($name))
            {
                continue;
            }
            $children[] = array
            (
                'uri'      => "{$_MIDCOM->context->prefix}{$name}/", // FIXME: dispatcher::generate_url
                'title'    => $mc->get_subkey($name, 'title'),
                'mimetype' => 'httpd/unix-directory',
                'resource' => 'collection',
            );
        }
        
        return $children;
    }
}
?>