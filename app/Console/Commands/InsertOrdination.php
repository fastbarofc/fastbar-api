<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventKit;
use App\Models\Kit;
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
        $eventKitsGroupedByEventId = EventKit::whereNull('event_kit_id')
            ->orderBy('event_id')  
            ->get()
            ->groupBy('event_id');  

        foreach ($eventKitsGroupedByEventId as $eventId => $eventKits) {
            $j = 1;  

            foreach ($eventKits as $eventKit) {
                $eventKit->update(['ordination' => $j]);
                $j++;
            }

            $groupedEventKits = EventKit::where('event_id', $eventId)
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

        $kits = Kit::whereNull('kit_id')->get();
        $j = 1;

        foreach ($kits as $kit) {
            $kit->update(['ordination' => $j]);
            $j++;
            $groupedKits = Kit::where('kit_id', $kit->id)
                ->orderBy('ordination', 'asc')
                ->get()
                ->groupBy('kit_id');

            foreach ($groupedKits as $parentId => $kits) {
                $i = 1;
                foreach ($kits as $kit) {
                    $kit->update(['ordination' => $i]);
                    $i++;
                }
            }
        }
    }
}
