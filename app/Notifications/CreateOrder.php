<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreateOrder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $orderPrice;
    protected $userName;
    protected $orderId;

    public function __construct($orderPrice , $userName , $orderId)
    {
        $this->orderPrice = $orderPrice;
        $this->userName = $userName;
        $this->orderId = $orderId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail' , 'database'];
    }



    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم انشاء طلبك بنجاح ')
            ->greeting('مرحبا ' . $this->userName)
            ->line('تم انشاء طلبك بنجاح برقم  ' . $this->orderId)
            // ->action('Notification Action', url('/'))
            ->line('سنقوم بمراجعة طلبك قريبا واعلامك باي تحديثات');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [

            'order_id' => $this->orderId,
            'order_price' => $this->orderPrice,
            'user_name' => $this->userName,
            'message' => 'تم انشاء طلبك بنجاح'

        ];
    }
}
