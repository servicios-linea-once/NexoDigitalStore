<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_favicon', null);
        $this->migrator->add('general.seo_title', 'Nexo Digital Store | Las mejores claves y licencias');
        $this->migrator->add('general.seo_description', 'Tu tienda de confianza para comprar software, juegos y tarjetas de regalo al mejor precio.');
        $this->migrator->add('general.seo_keywords', ['software', 'claves', 'licencias', 'juegos', 'gifcards']);
    }
};
