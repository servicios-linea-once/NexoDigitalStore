<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $username,
        public ?string $avatar_url,
        public ?string $role,
        public bool $is_active,
        public ?string $created_at,
    ) {}

    public static function fromModel(\App\Models\User $user): self
    {
        return new self(
            id: (string) $user->id,
            name: $user->name,
            email: $user->email,
            username: $user->username,
            avatar_url: $user->avatar_url,
            role: $user->role,
            is_active: (bool) $user->is_active,
            created_at: $user->created_at?->toDateTimeString(),
        );
    }
}
