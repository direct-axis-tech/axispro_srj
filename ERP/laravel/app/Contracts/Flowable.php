<?php

namespace App\Contracts;

use App\Models\TaskRecord;

interface Flowable
{
    /**
     * The callback function to be called after being completed during the flow
     *
     * @param  \App\Models\TaskRecord  $taskRecord
     * @return void
     */
    public static function resolve(TaskRecord $taskRecord);

    /**
     * The callback function to be called after being rejected during the flow
     *
     * @param  \App\Models\TaskRecord  $taskRecord
     * @return void
     */
    public static function reject(TaskRecord $taskRecord);

    /**
     * The callback function to be called after the flow was cancelled
     *
     * @param  \App\Models\TaskRecord  $taskRecord
     * @return void
     */
    public static function cancel(TaskRecord $taskRecord);

    /**
     * Returns the relevant data to be shown to public
     *
     * @param  \App\Models\TaskRecord  $taskRecord
     * @return array
     */
    public static function getDataForDisplay(TaskRecord $taskRecord): array;

     /**
     * Returns the view to be shown to public
     *
     * @param  \App\Models\TaskRecord  $taskRecord
     * @return \Illuminate\View\View
     */
    public static function view(TaskRecord $taskRecord): \Illuminate\View\View;
}