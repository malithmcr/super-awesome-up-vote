<?php
/*
Plugin Name: Super Awesome  upVote
Plugin URI: http://malith.pro
Description: Voting plugin for wordpress posts.
Author: Malith Priyashan
Author URI: http://malith.pro
Version: 1.0.0
Text Domain: super-awesome-up-vote
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

//Exit if file called directly

if( ! defined( 'ABSPATH' ) ) {
    exit;
}


// admin stuff
if ( is_admin() ) {
    // include plugin dependencies
    require_once plugin_dir_path( __FILE__ ) . 'admin/menu.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';
}

/**
 * Main plugin class.
 */
class UpVote {
    /**
     * This plugin's version number. Used for busting caches.
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The single instance of this plugin.
     *
     *
     * @access private
     * @var    UpVote
     */
    private static $instance;

    /**
     * Creates a new instance of this class if one hasn't already been made
     * and then returns the single instance of this class.
     *
     * @return UpVote
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new UpVote;
            self::$instance->setup();
        }

        return self::$instance;
    }

    /**
     * Register all of the needed hooks and actions.
     */
    public function setup() {
        add_filter( 'the_content', array( $this, 'add_to_content' ) );
        add_action('wp_enqueue_scripts', array( $this, 'register_script' ));

        //Add ajax request
        add_action('wp_ajax_add_votes', array( $this, '_getVotes' ));
        add_action('wp_ajax_nopriv_add_votes', array( $this, '_getVotes' ));

        add_action('wp_head',array($this,'addPostIdToHead'));
        add_shortcode( 'superawesomeupvote', array($this,'super_awesome_up_vote_shortcode'));
    }


    /**
     * Add post Id to head
     * addPostIdToHead
     */

    function addPostIdToHead() {

        global $current_screen;

        ?>
        <script type="text/javascript">
            var up_vote_post_id = '<?php global $post; echo $post->ID; ?>';
        </script>
        <?php
    }


    /**
     * Insert all script files
     * register_admin_script
     */
    function register_script()
    {
        wp_enqueue_script('super_awesome_up_vote', plugin_dir_url(__FILE__) . 'assets/js/super-awesome-up-vote.js', array('jquery'), '0.0.1', true);
        wp_enqueue_style( 'super_awesome_up_vote', plugins_url( "assets/css/style.css", __FILE__ ), array(), '001' );
    }

    /**
     * add_to_content
     * add buttons to the content.
     */
    function add_to_content( $content ) {
        $options = get_option('up_vote_plugin_options');
        if(strtolower($options['vote_enable']) === 'enable') {
            $content = $content . self::$instance->vote_buttons() ;
        }
        return $content;
    }

    /**
     * super_awesome_up_vote_shortcode
     * Short code to show up votes
     */
    function super_awesome_up_vote_shortcode( $atts ){
        return self::$instance->vote_buttons();
    }

