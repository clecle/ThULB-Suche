<?php

namespace ThULB\Role\PermissionProvider;
use VuFind\Role\PermissionProvider\IpRange as OriginalIpRange;

class IpRange extends OriginalIpRange
{
    /**
     * Return an array of roles which may be granted the permission based on
     * the options.
     *
     * @param mixed $options Options provided from configuration.
     *
     * @return array
     */
    public function getPermissions($options)
    {
        $server = $this->request->getServer();
        $ip = $server->get('HTTP_X_FORWARDED_FOR') ?: $server->get('REMOTE_ADDR');

        if ($this->ipAddressUtils->isInRange($ip, (array)$options)) {
            // Match? Grant to all users (guest or logged in).
            return ['guest', 'loggedin'];
        }

        //  No match? No permissions.
        return [];
    }
}
