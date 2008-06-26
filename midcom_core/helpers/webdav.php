<?php
/**
 * @package midcom_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

require 'HTTP/WebDAV/Server.php';

/**
 * WebDAV server for MidCOM 3
 *
 * @package midcom_core
 */
class midcom_core_helpers_webdav extends HTTP_WebDAV_Server
{
    /**
     * Serve a WebDAV request
     *
     * @access public
     * @param  string  
     */
    public function serve($base = false) 
    {
        // special treatment for litmus compliance test
        // reply on its identifier header
        // not needed for the test itself but eases debugging
        foreach(apache_request_headers() as $key => $value) 
        {
            if (stristr($key, 'litmus'))
            {
                error_log("Litmus test {$value}");
                header("X-Litmus-reply: {$value}");
            }
        }

        $this->add_to_log("\n\n=================================================", false);
        $this->add_to_log("Serving {$_SERVER['REQUEST_METHOD']} request for {$_SERVER['REQUEST_URI']}");
        
        header("X-Dav-Method: {$_SERVER['REQUEST_METHOD']}");
        
        // let the base class do all the work
        parent::ServeRequest();
        die();
    }

    /**
     * PROPFIND method handler
     *
     * @param  array  general parameter passing array
     * @param  array  return array for file properties
     * @return bool   true on success
     */
    function PROPFIND(&$options, &$files) 
    {
        // get topic/document path
        $path = $options['path'];
        
        $resource = 'page';
        
        if ($_MIDCOM->context->page['id'] == $_MIDCOM->context->host->root)
        {
        
            if ($_MIDCOM->dispatcher->argv[0] == '__snippets')
            {
                $resource = 'snippetdir';
                
                if (count($_MIDCOM->dispatcher->argv) > 1)
                {
                }
                else
                {
                    $this->get_files_snippetdir(0, &$files);
                }
            }

            if ($resource == 'page')
            {
                /*
                // Add pseudo-folders to root
                // Snippetdir folder
                $snippets = $this->get_files_stub();
                $snippets['props'][] = $this->mkprop('displayname', 'Code Snippets');
                $snippets['props'][] = $this->mkprop('resourcetype', 'collection');
                $snippets['props'][] = $this->mkprop('getcontenttype', 'httpd/unix-directory');
                $snippets['path'] = "{$_MIDCOM->context->prefix}__snippets/";
                $files['files'][] = $snippets;
                */
            }
        }
        
        if ($resource == 'page')
        {
            $this->get_files_page($_MIDCOM->context->page, &$files);
        }
        
        return true;
    }
    
    private function get_files_page($page, &$files)
    {
        // Add the downloadable page itself
        $conf = $this->get_files_stub();
        $conf['props'][] = $this->mkprop('displayname', $page['title']);
        $conf['props'][] = $this->mkprop('resourcetype', '');
        $conf['props'][] = $this->mkprop('getcontenttype', 'text/html');
        $conf['path'] = "{$_MIDCOM->context->prefix}content.html";
        $files['files'][] = $conf;
    
        $mc = midgard_page::new_collector('up', $page['id']);
        $mc->set_key_property('name');
        $mc->add_value_property('title');
        $mc->execute();
        
        $guids = $mc->list_keys();
        foreach ($guids as $name => $array)
        {
            if (empty($name))
            {
                continue;
            }
            $subpage = $this->get_files_stub();
            $subpage['props'][] = $this->mkprop('displayname', $mc->get_subkey($name, 'title'));
            $subpage['props'][] = $this->mkprop('resourcetype', 'collection');
            $subpage['props'][] = $this->mkprop('getcontenttype', 'httpd/unix-directory');
            $subpage['path'] = "{$_MIDCOM->context->prefix}{$name}/";
            $files['files'][] = $subpage;
        }
        
        /*
        $mc = midgard_parameter::new_collector('parentguid', $page['guid']);
        $mc->add_constraint('domain', '=', $_MIDCOM->context->component);
        $mc->add_constraint('name', '=', 'configuration');
        $mc->add_constraint('value', '<>', '');
        $mc->set_key_property('guid');
        $mc->execute();
        $guids = $mc->list_keys();
        foreach ($guids as $guid => $array)
        {
            $conf = $this->get_files_stub();
            $conf['props'][] = $this->mkprop('displayname', "{$_MIDCOM->context->component} configuration");
            $conf['props'][] = $this->mkprop('resourcetype', '');
            $conf['props'][] = $this->mkprop('getcontenttype', 'text/yaml');
            $conf['path'] = "{$_MIDCOM->context->prefix}configuration.yml";
            $files['files'][] = $conf;
        }
        */
    }
    
    /*
    private function get_files_snippetdir($snippetdir_id, &$files)
    {
        $this->add_to_log("Snippetdir {$snippetdir_id}");
        $mc = midgard_snippetdir::new_collector('up', $snippetdir_id);
        $mc->set_key_property('name');
        $mc->execute();
        
        $guids = $mc->list_keys();
        foreach ($guids as $name => $array)
        {
            if (empty($name))
            {
                continue;
            }
            $page = $this->get_files_stub();
            $page['props'][] = $this->mkprop('displayname', $name);
            $page['props'][] = $this->mkprop('resourcetype', 'collection');
            $page['props'][] = $this->mkprop('getcontenttype', 'httpd/unix-directory');
            $page['path'] = "{$_MIDCOM->context->prefix}__snippets/{$name}/";
            $files['files'][] = $page;
        }
    }
    */
    private function get_files_stub()
    {
        return array
        (
            'props' => array(),
            'path'  => array(),
        );
    }
    
    /**
     * GET method handler
     * 
     * @param  array  parameter passing array
     * @return bool   true on success
     */
    function GET(&$options) 
    {
        if ($_MIDCOM->dispatcher->argv[0] == 'content.html')
        {
            $page = new midgard_page($_MIDCOM->context->page['guid']);
            $options['data'] = $page->content;
            return true;
        }
        
        throw new midcom_exception_notfound('No route matches current URL');
    }

    /**
     * PUT method handler
     * 
     * @param  array  parameter passing array
     * @return bool   true on success
     */
    function PUT(&$options) 
    {
        if ($_MIDCOM->dispatcher->argv[0] == 'content.html')
        {
            $this->add_to_log("PUT content.html");
            $page = new midgard_page($_MIDCOM->context->page['guid']);
            $page->content = file_get_contents('php://input');
            $this->add_to_log($page->content);
            $page->update();
            
            return true;
        }
    }

    /**
     * No authentication is needed here
     *
     * @access private
     * @param  string  HTTP Authentication type (Basic, Digest, ...)
     * @param  string  Username
     * @param  string  Password
     * @return bool    true on successful authentication
     */
    function check_auth($type, $user, $pass)
    {
        return true;
    }

    /**
     * checkLock() helper
     *
     * @param  string resource path to check for locks
     * @return bool   true on success
     */
    function checkLock($path) 
    {
        return true;
    }

    /**
     * Logging method for WebDAV debugging
     */
    private function add_to_log($string, $addtime = true) 
    {
        $log_file = fopen('/tmp/midcom-webdav.log', 'a');
        if (!$log_file) 
        {
            return;
        }
        
        if ($addtime)
        {
            $string = date('r') . ": {$string}";
        }
        
        fwrite($log_file, "{$string}\n");
        fclose($log_file);
    }
}
?>