    /**
     * @private
     * vote_buttons
     * Button template
     */
    private function vote_buttons () {

        global $post;

        $options = get_option('up_vote_plugin_options');
        $getUpVotes = get_post_meta( $post->ID, 'super_aswesome_vote_up', true);
        $getDownVotes = get_post_meta( $post->ID, 'super_aswesome_vote_down', true);

        $currentUpVotes = ($getUpVotes) ? $getUpVotes : 0;
        $currentDownVotes = ($getDownVotes) ? $getDownVotes  : 0;

        $icon_thumbs_up = '<img width="20" src="data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMS4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDQ3OC4yIDQ3OC4yIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA0NzguMiA0NzguMjsiIHhtbDpzcGFjZT0icHJlc2VydmUiIHdpZHRoPSI1MTJweCIgaGVpZ2h0PSI1MTJweCI+CjxnPgoJPHBhdGggZD0iTTQ1Ny41NzUsMzI1LjFjOS44LTEyLjUsMTQuNS0yNS45LDEzLjktMzkuN2MtMC42LTE1LjItNy40LTI3LjEtMTMtMzQuNGM2LjUtMTYuMiw5LTQxLjctMTIuNy02MS41ICAgYy0xNS45LTE0LjUtNDIuOS0yMS04MC4zLTE5LjJjLTI2LjMsMS4yLTQ4LjMsNi4xLTQ5LjIsNi4zaC0wLjFjLTUsMC45LTEwLjMsMi0xNS43LDMuMmMtMC40LTYuNCwwLjctMjIuMywxMi41LTU4LjEgICBjMTQtNDIuNiwxMy4yLTc1LjItMi42LTk3Yy0xNi42LTIyLjktNDMuMS0yNC43LTUwLjktMjQuN2MtNy41LDAtMTQuNCwzLjEtMTkuMyw4LjhjLTExLjEsMTIuOS05LjgsMzYuNy04LjQsNDcuNyAgIGMtMTMuMiwzNS40LTUwLjIsMTIyLjItODEuNSwxNDYuM2MtMC42LDAuNC0xLjEsMC45LTEuNiwxLjRjLTkuMiw5LjctMTUuNCwyMC4yLTE5LjYsMjkuNGMtNS45LTMuMi0xMi42LTUtMTkuOC01aC02MSAgIGMtMjMsMC00MS42LDE4LjctNDEuNiw0MS42djE2Mi41YzAsMjMsMTguNyw0MS42LDQxLjYsNDEuNmg2MWM4LjksMCwxNy4yLTIuOCwyNC03LjZsMjMuNSwyLjhjMy42LDAuNSw2Ny42LDguNiwxMzMuMyw3LjMgICBjMTEuOSwwLjksMjMuMSwxLjQsMzMuNSwxLjRjMTcuOSwwLDMzLjUtMS40LDQ2LjUtNC4yYzMwLjYtNi41LDUxLjUtMTkuNSw2Mi4xLTM4LjZjOC4xLTE0LjYsOC4xLTI5LjEsNi44LTM4LjMgICBjMTkuOS0xOCwyMy40LTM3LjksMjIuNy01MS45QzQ2MS4yNzUsMzM3LjEsNDU5LjQ3NSwzMzAuMiw0NTcuNTc1LDMyNS4xeiBNNDguMjc1LDQ0Ny4zYy04LjEsMC0xNC42LTYuNi0xNC42LTE0LjZWMjcwLjEgICBjMC04LjEsNi42LTE0LjYsMTQuNi0xNC42aDYxYzguMSwwLDE0LjYsNi42LDE0LjYsMTQuNnYxNjIuNWMwLDguMS02LjYsMTQuNi0xNC42LDE0LjZoLTYxVjQ0Ny4zeiBNNDMxLjk3NSwzMTMuNCAgIGMtNC4yLDQuNC01LDExLjEtMS44LDE2LjNjMCwwLjEsNC4xLDcuMSw0LjYsMTYuN2MwLjcsMTMuMS01LjYsMjQuNy0xOC44LDM0LjZjLTQuNywzLjYtNi42LDkuOC00LjYsMTUuNGMwLDAuMSw0LjMsMTMuMy0yLjcsMjUuOCAgIGMtNi43LDEyLTIxLjYsMjAuNi00NC4yLDI1LjRjLTE4LjEsMy45LTQyLjcsNC42LTcyLjksMi4yYy0wLjQsMC0wLjksMC0xLjQsMGMtNjQuMywxLjQtMTI5LjMtNy0xMzAtNy4xaC0wLjFsLTEwLjEtMS4yICAgYzAuNi0yLjgsMC45LTUuOCwwLjktOC44VjI3MC4xYzAtNC4zLTAuNy04LjUtMS45LTEyLjRjMS44LTYuNyw2LjgtMjEuNiwxOC42LTM0LjNjNDQuOS0zNS42LDg4LjgtMTU1LjcsOTAuNy0xNjAuOSAgIGMwLjgtMi4xLDEtNC40LDAuNi02LjdjLTEuNy0xMS4yLTEuMS0yNC45LDEuMy0yOWM1LjMsMC4xLDE5LjYsMS42LDI4LjIsMTMuNWMxMC4yLDE0LjEsOS44LDM5LjMtMS4yLDcyLjcgICBjLTE2LjgsNTAuOS0xOC4yLDc3LjctNC45LDg5LjVjNi42LDUuOSwxNS40LDYuMiwyMS44LDMuOWM2LjEtMS40LDExLjktMi42LDE3LjQtMy41YzAuNC0wLjEsMC45LTAuMiwxLjMtMC4zICAgYzMwLjctNi43LDg1LjctMTAuOCwxMDQuOCw2LjZjMTYuMiwxNC44LDQuNywzNC40LDMuNCwzNi41Yy0zLjcsNS42LTIuNiwxMi45LDIuNCwxNy40YzAuMSwwLjEsMTAuNiwxMCwxMS4xLDIzLjMgICBDNDQ0Ljg3NSwyOTUuMyw0NDAuNjc1LDMwNC40LDQzMS45NzUsMzEzLjR6IiBmaWxsPSIjMDAwMDAwIi8+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPGc+CjwvZz4KPC9zdmc+Cg==" />';
        $icon_thumbs_down = '<img width="20" src="data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTYuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgd2lkdGg9IjY0cHgiIGhlaWdodD0iNjRweCIgdmlld0JveD0iMCAwIDQ3NS4wOTIgNDc1LjA5MiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNDc1LjA5MiA0NzUuMDkyOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxnPgoJPHBhdGggZD0iTTQ0Mi44MjIsMjA5LjU2MmMxLjcxNS02LjI4MywyLjU3LTEyLjg0NywyLjU3LTE5LjcwMmMwLTE0LjY1NS0zLjYyMS0yOC4zNjEtMTAuODUyLTQxLjExMiAgIGMwLjU2Ny0zLjk5NSwwLjg1NS04LjA4OCwwLjg1NS0xMi4yNzVjMC0xOS4yMjMtNS43MTYtMzYuMTYyLTE3LjEzMi01MC44MTl2LTEuNDI3YzAuMTkxLTI2LjA3NS03Ljk0Ni00Ni42MzItMjQuNDE0LTYxLjY2OSAgIEMzNzcuMzg3LDcuNTIxLDM1NS44MzEsMCwzMjkuMTg2LDBoLTMxLjk3N2MtMTkuOTg1LDAtMzkuMDIsMi4wOTMtNTcuMTAyLDYuMjhjLTE4LjA4Niw0LjE4OS0zOS4zMDQsMTAuNDY4LTYzLjY2NiwxOC44NDIgICBjLTIyLjA4LDcuNjE2LTM1LjIxMSwxMS40MjItMzkuMzk5LDExLjQyMkg1NC44MjFjLTEwLjA4OCwwLTE4LjcwMiwzLjU2Ny0yNS44NCwxMC43MDRDMjEuODQ1LDU0LjM4NywxOC4yNzYsNjMsMTguMjc2LDczLjA4NSAgIHYxODIuNzI4YzAsMTAuMDg5LDMuNTY2LDE4LjY5OCwxMC43MDUsMjUuODM3YzcuMTQyLDcuMTM5LDE1Ljc1MiwxMC43MDUsMjUuODQsMTAuNzA1aDc4LjIyOCAgIGM2Ljg0OSw0LjU3MiwxOS44ODksMTkuMzI0LDM5LjExMyw0NC4yNTVjMTEuMjMxLDE0LjY2MSwyMS40MTYsMjYuNzQxLDMwLjU1MSwzNi4yNjVjMy42MTIsMy45OTcsNi41NjQsMTAuMDg5LDguODQ4LDE4LjI3MSAgIGMyLjI4NCw4LjE4NiwzLjk0OSwxNi4yMjgsNC45OTgsMjQuMTI2YzEuMDQ3LDcuODk4LDMuNDc1LDE2LjUxNiw3LjI4MSwyNS44MzdjMy44MDYsOS4zMjksOC45NDQsMTcuMTM5LDE1LjQxNSwyMy40MjIgICBjNy40MjMsNy4wNDMsMTUuOTg1LDEwLjU2MSwyNS42OTcsMTAuNTYxYzE1Ljk4OCwwLDMwLjM2MS0zLjA4Nyw0My4xMTItOS4yNzRjMTIuNzU0LTYuMTg0LDIyLjQ2My0xNS44NDUsMjkuMTI2LTI4Ljk4MSAgIGM2LjY2My0xMi45NDMsOS45OTYtMzAuNjQ2LDkuOTk2LTUzLjEwM2MwLTE3LjcwMi00LjU2OC0zNS45NzQtMTMuNzAyLTU0LjgxOWg1MC4yNDRjMTkuODAxLDAsMzYuOTI1LTcuMjMsNTEuMzk0LTIxLjcgICBjMTQuNDY5LTE0LjQ2MiwyMS42OTMtMzEuNDk3LDIxLjY5My01MS4xMDNDNDU2LjgwOSwyMzkuMTY1LDQ1Mi4xNSwyMjMuNjUyLDQ0Mi44MjIsMjA5LjU2MnogTTg1Ljk0MiwxMDQuMjE5ICAgYy0zLjYxNiwzLjYxNS03Ljg5OCw1LjQyNC0xMi44NDcsNS40MjRjLTQuOTUsMC05LjIzMy0xLjgwNS0xMi44NS01LjQyNGMtMy42MTUtMy42MjEtNS40MjQtNy44OTgtNS40MjQtMTIuODUxICAgYzAtNC45NDgsMS44MDktOS4yMzEsNS40MjQtMTIuODQ3YzMuNjIxLTMuNjE3LDcuOS01LjQyNCwxMi44NS01LjQyNGM0Ljk0OSwwLDkuMjMxLDEuODA3LDEyLjg0Nyw1LjQyNCAgIGMzLjYxNywzLjYxNiw1LjQyNiw3Ljg5OCw1LjQyNiwxMi44NDdDOTEuMzY4LDk2LjMxNyw4OS41NiwxMDAuNTk4LDg1Ljk0MiwxMDQuMjE5eiBNNDA5LjEzNSwyODEuMzc3ICAgYy03LjQyLDcuMzMtMTUuODg2LDEwLjk5Mi0yNS40MTMsMTAuOTkySDI4My4yMjdjMCwxMS4wNCw0LjU2NCwyNi4yMTcsMTMuNjk4LDQ1LjUzNWM5LjEzOCwxOS4zMjEsMTMuNzEsMzQuNTk4LDEzLjcxLDQ1LjgyOSAgIGMwLDE4LjY0Ny0zLjA0NiwzMi40NDktOS4xMzQsNDEuMzk1Yy02LjA5Miw4Ljk0OS0xOC4yNzQsMTMuNDIyLTM2LjU0NiwxMy40MjJjLTQuOTUxLTQuOTQ4LTguNTcyLTEzLjA0NS0xMC44NTQtMjQuMjc2ICAgYy0yLjI3Ni0xMS4yMjUtNS4xODUtMjMuMTY4LTguNzA2LTM1LjgzYy0zLjUxOS0xMi42NTUtOS4xOC0yMy4wNzktMTYuOTg0LTMxLjI2NmMtNC4xODQtNC4zNzMtMTEuNTE2LTEzLjAzOC0yMS45ODItMjUuOTggICBjLTAuNzYxLTAuOTUxLTIuOTUyLTMuODA2LTYuNTY3LTguNTYyYy0zLjYxNC00Ljc1Ny02LjYxMy04LjY1OC04Ljk5Mi0xMS43MDNjLTIuMzgtMy4wNDYtNS42NjQtNy4wOTEtOS44NTEtMTIuMTM2ICAgYy00LjE4OS01LjA0NC03Ljk5NS05LjIzMi0xMS40MjItMTIuNTY1Yy0zLjQyNy0zLjMyNy03LjA4OS02LjcwOC0xMC45OTItMTAuMTM3Yy0zLjkwMS0zLjQyNi03LjcxLTUuOTk2LTExLjQyMS03LjcwNyAgIGMtMy43MTEtMS43MTEtNy4wODktMi41NjYtMTAuMTM1LTIuNTY2aC05LjEzNlY3My4wOTJoOS4xMzZjMi40NzQsMCw1LjQ3LTAuMjgyLDguOTkzLTAuODU0YzMuNTE4LTAuNTcxLDYuNjU4LTEuMTkyLDkuNDE5LTEuODU4ICAgYzIuNzYtMC42NjYsNi4zNzctMS43MTMsMTAuODQ5LTMuMTRjNC40NzYtMS40MjUsNy44MDQtMi41MjIsOS45OTQtMy4yODNjMi4xOS0wLjc2Myw1LjU2OC0xLjk1MSwxMC4xMzgtMy41NzEgICBjNC41Ny0xLjYxNSw3LjMzLTIuNjEzLDguMjgtMi45OTZjNDAuMTU5LTEzLjg5NCw3Mi43MDgtMjAuODM5LDk3LjY0OC0yMC44MzloMzYuNTQyYzE2LjU2MywwLDI5LjUwNiwzLjg5OSwzOC44MjgsMTEuNzA0ICAgYzkuMzI4LDcuODA0LDEzLjk4OSwxOS43OTUsMTMuOTg5LDM1Ljk3NWMwLDQuOTQ5LTAuNDc5LDEwLjI3OS0xLjQyMywxNS45ODdjNS43MDgsMy4wNDYsMTAuMjMxLDguMDQyLDEzLjU1OSwxNC45ODcgICBjMy4zMzMsNi45NDUsNC45OTYsMTMuOTQ0LDQuOTk2LDIwLjk4NWMwLDcuMDM5LTEuNzExLDEzLjYxLTUuMTQxLDE5LjcwMWMxMC4wODksOS41MTcsMTUuMTI2LDIwLjgzOSwxNS4xMjYsMzMuOTc0ICAgYzAsNC43NTktMC45NDgsMTAuMDM5LTIuODQ3LDE1Ljg0NmMtMS44OTksNS44MDgtNC4yODUsMTAuMzI3LTcuMTM5LDEzLjU2MmM2LjA5MSwwLjE5MiwxMS4xODQsNC42NjUsMTUuMjc2LDEzLjQyMiAgIGM0LjA5Myw4Ljc1NCw2LjE0LDE2LjQ2OCw2LjE0LDIzLjEyN0M0MjAuMjc3LDI2NS41MjUsNDE2LjU2MSwyNzQuMDQzLDQwOS4xMzUsMjgxLjM3N3oiIGZpbGw9IiMwMDAwMDAiLz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8L3N2Zz4K" />';
        $icon_text_up = 'Up';
        $icon_text_down = 'Down';

        $template  = '<div class="super-awesome-up-vote-wrapper">';
        $template .= '<span id="super-awesome-up-vote">'.$currentUpVotes.'</span>';
        if(strtolower($options['vote_icon']) === 'text') {
            $template .= '<button id="super-awesome-up">'.$icon_text_up.'</button>';
        }
        if(strtolower($options['vote_icon']) === 'thumbsup') {
            $template .= '<button id="super-awesome-down">'.$icon_thumbs_up.'</button>';
        }
        $template .= '<span id="super-awesome-down-vote">-'.$currentDownVotes.'</span>';
        if(strtolower($options['vote_icon']) === 'text') {
            $template .= '<button id="super-awesome-down">'.$icon_text_down.'</button>';
        }
        if(strtolower($options['vote_icon']) === 'thumbsup') {
            $template .= '<button id="super-awesome-down">'.$icon_thumbs_down.'</button>';
        }
        $template .= '<input type="hidden" name="action" value="add_votes"/>';
        $template .= '</div>';

        return $template;
    }


    /**
     * @private
     * _getVotes
     * Get votes from the frontend.
     */
    function _getVotes() {

        if(isset($_POST['vote_type'])) {

            if($_POST['postId']) {
              $postId = (int)$_POST['postId'];
            }

            if($_POST['vote_type'] === 'vote_up') {
                $getUpVotes = get_post_meta( $postId, 'super_aswesome_vote_up', true);
                $currentUpVotes = ($getUpVotes) ? $getUpVotes + 1 : 1;
                update_post_meta($postId, 'super_aswesome_vote_up', $currentUpVotes);
            }

            if($_POST['vote_type'] === 'vote_down') {
                $getDownVotes = get_post_meta( $postId, 'super_aswesome_vote_down', true);
                $currentDownVotes = ($getDownVotes) ? $getDownVotes + 1 : 1;
                update_post_meta($postId, 'super_aswesome_vote_down', $currentDownVotes);
            }

        }
        return;
    }

}


/**
 * Returns the single instance of this plugin, creating one if needed.
 *
 * @return UpVote
 */
function UpVote() {
    return UpVote::instance();
}

/**
 * Initialize this plugin once all other plugins have finished loading.
 */
add_action( 'init', 'UpVote' );