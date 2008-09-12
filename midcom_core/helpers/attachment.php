<?php
/**
 * @package midcom_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Attachment helpers for MidCOM 3
 *
 *
 * @package midcom_core
 */
class midcom_core_helpers_attachment
{
    public function __construct() {}
    
    /**
      * Returns the url where the attachment can be found
      *
      * @param midgard_attachment $attachment An attachment object
      * @return string url
      */
    public static function get_url(midgard_attachment $attachment)
    {
        // FIXME: No ACL checking
        if ($_MIDCOM->configuration->enable_attachment_cache)
        {
            return $_MIDCOM->configuration->attachment_cache_url . $attachment->location;
        }
        else
        {
            return '/__midcom/serveattachment/' . $attachment->guid . '/';
        }
    }
    
    /**
      * Links file to public web folder.
      * 
      * @param midgard_attachment attachment An attachment object
      * @return true of false 
      */
    public static function add_to_cache(midgard_attachment $attachment)
    {
        $blob = new midgard_blob($attachment);
        
        // FIXME: Attachment directory creating should be done more elegantly
        $attachment_dir = explode('/', $attachment->location);
        $attachment_dir = $_MIDCOM->configuration->attachment_cache_directory . "{$attachment_dir[0]}/{$attachment_dir[1]}/";

        if(!is_dir($attachment_dir))
        {
            mkdir($attachment_dir, 0700, true);
        }
 
        return symlink ($blob->get_path(), $_MIDCOM->configuration->attachment_cache_directory.$attachment->location);
    }
    
    /**
      * Removes file from the public web folder
      *
      * @prarm midgard_attachment attachment An attachment object
      * @return true or false
      * 
      */
    public static function remove_from_cache(midgard_attachment $attachment)
    {
        $filepath = $_MIDCOM->configuration->attachment_cache_directory.$attachment->location;     
        if (is_file ($filepath))
        {
            return unlink ($filepath);
        }
        else
        {
            return false;
        }
    }
    
}

?>