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
$_SERVER['SCRIPT_NAME'] = $_MIDCOM->context->prefix;

/**
 * WebDAV server for MidCOM 3
 *
 * @package midcom_core
 */
class midcom_core_helpers_webdav extends HTTP_WebDAV_Server
{
    private $logger = null;
    private $controller = null;
    private $route_id = '';
    private $action_method = '';
    private $action_arguments = array();
    
    public function __construct($controller)
    {
        $this->logger = new midcom_core_helpers_log('webdav');
        $this->controller = $controller;
        parent::HTTP_WebDAV_Server();
    }

    /**
     * Serve a WebDAV request
     *
     * @access public
     */
    public function serve($route_id, $action_method, $action_arguments) 
    {
        $this->route_id = $route_id;
        $this->action_method = $action_method;
        $this->action_arguments = $action_arguments;
    
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

        $this->logger->log("\n\n=================================================", false);
        $this->logger->log("Serving {$_SERVER['REQUEST_METHOD']} request for {$_SERVER['REQUEST_URI']}");
        $this->logger->log("Controller: " . get_class($this->controller) . ", action: {$this->action_method}");
        
        header("X-Dav-Method: {$_SERVER['REQUEST_METHOD']}");
        
        // let the base class do all the work
        parent::ServeRequest();
        $this->logger->log("Path was: {$this->path}");
        die();
    }

    /**
     * OPTIONS method handler
     *
     * The OPTIONS method handler creates a valid OPTIONS reply
     * including Dav: and Allowed: heaers
     * based on the route configuration
     *
     * @param  void
     * @return void
     */
    function http_OPTIONS() 
    {
        // Microsoft clients default to the Frontpage protocol 
        // unless we tell them to use WebDAV
        header("MS-Author-Via: DAV");

        // tell clients what we found
        $this->http_status("200 OK");
        
        // We support DAV levels 1 & 2
        // header("DAV: 1, 2"); TODO: Re-enable when we support locks
        header("DAV: 1");
        
        header("Content-length: 0");
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

        // Run the controller
        $controller = $this->controller;
        $action_method = $this->action_method;
        $data = array();
        $controller->$action_method($this->route_id, $data, $this->action_arguments);
        
        if (!isset($data['children']))
        {
            // Controller did not return children
            $page = new midgard_page($_MIDCOM->context->page['guid']);
            $data['children'] = $this->get_node_children($page);
        }
        
        // Convert children to PROPFIND elements
        $this->children_to_files($data['children'], &$files);
        
        return true;
    }

    private function children_to_files($children, &$files)
    {
        $files['files'] = array();

        foreach ($children as $child)
        {
            $child_props = array
            (
                'props' => array(),
                'path'  => $child['uri'],
            );
            $child_props['props'][] = $this->mkprop('displayname', $child['title']);
            $child_props['props'][] = $this->mkprop('getcontenttype', $child['mimetype']);
            
            if (isset($child['resource']))
            {
                $child_props['props'][] = $this->mkprop('resourcetype', $child['resource']);
            }

            if (isset($child['size']))
            {
                $child_props['props'][] = $this->mkprop('getcontentlength', $child['size']);
            }

            if (isset($child['revised']))
            {
                $child_props['props'][] = $this->mkprop('getlastmodified', strtotime($child['revised']));
            }

            $files['files'][] = $child_props;
        }
    }
    
    
    private function get_node_children(midgard_page $node)
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
        
        // Additional "special" URLs
        $children[] = array
        (
            'uri'      => "{$_MIDCOM->context->prefix}__snippets/", // FIXME: dispatcher::generate_url
            'title'    => 'Code Snippets',
            'mimetype' => 'httpd/unix-directory',
            'resource' => 'collection',
        );
        
        return $children;
    }

    /**
     * GET method handler
     * 
     * @param  array  parameter passing array
     * @return bool   true on success
     */
    function GET(&$options) 
    {
        $_MIDCOM->authorization->require_user();

        // Run the controller
        $controller = $this->controller;
        $action_method = $this->action_method;
        $data =& $options;
        $controller->$action_method($this->route_id, $data, $this->action_arguments);
        
        return true;
    }

    /**
     * PUT method handler
     * 
     * @param  array  parameter passing array
     * @return bool   true on success
     */
    function PUT(&$options) 
    {
        $_MIDCOM->authorization->require_user();

        // Run the controller
        $controller = $this->controller;
        $action_method = $this->action_method;
        $data =& $options;
        $controller->$action_method($this->route_id, $data, $this->action_arguments);
        
        return true;
    }

    /**
     * MKCOL method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    public function MKCOL($options)
    {
        // Run the controller
        $controller = $this->controller;
        $action_method = $this->action_method;
        $data = array();
        $controller->$action_method($this->route_id, $data, $this->action_arguments);
        
        return '201 Created';
    }

    /**
     * MOVE method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
     */
    function MOVE($options) 
    {
        // Run the controller
        $controller = $this->controller;
        $action_method = $this->action_method;
        $data =& $options;
        $controller->$action_method($this->route_id, $data, $this->action_arguments);
        
        return true;
    }

    /**
     * LOCK method handler
     *
     * @param  array  general parameter passing array
     * @return bool   true on success
    function LOCK(&$options) 
    {
        $this->logger->log("Options: " . serialize($options));
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
            $this->logger->log("Object is locked by another user");
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
    function UNLOCK(&$options) 
    {
        $info = $this->get_path_info();
        
        if (is_null($info['object']))
        {
            throw new midcom_exception_notfound("Not found");
        }
        
        if (midcom_core_helpers_metadata::is_locked($info['object']))
        {
            $this->logger->log("Object is locked by another user {$info['object']->metadata->locker}");
            return "423 Locked";
        }

        $this->logger->log("Unlocking");
        midcom_core_helpers_metadata::unlock($info['object']);

        return "200 OK";
    }

    /**
     * checkLock() helper
     *
     * @param  string resource path to check for locks
     * @return bool   true on success
    function checkLock($path) 
    {
        $this->logger->log("checkLock {$path}");
        $info = $this->get_path_info($path);
        if (is_null($info['object']))
        {
            $this->logger->log("{$path} Not Found");
            return false;
        }
        
        if (!midcom_core_helpers_metadata::is_locked($info['object'], false))
        {
            $this->logger->log("Not locked, locked = {$info['object']->metadata->locked}, locker = {$info['object']->metadata->locker}");
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
        
        $this->logger->log(serialize($lock));
        return $lock;
    }
     */

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
}
?>