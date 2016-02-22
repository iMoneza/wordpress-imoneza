<?php
/**
 * Main App
 *
 * @author Aaron Saray
 */

namespace iMonezaPRO;
use Pimple\Container;
use iMonezaPRO\Traits;


/**
 * Class Admin
 * @package iMonezaPRO
 */
class Admin
{
    use Traits\Options;

    /**
     * @var string used to indicate it's the settings page
     */
    const SETTINGS_PAGE_IDENTIFIER = 'imoneza-pro-settings';

    /**
     * @var Container
     */
    protected $di;

    /**
     * App constructor.
     * @param Container $di
     */
    public function __construct(Container $di)
    {
        $this->di = $di;

        $firstTime = empty($this->getOptions());

        if ($firstTime) {
            $this->addAdminNoticeConfigNeeded();
        }

        add_action('admin_init', function() {
            register_setting('imoneza-options', 'imoneza-options');
        });

        add_action('admin_menu', function() use ($firstTime, $di) {
            add_options_page('iMoneza Options', 'iMoneza', 'manage_options', self::SETTINGS_PAGE_IDENTIFIER, $firstTime ? $di['controller.first-time-options'] : $di['controller.options']);
        });
        add_action('wp_ajax_first-time-settings', function() use ($di) {
            /** @var \iMonezaPRO\Controller\FirstTimeOptions $controller */
            $controller = $di['controller.first-time-options'];
            $controller();
        });
        add_action('wp_ajax_settings', function() use ($di) {
            /** @var \iMonezaPRO\Controller\Options $controller */
            $controller = $di['controller.options'];
            $controller();
        });
        add_action('wp_ajax_refresh_settings', function() use ($di) {
            /** @var \iMonezaPRO\Controller\RefreshOptions $controller */
            $controller = $di['controller.refresh-options'];
            $controller();
        });

        add_action('admin_enqueue_scripts', function() {
            wp_register_style('imoneza-admin-css', WP_PLUGIN_URL . '/imoneza-pro/assets/css/admin.css');
            wp_enqueue_style('imoneza-admin-css');
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-form');
            wp_enqueue_script('imoneza-admin-js', WP_PLUGIN_URL . '/imoneza-pro/assets/js/admin.js', [], false, true);
        });

        // this is scheduled hourly AFTER the first time we've kicked this off, or if we have this configured
        add_action('imoneza_hourly', function() use ($di) {
            /** @var \iMonezaPRO\Controller\RefreshOptions $controller */
            $controller = $di['controller.refresh-options'];
            $controller();
        });
        register_activation_hook('imoneza-pro/imoneza-pro.php', function() use ($firstTime) {
            if (!$firstTime) wp_schedule_event(time(), 'hourly', 'imoneza_hourly');
        });
        register_deactivation_hook('imoneza-pro/imoneza-pro.php', function() {
            wp_clear_scheduled_hook('imoneza_hourly');
        });
    }

    /**
     * Show the config if need be
     */
    public function addAdminNoticeConfigNeeded()
    {
        global $pagenow;

        if (!($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == self::SETTINGS_PAGE_IDENTIFIER)) {
            add_action('admin_notices', function() {
                View::render('notify-config-needed');
            });
        }
    }
}