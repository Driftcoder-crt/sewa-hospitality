<?php

namespace App\Modules\Services\Jobs;

use App\Modules\Services\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyServicePublished implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Service $service;

    /**
     * Create a new job instance.
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send notification to subscribers about new published service
        // Mail::to($subscribers)->send(new ServicePublishedMail($this->service));
        
        // Log for analytics
        logger()->info('Service published notification sent', [
            'service_id' => $this->service->id,
            'service_name' => $this->service->name,
        ]);
    }
}
