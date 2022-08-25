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
    public function getPermissions($options) : array
    {
        $server = $this->request->getServer();
        $ip = $server->get('HTTP_X_FORWARDED_FOR') ?: $server->get('REMOTE_ADDR');

        // Check if a ip is not in range
        $allIps = explode(',', $ip);
        foreach($allIps as $singleIp) {
            if(!$this->ipAddressUtils->isInRange($singleIp, (array) $options)) {
                return [];
            }
        }

        // All ips in range? Grant to all users (guest or logged in).
        return ['guest', 'loggedin'];
    }
}
