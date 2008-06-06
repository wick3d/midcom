/**
 * @package midcom_service_sessionauth
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

(function($){
    
    $.midcom = $.midcom || {};
    $.midcom.service = $.midcom.service || {};
    
    $.midcom.service.sessionauth = {
        config: {
            prefix: ''
        }
    };
    $.extend($.midcom.service.sessionauth, {
        init: function(options) {
            $.midcom.service.sessionauth.options = $.midcom.services.configuration.merge($.midcom.service.sessionauth.config, config);
        }
    });
    
    $.midcom.register_component('midcom.service.sessionauth', $.midcom.service.sessionauth);
    
})(jQuery);