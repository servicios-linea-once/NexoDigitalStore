<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Nexo Digital Store');
        $this->migrator->add('general.site_active', true);
        $this->migrator->add('general.site_logo', null);
        $this->migrator->add('general.contact_email', 'soporte@nexodigital.com');
        $this->migrator->add('general.telegram_link', 'https://t.me/nexodigital');
        $this->migrator->add('general.whatsapp_contact', '+51900000000');
        $this->migrator->add('general.social_links', [
            'facebook' => 'https://facebook.com/nexodigital',
            'instagram' => 'https://instagram.com/nexodigital',
            'twitter' => 'https://twitter.com/nexodigital',
        ]);
    }
};
