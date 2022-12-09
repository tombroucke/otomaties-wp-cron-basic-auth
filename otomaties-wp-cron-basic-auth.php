<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
namespace Otomates\WpCronBasicAuth;

/**
 * Plugin Name:     Otomaties WP Cron Basic Auth
 * Description:     Fix for WP Cron when using Basic Auth
 * Author:          Tom Broucke
 * Author URI:      https://tombroucke.be
 * Version:         1.0.0
 * Text Domain:     wp-cron-basic-auth
 * Domain Path:     /lang
 * License:         GPL v2 or later
 */

class Authenticator
{
    /**
     * Initialize authenticator
     *
     * @param string $user
     * @param string $pass
     */
    public function __construct(private string $user, private string $pass)
    {
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
        $authorizationHeader = ['Authorization' => sprintf('Basic %s', base64_encode($this->user .':'. $this->pass))];
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
}

$basicAuthUser = Authenticator::findVariable('BASIC_AUTH_USER');
$basicAuthPass = Authenticator::findVariable('BASIC_AUTH_PASS');

if ($basicAuthUser && $basicAuthPass) {
    $authenticator = new Authenticator($basicAuthUser, $basicAuthPass);
    add_filter('cron_request', [$authenticator, 'addBasicAuthHeader']);
}
