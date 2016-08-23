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
trait ControllerSecurity
{
    /**
     * Check for CSRF token, reroute if failed, otherwise generate new csrf token
     * Call this method from a controller method class to check and then set a new csrf token
     * then include $f3-get('csrf') as a hidden type in your form to be submitted
     *
     * @param string $url if csrf check fails
     * @param array $params for querystring
     * @return boolean true/false if csrf enabled
     */
    public function csrf(string $url = '@index', array $params = []): bool
    {
        $f3 = \Base::instance();
        if (empty($f3->get('security.csrf'))) {
            return false;
        }
        // redirect user if it's not a POST request
        if ('POST' !== $f3->get('VERB')) {
            $f3->reroute($url);
        }
        $csrf = $f3->get('csrf');
        if ($csrf === false) {
            $url = $this->url($url, $params);
            $f3->reroute($url);
            return;
        } else {
            $csrf = Helpers\Str::salted(Helpers\Str::random(16), Helpers\Str::random(16), Helpers\Str::random(16));
            $f3->set('csrf', $csrf);
            $f3->set('SESSION.csrf', $csrf);
            $f3->expire(0);
        }
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
