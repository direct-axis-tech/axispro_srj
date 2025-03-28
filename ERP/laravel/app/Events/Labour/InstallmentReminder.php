<?php

namespace App\Events\Labour;

use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class InstallmentReminder
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** 
     * The source for this event
     * 
     * @var CalendarEvent
     */
    public $calendarEvent;

    /** 
     * The id of the installment detail
     * 
     * @var int
     */
    public $installmentDetailId;

    /**
     * The id of contract, for which: the income is recognized
     *
     * @var int
     */
    public $contractId;

    /**
     * The reference no of the contract
     *
     * @var string
     */
    public $contractRef;

    /**
     * The from date
     *
     * @var CarbonImmutable
     */
    public $contractFrom;

    /**
     * The till date
     *
     * @var CarbonImmutable
     */
    public $contractTill;

    /**
     * The total contract amount
     *
     * @var float
     */
    public $contractAmount;

    /**
     * The category of this contract
     *
     * @var float
     */
    public $categoryId;

    /**
     * The date at which previous income was recognized
     *
     * @var CarbonImmutable
     */
    public $lastRecognizedAt;

    /**
     * The number of days for the income was recognized
     *
     * @var int
     */
    public $daysRecognized;

    /**
     * The amount(income) that is applicable for this installment
     *
     * @var float
     */
    public $amount;

    /**
     * The balance(income) that is to be recognized
     *
     * @var float
     */
    public $balance;

    /**
     * Create a new event instance.
     *
     * @param CalendarEvent $calendarEvent
     * @return void
     */
    public function __construct($calendarEvent)
    {
        $this->calendarEvent = $calendarEvent;
        $this->installmentDetailId = $calendarEvent->context['installment_detail_id'];
        $this->contractId = $calendarEvent->context['contract_id'];
        $this->contractRef = $calendarEvent->context['contract_ref'];
        $this->contractFrom = (new CarbonImmutable($calendarEvent->context['contract_from']))->midDay();
        $this->contractTill = (new CarbonImmutable($calendarEvent->context['contract_till']))->midDay();
        $this->contractAmount = $calendarEvent->context['contract_amount'];
        $this->categoryId = $calendarEvent->context['category_id'];
        $this->lastRecognizedAt = (new CarbonImmutable($calendarEvent->context['last_recognized_at']))->midDay();
        $this->daysRecognized = $calendarEvent->context['days_recognized'];
        $this->amount = $calendarEvent->context['amount'];
        $this->balance = $calendarEvent->context['balance'];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}