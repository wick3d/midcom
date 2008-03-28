<?php
/**
 * @package midcom_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Basic UI Message class
 *
 * @package midcom_core
 */
class midcom_core_services_uimessages_midgard implements midcom_core_services_uimessage
{
    private $configuration = array();
    private $jsconfiguration = '{}';

    /**
     * The current message stack
     *
     * @var Array
     * @access private
     */
    private $message_stack = array();

    /**
     * List of messages retrieved from session to avoid storing them again
     *
     * @var Array
     * @access private
     */
    private $messages_from_session = array();

    /**
     * ID of the latest UI message added so we can auto-increment
     *
     * @var integer
     * @access private
     */
    private $latest_message_id = 0;

    /**
     * List of allowed message types
     *
     * @var Array
     * @access private
     */
    private $allowed_types = array();
    
    public function __construct(&$configuration=array())
    {
        $this->set_configuration($configuration);
        
        $_MIDCOM->head->enable_jsmidcom();
        $_MIDCOM->head->add_jsfile(MIDCOM_STATIC_URL . "/midcom_core/services/uimessages/midgard.js");
        $_MIDCOM->head->add_jsfile(MIDCOM_STATIC_URL . "/midcom_core/jQuery/jquery.dimensions-1.1.2.js");
        
        $_MIDCOM->head->add_link_head(
            array
            (
                'rel'   => 'stylesheet',
                'type'  => 'text/css',
                'media' => 'screen',
                'href'  => MIDCOM_STATIC_URL . '/midcom_core/services/uimessages/midgard.css',
            )
        );
        $_MIDCOM->head->add_link_head(
            array
            (
                'condition' => 'eq IE',
                'rel'   => 'stylesheet',
                'type'  => 'text/css',
                'media' => 'screen',
                'href'  => MIDCOM_STATIC_URL . '/midcom_core/services/uimessages/midgard-ie.css',
            )
        );
        
        // Set the list of allowed message types
        $this->allowed_types[] = 'info';
        $this->allowed_types[] = 'ok';
        $this->allowed_types[] = 'warning';
        $this->allowed_types[] = 'error';
        $this->allowed_types[] = 'debug';
        
        $this->get_messages();
    }
    
    private function set_configuration($configuration)
    {
        $this->configuration = $configuration;
        
        if (! array_key_exists('className', $this->configuration))
        {
            $this->configuration['className'] = 'midcom_services_uimessages_midgard';
        }
        
        if (array_key_exists('js', $this->configuration))
        {
            $jsconfig = '{';
            
            $config_length = count($this->configuration['js']);
            $curr_key_i = 1;
            foreach ($this->configuration['js'] as $key => $value)
            {
                $jsconfig .= "{$key}: {$value}";
                if ($curr_key_i < $config_length)
                {
                    $jsconfig .= ", ";
                }                
                $curr_key_i += 1;      
            }
            
            $jsconfig .= '}';
            
            $this->jsconfiguration = $jsconfig;
        }
    }
    
    private function get_messages()
    {
        // Read messages from session
        $session = new midcom_core_services_sessioning('midcom_services_uimessages');
        if ($session->exists('midcom_services_uimessages_stack'))
        {
            // We've got old messages in the session
            $stored_messages = $session->get('midcom_services_uimessages_stack');
            $session->remove('midcom_services_uimessages_stack');
            if (! is_array($stored_messages))
            {
                return false;
            }

            foreach ($stored_messages as $message)
            {                
                $id = $this->add($message);
                $this->messages_from_session[] = $id;
            }
        }

        return $this->messages_from_session;
    }
    
    public function has_messages()
    {
        if (count($this->message_stack) > 0)
        {
            return true;
        }
        
        return false;
    }
    
    /**
     * Store unshown UI messages from the stack to user session.
     */
    public function store()
    {
        if (count($this->message_stack) == 0)
        {
            // No unshown messages
            return true;
        }

        // We have to be careful what messages to store to session to prevent them
        // from accumulating
        $messages_to_store = array();
        foreach ($this->message_stack as $id => $message)
        {
            // Check that the messages were not coming from earlier session
            if (! in_array($id, $this->messages_from_session))
            {
                $messages_to_store[$id] = $message;
            }
        }
        if (count($messages_to_store) == 0)
        {
            // We have only messages coming from earlier sessions, and we ditch those
            return true;
        }

        $session = new midcom_core_services_sessioning('midcom_services_uimessages');

        // Check if some other request has added stuff to session as well
        if ($session->exists('midcom_services_uimessages_stack'))
        {
            $old_stack = $session->get('midcom_services_uimessages_stack');
            $messages_to_store = array_merge($old_stack, $messages_to_store);
        }
        $session->set('midcom_services_uimessages_stack', $messages_to_store);
        $this->message_stack = array();
    }
    
    /**
     * Add a message to be shown to the user.
     * @param array $data Message parts
     */
    public function add($data)
    {        
        if (   !array_key_exists('title', $data)
            || !array_key_exists('message', $data))
        {
            return false;
        }
        
        if (! array_key_exists('type', $data))
        {
            $data['type'] = 'info';
        }
        
        // Make sure the given class is allowed
        if (! in_array($data['type'], $this->allowed_types))
        {
            // Message class not in allowed list
            return false;
        }

        // Normalize the title and message contents
        $title = str_replace("'", '"', $data['title']);
        $message = str_replace("'", '"', $data['message']);

        $this->latest_message_id++;

        // Append to message stack
        $this->message_stack[$this->latest_message_id] = array(
            'title'   => $title,
            'message' => $message,
            'type'    => $data['type'],
        );
        
        return $this->latest_message_id;
    }
    
    public function remove($key)
    {
        if (array_key_exists($key, $this->message_stack))
        {
            unset($this->message_stack[$key]);
        }
    }
    
    public function get($key)
    {
        if (array_key_exists($key, $this->message_stack))
        {
            return $this->message_stack[$key];
        }
    }
    
    public function render($key=null)
    {
        $html = '';

        if (count($this->message_stack) > 0)
        {
            $html .= "<div class=\"{$this->configuration['className']}\">\n";
            
            if (   !is_null($key)
                && array_key_exists($key, $this->message_stack))
            {
                $html .= $this->render_message($this->message_stack[$key]);

                // Remove the message from stack
                unset($this->message_stack[$key]);
            }
            else
            {
                foreach ($this->message_stack as $id => $message)
                {
                    $html .= $this->render_message($message);

                    // Remove the message from stack
                    unset($this->message_stack[$id]);
                }                
            }

            $html .= "</div>\n";
        }
        
        $html .= "<script type=\"text/javascript\">\n";
        $html .= "    jQuery(document).ready(function() {\n";
        $html .= "        jQuery('.{$this->configuration['className']}').midcom_services_uimessages_midgard({$this->jsconfiguration});\n";
        $html .= "    });\n";
        $html .= "</script>\n";
        
        return $html;
    }

    /**
     * Render single message to HTML
     */
    private function render_message($message)
    {
        $html = "<div class=\"{$this->configuration['className']}_message msu_{$message['type']}\">";

        $html .= "    <div class=\"{$this->configuration['className']}_message_type\">{$message['type']}</div>";
        $html .= "    <div class=\"{$this->configuration['className']}_message_title\">{$message['title']}</div>";
        $html .= "    <div class=\"{$this->configuration['className']}_message_msg\">{$message['message']}</div>";

        $html .= "</div>\n";
        
        return $html;
    }
    
}

?>