<?php

declare(strict_types=1);

namespace Neucore\Storage;

/**
 * A variable for the volatile storage.
 *
 * Depending on the storage, these are saved as a SystemVariable or in APCu (preferred).
 *
 * @see \Neucore\Entity\SystemVariable
 */
class Variables
{
    /**
     * Time, remain and reset from X-Esi-Error-Limit-* HTTP headers.
     */
    public const ESI_ERROR_LIMIT = 'esi_error_limit';

    /**
     * Time to wait when hitting a 429 response (unix timestamp).
     */
    public const ESI_RATE_LIMIT = 'esi_rate_limit';

    /**
     * Value: 1 or 0
     */
    public const ESI_THROTTLED = 'esi_throttled';

    /**
     * Prefix for the application API rate limit.
     */
    public const RATE_LIMIT_APP = 'rate_limit_app';

    /**
     * Prefix for the IP-based rate limit.
     */
    public const RATE_LIMIT_IP = 'rate_limit_ip';
}
