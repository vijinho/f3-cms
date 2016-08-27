<?php

namespace FFCMS\Traits;

use FFMVC\Helpers;


/**
 * Check for CSRF code (see counterpart CSRF code in app/app.php)
 * and dns blacklist check
 *
 * If a config key called 'security.csrf' is true, will redirect the client
 * to the supplied url (or homepage if not). The URL can be a full internal URL
 * or an f3 url alias.
 */
trait SecurityController
{
    /**
     * Create an internal URL
     * Uses method from
     * @see \FFCMS\Helpers\UrlHelper
     * @param string $url
     * @param array $params
     */
    abstract public function url(string $url, array $params = []): string;

    /**
     * Check for CSRF token, reroute if failed on POST, or generate if GET
     * Call this method from a controller method class to check/generate csrf token
     * then include $f3-get('csrf') as a hidden type in your form to be submitted
     *
     * @param string $url if csrf check fails
     * @param array $params for querystring
     * @return boolean true/false if csrf enabled
     */
    public function csrf(string $url = '@index', array $params = []): bool
    {
        $f3 = \Base::instance();

        // csrf disabled
        if (empty($f3->get('security.csrf'))) {
            return false;
        }

        // security check
        // true if page iS GET, OR page is POST and REQUEST csrf matched session
        $verb = $f3->get('VERB');
        $passed = ('GET' == $verb) ||
            ('POST' == $verb && $f3->get('REQUEST.csrf') === $f3->get('SESSION.csrf'));

        // redirect if not POST or csrf present
        if (!$passed) {
            $f3->clear('SESSION.csrf');
            $url = $this->url($url, $params);
            return $f3->reroute($url);
        }

        // if GET/POST generate a new csrf token
        $session = \Registry::get('session');
        $f3->csrf = $session->csrf();
        $f3->copy('csrf', 'SESSION.csrf');

        return true;
    }


    /**
     * Check ip-address is blacklisted, halt, if-so
     *
     * @return bool
     */
    public function dnsbl(): bool
    {
        $f3 = \Base::instance();
        $cache = \Cache::instance();

        $ip = $f3->get('IP');
        $f3->set('DNSBL', $f3->get('security.dnsbl'));
        if (!$cache->exists($ip, $isBlacklisted)) {
            $isBlacklisted = $f3->blacklisted($ip);
            $cache->set($ip, $isBlacklisted, $f3->get('ttl.blacklist'));
        }
        if (false !== $isBlacklisted) {
            printf(_("Your ip-address '%s' is blacklisted!"), $ip);
            $f3->halt();
        }
        return true;
    }

}
