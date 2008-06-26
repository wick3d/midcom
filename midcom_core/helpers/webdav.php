<?php
/**
 * @package midcom_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

// We use the PEAR WebDAV server class
require 'HTTP/WebDAV/Server.php';

// The PATH_INFO needs to be provided so that creates will work
$_SERVER['PATH_INFO'] = $_MIDCOM->context->uri;

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
        $this->add_to_log("Path was: {$this->path}");
        flush();
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
        $_MIDCOM->authorization->require_user();
    
        $info = $this->get_path_info();
        
        if (is_null($info['object']))
        {
            $this->add_to_log("404 Not Found");
            throw new midcom_exception_notfound("Not found");
        }

        /*if (substr($_MIDCOM->dispatcher->argv[0], 0, 1) == '.')
        {
            $this->add_to_log("Skipping dotfile, object is {$info['object']->guid}");
            throw new midcom_exception_notfound("No dotfiles please");
        }*/

        $this->get_files_page($info['object'], &$files);
        
        return true;
    }
    
    private function get_files_page($page, &$files)
    {
        // Add the downloadable page itself
        $conf = $this->get_files_stub();
        $conf['props'][] = $this->mkprop('displayname', $page->title);
        $conf['props'][] = $this->mkprop('resourcetype', '');
        $conf['props'][] = $this->mkprop('getcontenttype', 'text/html');
        $conf['props'][] = $this->mkprop('getcontentlength', strlen($page->content));
        $conf['props'][] = $this->mkprop('getlastmodified', strtotime($page->metadata->revised));
        $conf['path'] = "{$_MIDCOM->context->prefix}__content.html";
        $files['files'][] = $conf;
    
        $mc = midgard_page::new_collector('up', $page->id);
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
        $info = $this->get_path_info();
        
        if (is_null($info['object']))
        {
            throw new midcom_exception_notfound("Not found");
        }

        $_MIDCOM->authorization->require_user();

        switch ($info['variant'])
        {
            case 'content_html':
                $options['data'] = $info['object']->content;
                $options['mimetype'] = 'text/html';
                $options['mtime'] = $info['object']->metadata->revised;
                return true;
            default:
                throw new midcom_exception_notfound("Not found");
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
        $info = $this->get_path_info($options['path']);

        if (is_null($info['object']))
        {
            // Creation support
            if (is_null($info['parent']))
            {
                $this->add_to_log("No parent known");
                throw new midcom_exception_notfound("Not found");
            }
            
            $_MIDCOM->authorization->require_do('midgard:create', $info['parent']);
            
            $this->add_to_log("Trying to create {$options['path']}.");
            
            $file_type = pathinfo($options['path'], PATHINFO_EXTENSION);
            switch ($file_type)
            {
                case 'html':
                    $info['object'] = $info['parent'];
                    $info['variant'] = 'content_html';
                    break;
                default:
                    return "415 Unsupported media type";
            }
        }
        
        $_MIDCOM->authorization->require_user();
        
        $_MIDCOM->authorization->require_do('midgard:update', $info['object']);
        
        switch ($info['variant'])
        {
            case 'content_html':
                $info['object']->content = file_get_contents('php://input');
                $info['object']->update();
                return true;
            default:        
                return '405 Method Not Allowed';
        }
    }

    /**
     * MKCOL method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    public function MKCOL($options)
    {
        $info = $this->get_path_info($options['path']);
        
        if (!is_null($info['object']))
        {
            return '405 Method not allowed';
        }
        
        // Creation support
        if (is_null($info['parent']))
        {
            $this->add_to_log("No parent known");
            throw new midcom_exception_notfound("Not found");
        }
        
        $_MIDCOM->authorization->require_do('midgard:create', $info['parent']);
        
        $this->add_to_log("Trying to create {$options['path']}");
        $page = new midgard_page();
        $page->up = $info['parent']->id;
        $page->name = basename($options['path']);
        $page->title = $page->name;
        if (!$page->create())
        {
            return false;
        }
        
        return '201 Created';
    }

    /**
     * LOCK method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function LOCK(&$options) 
    {
        $this->add_to_log("Options: " . serialize($options));
        $info = $this->get_path_info();

        $shared = false;
        if ($options['scope'] == 'shared')
        {
            $shared = true;
        }
        
        if (is_null($info['object']))
        {
            throw new midcom_exception_notfound("Not found");
        }
        
        if (midcom_core_helpers_metadata::is_locked($info['object']))
        {
            $this->add_to_log("Object is locked by another user");
            return "423 Locked";
        }

        midcom_core_helpers_metadata::lock($info['object'], $shared, $options['locktoken']);
        $options['timeout'] = time() + $_MIDCOM->configuration->get('metadata_lock_timeout');
        
        return "200 OK";
    }
    
    /**
     * UNLOCK method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function UNLOCK(&$options) 
    {
        $info = $this->get_path_info();
        
        if (is_null($info['object']))
        {
            throw new midcom_exception_notfound("Not found");
        }
        
        if (midcom_core_helpers_metadata::is_locked($info['object']))
        {
            $this->add_to_log("Object is locked by another user {$info['object']->metadata->locker}");
            return "423 Locked";
        }

        $this->add_to_log("Unlocking");
        midcom_core_helpers_metadata::unlock($info['object']);

        return "200 OK";
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
    function checkAuth($type, $user, $pass)
    {
        if (!$_MIDCOM->authentication->is_user())
        {
            if (!$_MIDCOM->authentication->login($username, $password))
            {
                return false;
            }
        }
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
        $this->add_to_log("checkLock {$path}");
        $info = $this->get_path_info($path);
        if (is_null($info['object']))
        {
            $this->add_to_log("{$path} Not Found");
            return false;
        }
        
        if (!midcom_core_helpers_metadata::is_locked($info['object'], false))
        {
            $this->add_to_log("Not locked, locked = {$info['object']->metadata->locked}, locker = {$info['object']->metadata->locker}");
            return false;
        }

        // Populate lock info from metadata
        $lock = array
        (
            'type' => 'write',
            'scope' => 'shared',
            'depth' => 0,
            'owner' => $info['object']->metadata->locker,
            'created' => strtotime($info['object']->metadata->locked  . ' GMT'),
            'modified' => strtotime($info['object']->metadata->locked . ' GMT'),
            'expires' => strtotime($info['object']->metadata->locked . ' GMT') + $_MIDCOM->configuration->get('metadata_lock_timeout') * 60,
        );
        
        if ($info['object']->metadata->locker)
        {
            $lock['scope'] = 'exclusive';
        }
        
        $lock_token = $info['object']->parameter('midcom_core_helper_metadata', 'lock_token');
        if ($lock_token)
        {
            $lock['token'] = $lock_token;
        }
        
        $this->add_to_log(serialize($lock));
        return $lock;
    }
    
    private function get_path_info($path = null)
    {
        if (is_null($path))
        {
            $local_path = implode('/', $_MIDCOM->dispatcher->argv);
            $this->add_to_log("Get path info \"{$local_path}\" from ARGV");
        }
        else
        {
            $local_path = substr($path, strlen($_MIDCOM->context->prefix));
            $this->add_to_log("Get path info \"{$local_path}\" from OPTIONS");
        }
        $path = $local_path;
        
        static $info = array();
        if (isset($info[$path]))
        {
            return $info[$path];
        }
        
        $current_page = new midgard_page($_MIDCOM->context->page['guid']);
        
        $argv = array();
        $args = explode('/', $path);
        foreach ($args as $arg)
        {
            if (empty($arg))
            {
                continue;
            }
            $argv[] = $arg;
        }
        
        $info[$path] = array
        (
            'object' => null,
            'parent' => null,
            'variant' => 'default',
        );
        
        /*if ($_MIDCOM->context->page['id'] == $_MIDCOM->context->host->root)
        {
            // We're at MidCOM host root page, handle special URL cases
        }*/

        if (count($argv) == 0)
        {
            $info[$path]['object'] = $current_page;
            $info[$path]['variant'] = 'collection';
            return $info[$path];
        }

        if (count($argv) == 1)
        {
            // Only one argument, check it
            if ($argv[0] == '__content.html')
            {
                $info[$path]['object'] = $current_page;
                $info[$path]['variant'] = 'content_html';
                return $info[$path];
            }
            else
            {
                $info[$path]['parent'] = $current_page;
                $qb = midgard_page::new_query_builder();
                $qb->add_constraint('up', '=', $current_page->id);
                $qb->add_constraint('name', '=', $argv[0]);
                $pages = $qb->execute();
                if (count($pages) > 0)
                {
                    $info[$path]['object'] = $pages[0];
                    $info[$path]['variant'] = 'collection';
                    return $info[$path];
                }
            }
        }
        
        return $info[$path];
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