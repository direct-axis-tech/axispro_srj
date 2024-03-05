<?php
 
namespace App\Listeners;

use App\Events\Hr\CircularIssued;
use App\Models\Entity;
use App\Models\System\User;
use App\Notifications\Hr\CircularIssuedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\Collection;

class CircularEventSubscriber
{ 
    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(CircularIssued::class, [$this, 'handleCircularIssued']);
    }

    /**
     * Get notifiables against the circular issue
     *
     * @param Circular $circular
     * @return Collection
     */
    private function getNotifiablesForCircularIssued($circular)
    {
        $assignedEntity = Entity::find($circular->entity_type_id);
        $initiatedUser = User::find($circular->created_by);

        $notifiables = (new Collection([$initiatedUser]));

        foreach (json_decode($circular->entity_id, true) as $entityId) {
            $notifiables = $notifiables->merge($assignedEntity->resolveUsers($entityId, $initiatedUser));
        }

        return $notifiables->unique()->where('inactive', '0');
    }

    /**
     * Handles the event when a new circular issued
     * 
     * @param CircularIssued $event
     * @return void
     */
    public function handleCircularIssued($event) {
        Notification::send(
            $this->getNotifiablesForCircularIssued($event->circular),
            new CircularIssuedNotification($event)
        );
    }

}