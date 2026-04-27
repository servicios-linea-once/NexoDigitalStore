<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Bienvenido a Nexo Digital Store! 🎮')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line('Tu cuenta ha sido creada exitosamente.')
            ->line('Explora miles de productos digitales: claves de juegos, gift cards, software y más.')
            ->action('Explorar la tienda', route('home'))
            ->line('Como regalo de bienvenida, recibirás **cashback en NexoTokens (NT)** en tu primera compra.')
            ->salutation('¡Que disfrutes tu experiencia! — El equipo de Nexo Digital Store');
    }
}
