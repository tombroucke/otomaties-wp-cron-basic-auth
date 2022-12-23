<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
namespace Otomaties\WpCronBasicAuth;

/**
 * Plugin Name:       Otomaties WP Cron Basic Auth
 * Description:       Fix for WP Cron when using Basic Auth
 * Author:            Tom Broucke
 * Author URI:        https://tombroucke.be
 * Version:           1.2.0
 * Text Domain:       wp-cron-basic-auth
 * Domain Path:       /lang
 * License:           GPL v2 or later
 */

class Authenticator
{
    private string $transientKey;
    /**
     * Initialize authenticator
     *
     * @param string|null $user
     * @param string|null $pass
     */
    public function __construct(private ?string $user = null, private ?string $pass = null)
    {
        $this->transientKey = $transientKey = sprintf(
            'otomaties_wp_cron_basic_auth_connected_%s',
            md5($this->user . $this->pass)
        );

        add_action('admin_bar_menu', [$this, 'addStatusInToolbar'], 999);
        
        add_action('admin_post_wp_cron_basic_auth_force_test', function () use ($transientKey) {
            $nonce = $_GET['wp_cron_basic_auth_force_test_nonce'] ?? '';
            if (!wp_verify_nonce($nonce, 'wp_cron_basic_auth_force_test')) {
                wp_die('Invalid nonce');
            }
            delete_transient($transientKey);
            $referer = wp_get_referer();
            if (!$referer) {
                $referer = admin_url();
            }
            wp_safe_redirect(esc_url_raw($referer));
        });

        if ($user && $pass) {
            add_filter('cron_request', [$this, 'addBasicAuthHeader']);
        }
    }
    
    /**
     * Add basic auth headers to cron request
     *
     * @param array<string, mixed> $cronRequestArray
     * @return array<string, mixed>
     */
    public function addBasicAuthHeader(array $cronRequestArray) : array
    {
        if (!isset($cronRequestArray['args']['headers'])) {
            $cronRequestArray['args']['headers'] = [];
        }
        $authorizationHeader = [
            'Authorization' => $this->basicAuthHeader()
        ];
        $cronRequestArray['args']['headers'] = array_merge($cronRequestArray['args']['headers'], $authorizationHeader);
        
        return $cronRequestArray;
    }

    /**
     * Find variable in environment
     *
     * @param string $variableName
     * @return string|null
     */
    public static function findVariable(string $variableName) : ?string
    {
        if (defined($variableName)) {
            return constant($variableName);
        }
        if (isset($_SERVER[$variableName])) {
            return $_SERVER[$variableName];
        }
        if (isset($_ENV[$variableName])) {
            return $_ENV[$variableName];
        }
        
        return null;
    }

    /**
     * Test if we can connect to the website using the basic auth credentials
     *
     * @return boolean
     */
    public function isConnected() : bool
    {
        if (false === ( $connected = get_transient($this->transientKey) )) {
            $response = wp_remote_get(get_site_url(), [
                'headers' => [
                    'Authorization' => $this->basicAuthHeader(),
                ],
                'sslverify' => apply_filters('otomaties_wp_cron_basic_auth_test_sslverify', false)
            ]);
            $connected = !is_wp_error($response) && $response['response']['code'] !== 401;
            set_transient($this->transientKey, $connected, 12 * DAY_IN_SECONDS);
        }
        
        return $connected;
    }

    /**
     * Add status in WP Toolbar
     *
     * @param \WP_Admin_Bar $wpToolbar
     * @return void
     */
    public function addStatusInToolbar(\WP_Admin_Bar $wpToolbar) : void
    {
        if (!current_user_can(apply_filters('otomaties_wp_cron_basic_auth_status_capability', 'manage_options'))) {
            return;
        }
        $args = array(
            'id'    => 'wp-cron-basic-auth',
            'title' => sprintf(
                '<span class="ab-label">WP Cron Status</span><span class="ab-icon dashicons dashicons-%s"></span>',
                $this->isConnected() ? 'yes' : 'no'
            ),
            'href'  => wp_nonce_url(
                admin_url('admin-post.php?action=wp_cron_basic_auth_force_test'),
                'wp_cron_basic_auth_force_test',
                'wp_cron_basic_auth_force_test_nonce'
            ),
        );
        $wpToolbar->add_node($args);

        $args = array(
            'id'     => 'wp-cron-basic-auth-user',
            'title'  => sprintf('%s: %s', __('Username', 'sage'), $this->user ?? '-'),
            'parent' => 'wp-cron-basic-auth',
        );
        $wpToolbar->add_node($args);

        $args = array(
            'id'     => 'wp-cron-basic-auth-pass',
            'title'  => sprintf(
                '%s: %s',
                __('Password', 'sage'),
                $this->pass ? $this->obfuscatePassword($this->pass) : '-'
            ),
            'parent' => 'wp-cron-basic-auth',
        );
        $wpToolbar->add_node($args);
    }

    /**
     * Create basic auth header
     *
     * @return string
     */
    private function basicAuthHeader() : string
    {
        return sprintf('Basic %s', base64_encode($this->user . ':' . $this->pass));
    }

    /**
     * Replace middle characters of password with ****
     *
     * @param string $password
     * @return string
     */
    public function obfuscatePassword(string $password) : string
    {
        switch (strlen($password)) {
            case 0:
            case 1:
                return str_repeat('*', strlen($password));
            case 2:
                $firstChar = substr($password, 0, 1);
                $password = str_repeat('*', strlen($password) - 1);
                return $firstChar . $password;
            default:
                $firstChar = substr($password, 0, 1);
                $lastChar = substr($password, -1);
                $length = strlen($password);
                $password = str_repeat('*', $length - 2);
                $password = $firstChar . $password . $lastChar;
                return $password;
        }
    }
}

$basicAuthUser = Authenticator::findVariable('BASIC_AUTH_USER');
$basicAuthPass = Authenticator::findVariable('BASIC_AUTH_PASS');

$authenticator = new Authenticator($basicAuthUser, $basicAuthPass);
