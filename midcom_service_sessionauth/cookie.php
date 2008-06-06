<?php

class midcom_service_sessionauth_cookie
{
    private $_cookie_id = 'midcom_services_auth_backend_simple-';
    protected $session_id = null;
    protected $user_id = null;
    
    public function read_login_session()
    {
        $reset_cookie = false;
        if (   array_key_exists($this->_cookie_id, $_GET)
             && !array_key_exists($this->_cookie_id, $_COOKIE))
        {
            $reset_cookie = true;
        }
        
        if (! array_key_exists($this->_cookie_id, $_COOKIE))
        {
            return false;
        }
        
        $data = explode('-', $_COOKIE[$this->_cookie_id]);
        if (count($data) != 2)
        {
            $this->delete_cookie();
            return false;
        }
        
        $this->session_id = $data[0];
        $this->user_id = $data[1];
        
        if ($reset_cookie)
        {
            $this->set_cookie();
        }
        
        return true;
    }
        
    private function set_cookie()
    {
        // TODO: Make config available so no suppression is needed
        setcookie(
                    $this->_cookie_id,
                    "{$this->session_id}-{$this->user_id}",
                    0,
                    @$GLOBALS['midcom_config']['auth_backend_simple_cookie_path']
                );
    }
    
    public function get_session_id()
    {
        return $this->session_id;
    }
    
    private function delete_cookie()
    {
        // TODO: Make config available so no suppression is needed
        setcookie(
                    $this->_cookie_id,
                    false,
                    0,
                    @$GLOBALS['midcom_config']['auth_backend_simple_cookie_path']
                );
    }
    
    public function create_login_session_cookie($session_id, $user_id)
    {
        $this->session_id = $session_id;
        $this->user_id = $user_id;
        $this->set_cookie();
    }
    
    public function delete_login_session_cookie()
    {
        $this->delete_cookie();
    }
}
?>