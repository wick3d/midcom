<?php
/**
 * @package midcom_service_sessionauth
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
 
/**
 * Provides a session based authentication method.
 *
 * @package midcom_service_sessionauth
 */

class midcom_service_sessionauth_sessionauth implements midcom_core_services_authentication
{
    private $user = null;
    private $person = null;
    private $sitegroup = null;
    private $session_cookie = null;

    private $current_session_id = null;

    public function __construct()
    {
        $this->session_cookie = new midcom_service_sessionauth_cookie();
    }
    
    public function login($username, $password)
    { 
        if( $this->session_cookie->read_login_session())
        {
            $sessionid = $this->session_cookie->get_session_id();
            return $this->authenticate_session($sessionid);
        }
        return $this->create_login_session($username, $password);
    }
    
    public function is_user()
    {
        if (!$this->user)
        {
            return false;
        }
        
        return true;
    }
    
    public function get_person()
    {
        if (!$this->is_user())
        {
            return null;
        }
        
        if (is_null($this->person))
        {
            $this->person = new midgard_person($this->user->guid);
        }
        
        return $this->person;
    }

    private function do_midgard_login($username, $password)
    {
        if (extension_loaded('midgard2'))
        {
            // In Midgard2 we need current SG name for authentication
            $this->sitegroup = $_MIDGARD_CONNECTION->get_sitegroup();
        }
        
        $this->user = midgard_user::auth($username, $password, $this->sitegroup);
        
        if (!$this->user)
        {
            return false;
        }
        
        return true;
    }
    


    private function create_login_session($username, $password, $clientip = null)
    {
        if( is_null($clientip))
        {
            $clientip = $_SERVER['REMOTE_ADDR'];
        }
        
        if( !$this->do_midgard_login($username, $password))
        {
            return false;
        }
        
        $session = new midcom_core_login_session_db();
        $session->userid = $this->user->guid;
        $session->username = $username;
        $session->password = $this->_obfuscate_password($password);
        $session->clientip = $clientip;
        $session->timestamp = time();

        if(! $session->create())
        {
            // TODO: Add some exception?
            return false;
        }
        
        $result = Array
        (
            'session_id' => $session->guid,
            'user' => &$user
        );
        
        $this->current_session_id = $session->guid;
        
        $this->session_cookie->create_login_session_cookie($session->guid, $this->user->guid);
        
        return $result;
        
    }
    
    public function logout()
    {
        return true;
    }
    
    /**
      * This function authenticates a session that has been created 
      * previously with load_login_session (mandatory)
      * 
      * On success ... TODO: Write more
      *
      * If authentication fails, given session id will be deleted
      * from database immediately.
      *
      * @param string $sessionid The session identifier to authenticate against
      * @param bool Indicating success
      */ 
    public function authenticate_session($sessionid)
    {
        $qb = new midgard_query_builder('midcom_core_login_session_db');
        $qb->add_constraint('guid', '=', $sessionid);
        $res = $qb->execute();
        if(! $res)
        {
            return false;
        }
        
        $session = $res[0];
        
        $username = $session->username;
        $password = $this->_unobfuscate_password($session->password);
        
        if(! $this->do_midgard_login($username, $password))
        {
            if(! $session->delete())
            {
                // TODO: Throw exception
                // TODO: Sessions must be purged time to time
            }
            return false;
        }
        $this->current_session_id = $session->guid;
        return true;
    }

    /**
      * This function obfuscates a password in some way so that accidential
 	  * "views" of a password in the database or a log are not immediately
 	  * a problem. This is not targeted to prevent intrusion, just to prevent
 	  * somebody viewing the logs or debugging the system is able to just
 	  * read somebody elses passwords (especially given that many users
 	  * share their passwords over multiple systems).
      *
      * _unobfuscate_password() is used to restore the password into its original
      * form.
      *
      * @param string $password The password to obfuscate.
      * @return string The obfuscated password.
      * @see _unobfuscate_password()
      * @access private
 	  */
    
    private function _obfuscate_password($password)
    {
        return base64_encode($password);
    }
    
    /**
    * Reverses password obfuscation.
    *
    * @param string $password The password to obfuscate.
    * @return string The obfuscated password.
    * @see _unobfuscate_password()
    * @access private
    */
    function _unobfuscate_password($password)
    {
        return base64_decode($password);
    }
    
    public function handle_exception(Exception $exception)
    {
        if (!isset($_SERVER['PHP_AUTH_USER']))
        {
            header("WWW-Authenticate: Basic realm=\"Midgard\"");
            header('HTTP/1.0 401 Unauthorized');
            // TODO: more fancy 401 output ?
            echo "<h1>Authorization required</h1>\n";
            exit();
        }
        
        if (!$this->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
        {
            // Wrong password: Recurse until auth ok or user gives up
            unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
            $this->handle_exception($exception);
        }
    }

    
}

    