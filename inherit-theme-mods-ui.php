<?php
/**
 * @package inherit-theme-mods
 */
class Inherit_Theme_Mods_UI {

    const NONCE_ACTION = 'inherit_theme_mods_nonce_action';
    const CAPABILITY   = 'manage_options';

    static private $ajax_actions = array(
        'inherit' => 'ITM_inherit',
        'restore' => 'ITM_restore',
    );
    private $itm;

    function __construct() {
        $this->itm = new Inherit_Theme_Mods();
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'wp_ajax_' . self::$ajax_actions['inherit'], array( $this, 'ajax_inherit' ) );
        add_action( 'wp_ajax_' . self::$ajax_actions['restore'], array( $this, 'ajax_restore' ) );
    }

    function register_admin_menu()
    {
    	$hook = add_options_page(
    		__( 'Inherit Theme Mods', ITM_TEXT_DOMAIN ),
    		__( 'Inherit Theme Mods', ITM_TEXT_DOMAIN ),
    		self::CAPABILITY,
    		ITM_TEXT_DOMAIN,
    		array( $this, 'describe_ui' )
    	);
        add_action( "admin_head-$hook", array( $this, 'enqueue_scripts' ) );
    }

    function enqueue_scripts() {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script(
            'itm_script',
            ITM_Util::url( 'assets', 'inherit-theme-mods.js' ),
            array( 'jquery' )
        );
        wp_enqueue_style(
            'font-awesome',
            ITM_Util::url( 'lib', 'font-awesome', 'css', 'font-awesome.min.css' )
        );
        wp_enqueue_style(
            'itm_style',
            ITM_Util::url( 'assets', 'inherit-theme-mods.css' ),
            array( 'font-awesome' )
        );
        wp_localize_script( 'itm_script', 'ajax', array(
            'endpoint' => admin_url( 'admin-ajax.php'),
            'nonce'    => wp_create_nonce( self::NONCE_ACTION ),
            'status'   => array(
                'updating' => __( 'updating..', ITM_TEXT_DOMAIN ),
                'finished' => __( 'finished!', ITM_TEXT_DOMAIN ),
                'success'  => __( 'Processed successfully.', ITM_TEXT_DOMAIN  ) . '<a href="' . esc_url( home_url( '/' ) ) . '" class="ITM-aside">' . __( 'Visit Site', ITM_TEXT_DOMAIN ) . '</a>',
                'error'    => __( 'error', ITM_TEXT_DOMAIN ),
            ),
        ) );
    }

    function ajax_inherit() {
        $message = $this->check_ajax_not_acceptable( 'inherit' );
        if ( ! $message ) {
            $this->itm->inherit();
            wp_send_json_success( $this->get_new_table()->data ); # JSON, xss OK
        } else {
            wp_send_json_error( esc_html( $message ) );
        }
    }

    function ajax_restore() {
        $message = $this->check_ajax_not_acceptable( 'restore' );
        if ( ! $message ) {
            $this->itm->restore();
            wp_send_json_success( $this->get_new_table()->data ); # JSON, xss OK
        } else {
            wp_send_json_error( esc_html( $message ) );
        }
    }

    function describe_ui() {
        ?>
        <div id="ITM" class="wrap">
    		<h1 id="ITM-title"><?php _e( 'Inherit Theme Mods', ITM_TEXT_DOMAIN ); ?>
                <span id="ITM-instant-notifier" class="ITM-status-notifier ITM-aside"></span>
            </h1>
            <?php
            if ( ! $this->itm->is_child_theme_active() ) {
                ?>
                <div id="ITM-notifier" class="notice notice-warning">
                    <p>
                        <?php _e( 'Active theme has no template and is not child theme.', ITM_TEXT_DOMAIN ); ?>
                    </p>
                </div>
                <?php
                $this->describe_list_table_area();
            } else {
                $this->describe_header_area();
                $this->describe_list_table_area();
            }
            ?>
        </div><!--#ITM-->
    	<?php
    }

    static function describe_header_area() {
        ?>
        <div id="ITM-notifier" class="ITM-visit-site notice notice-success">
            <p>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <?php _e('Visit Site', 'default'); ?>
                </a>
            </p>
        </div>
        <form class="ITM-form">
            <h2 class="ITM-action-header"><?php  _e( 'Inherit Properties', ITM_TEXT_DOMAIN ); ?></h2>
            <p><?php _e( "Copy parent theme's properties to child. The last child properties are stored at trash box once for backup.", ITM_TEXT_DOMAIN ); ?></p>
            <div class="ITM-action-table">
                <div class="ITM-action-block">
                    <div class="ITM-action-element ITM-button-col">
                        <a id="ITM-inherit" class="ITM-button button button-primary button-large" data-action="<?php echo esc_attr( self::$ajax_actions['inherit']); ?>">
                            <?php _e( 'inherit', ITM_TEXT_DOMAIN ); ?>
                        </a>
                    </div>
                    <div class="ITM-action-element ITM-picture-col">
                        <i class="fa fa-file-o fa-fw fa-3x"></i>
                        <i class="fa fa-arrow-right fa-2x"></i>
                        <i class="fa fa-copy fa-fw fa-3x"></i>
                        <i class="fa fa-arrow-right fa-2x"></i>
                        <i class="fa fa-trash-o fa-fw fa-3x"></i>
                    </div>
                </div>
            </div>
            <h2 class="ITM-action-header"><?php  _e( 'Restore Properties', ITM_TEXT_DOMAIN ); ?></h2>
            <p><?php _e( "Restore child properties from trash box.", ITM_TEXT_DOMAIN ); ?></p>
            <div class="ITM-action-table">
                <div class="ITM-action-block">
                    <div class="ITM-action-element ITM-button-col">
                        <a id="ITM-restore" class="ITM-button button button-primary button-large" data-action="<?php echo esc_attr( self::$ajax_actions['restore']); ?>">
                            <?php _e( 'restore', ITM_TEXT_DOMAIN ); ?>
                        </a>
                    </div>
                    <div class="ITM-action-element ITM-picture-col">
                        <i class="fa fa-copy fa-fw fa-3x"></i>
                        <i class="fa fa-arrow-left fa-2x"></i>
                        <i class="fa fa-trash fa-fw fa-3x"></i>
                    </div>
                </div>
            </div>
        </form>
        <?php
    }

    function describe_list_table_area() {
    	// generate list table with Admin Table class

        $itmTable = $this->get_new_table();
    	$itmTable->prepare_items();
    	echo '<div id="ITM-Content" class="wrap">';
    	$itmTable->display();
    	echo '</div>';
    }

    function get_new_table() {
        return new Inherit_Theme_Mods_Table(
            $this->itm->child_theme_slug,
            $this->itm->parent_theme_slug
        );
    }

    static function verify_nonce() {
        if ( func_num_args() > 0 ) {
            return wp_verify_nonce( func_get_arg( 0 ) , self::NONCE_ACTION );
        } elseif ( isset( $_REQUEST['nonce'] ) ) {
            return wp_verify_nonce( $_REQUEST['nonce'] , self::NONCE_ACTION );
        } else {
            return false;
        }
    }

    public function check_ajax_not_acceptable( $method ) {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            return __( 'You do not have sufficient permissions for the request.', ITM_TEXT_DOMAIN );

        } else if ( ! self::verify_nonce() ) {
            return __('Invalid request.', ITM_TEXT_DOMAIN );

        } else if ( ! $this->itm->is_child_theme_active() ) {
            return sprintf( __( 'No Child Theme to %1$s.', ITM_TEXT_DOMAIN ), $method );

        } else {
            return false;
        }
    }
}
