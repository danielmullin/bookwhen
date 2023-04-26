<?php

declare(strict_types=1);

namespace InShore\Bookwhen\Domain;

final class ClassPass
{
    /**
     *
     */
    public function __construct(
        public readonly null | string $details,
        public readonly string $id,
        public readonly int | null $numberAvailable,
        public readonly string $title,
        public readonly int | null $usageAllowance,
        public readonly string $usageType,
        public readonly int | null $useRestrictedForDays,
    ) {
    }
}
