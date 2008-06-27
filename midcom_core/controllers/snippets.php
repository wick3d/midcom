<?php
/**
 * @package midcom_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM Snippet and SnippetDir WebDAV management controller
 *
 * @package midcom_core
 */
class midcom_core_controllers_snippets
{
    public function __construct($instance)
    {
        $this->configuration = $_MIDCOM->configuration;
    }
    
    private function get_snippetdir_children($snippetdir_id, $uri_prefix)
    {
        // Load children for PROPFIND purposes
        $children = array();
        
        // Snippetdirs
        $mc = midgard_snippetdir::new_collector('up', $snippetdir_id);
        $mc->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
        $mc->set_key_property('name');
        $mc->add_value_property('description');
        $mc->execute(); 
        $snippetdirs = $mc->list_keys();
        foreach ($snippetdirs as $name => $array)
        {
            if (empty($name))
            {
                continue;
            }
            $children[] = array
            (
                'uri'      => "{$uri_prefix}{$name}/", // FIXME: dispatcher::generate_url
                'title'    => $name,
                'mimetype' => 'httpd/unix-directory',
                'resource' => 'collection',
            );
        }
        
        // Snippets
        $qb = midgard_snippet::new_query_builder();
        $qb->add_constraint('up', '=', $snippetdir_id);
        $qb->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
        $qb->add_constraint('name', '<>', '');
        $snippets = $qb->execute(); 
        foreach ($snippets as $snippet)
        {
            $children[] = array
            (
                'uri'      => "{$uri_prefix}{$snippet->name}/", // FIXME: dispatcher::generate_url
                'title'    => $snippet->name,
                'mimetype' => 'text/plain',
                'size'     => $snippet->metadata->size,
                'revised'  => $snippet->metadata->revised,
            );
        }
        
        return $children;
    }
    
    public function action_webdav($route_id, &$data, $args)
    {
        if ($route_id == 'snippets_root')
        {
            $data['children'] = $this->get_snippetdir_children(0, "{$_MIDCOM->context->prefix}__snippets/");
        }
        else
        {
            // First we need to load the object
            $object_path = '/' . implode('/', $args['variable_arguments']);
            
            try
            {
                $snippetdir = new midgard_snippetdir();
                $snippetdir->get_by_path($object_path);
            }
            catch (midgard_error_exception $e)
            {
                try
                {
                    // This is possibly snippet instead
                    $snippet = new midgard_snippet();
                    $snippet->get_by_path($object_path);
                }
                catch (midgard_error_exception $e)
                {
                    throw new midcom_exception_notfound("Code Snippet {$object_path} not found");
                }
            }
        
            if ($snippetdir->id)
            {
                $data['children'] = $this->get_snippetdir_children($snippetdir->id, "{$_MIDCOM->context->prefix}__snippets{$object_path}/");
            }
        }
    }
}
?>