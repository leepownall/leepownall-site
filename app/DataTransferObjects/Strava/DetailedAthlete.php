<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Strava;

final readonly class DetailedAthlete
{
    public function __construct(
        public int $id,
        public ?string $username,
        public string $firstName,
        public string $lastName,
        public string $country,
        public string $profileMedium,
    ) {}

    public static function fromResponse(array $response): static
    {
        return new self(
            id: $response['id'],
            username: $response['username'],
            firstName: $response['firstname'],
            lastName: $response['lastname'],
            country: $response['country'],
            profileMedium: $response['profile_medium'],
        );
    }
}
