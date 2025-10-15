<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Strava;

final readonly class AuthorizationRedirectDetails
{
    public function __construct(
        public string $authorizationUrl,
        public string $state,
    ) {}
}
