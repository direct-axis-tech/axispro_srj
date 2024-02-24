<?php

namespace App\Notifications\Hr;

use App\Models\System\User;
use App\Traits\BroadcastableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CircularIssuedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;
    use BroadcastableNotification;

    /** 
     * The circular event that triggered this notification
     * 
     * @var CircularIssued
     */
    public $event;

    /**
     * Create a new notification instance.
     *
     * @param CircularIssued $event
     * @return void
     */
    public function __construct($event)
    {
        $this->event = $event;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  User $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return $notifiable->notificationsVia($this);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  User  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  User  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $circular = $this->event->circular;
        
        $data =  [
            'title'    => 'New Circular Issued',
            'reference'  => $circular->reference,
            'description'   => $circular->memo
        ];
        $data['actions'][] = [
            "title" => "View Circular",
            "class" => "success",
            "url" => "/v3/hr/circular-issued?".http_build_query([
                "reference" => $circular->reference
            ])
        ];
        return $data;
    }

}
