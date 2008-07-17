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
        require_once('PHPTAL.php'); // FIXME: Better place required
        require_once 'PHPTAL/GetTextTranslator.php'; // FIXME: Better place required
        $this->configuration = $configuration;
        try {
            $tr = new PHPTAL_GetTextTranslator();
            $component = $configuration->get_component();

            // set language to use for this session (first valid language will 
            // be used)
            $lang = $_MIDCOM->configuration->get('default_language');
            $tr->setLanguage($lang.'.utf8', $lang);
                    
            // register gettext domain to use
            $path = MIDCOM_ROOT . "/{$component}/locale/";

            $tr->addDomain($component, $path);

            // specify current domain
            $tr->useDomain($component);
            
            // tell PHPTAL to use our translator
            $_MIDCOM->templating->set_gettext_translator($tr);
        }
        catch (Exception $e)
        {
            echo $e;
        }

    }

    public function initialize()
    {
        $this->on_initialize();
    }

    public function on_initialize()
    {
    }
}
?>