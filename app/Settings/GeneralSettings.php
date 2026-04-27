<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;
    public bool $site_active;
    public ?string $site_logo;
    public ?string $site_favicon;
    public ?string $contact_email;
    public ?string $telegram_link;
    public ?string $whatsapp_contact;
    public array $social_links;
    public ?string $seo_title;
    public ?string $seo_description;
    public array $seo_keywords;

    public static function group(): string
    {
        return 'general';
    }
}
