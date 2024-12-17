<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventKit;
use Illuminate\Console\Command;

class InsertOrdination extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:insert-ordination';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert ordination in event_kits table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $events = Event::whereHas('eventKits')->get();

        foreach ($events as $event) {
            $groupedEventKits = EventKit::where('event_id', $event->id)
                ->orderBy('event_kit_id', 'asc')
                ->orderBy('ordination', 'asc')
                ->get()
                ->groupBy('event_kit_id');

            foreach ($groupedEventKits as $parentId => $eventKits) {
                $i = 1;
                foreach ($eventKits as $eventKit) {
                    $eventKit->update(['ordination' => $i]);
                    $i++;
                }
            }
        }
    }
}
