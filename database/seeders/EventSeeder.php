<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\EventStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Material;
use App\Models\Event;
use App\Models\ExtraSale;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    private array $materialUsage = [];

    public function run(): void
    {
        $events = Event::factory(200)->create();

        $legalRepresentatives = Client::where('type', Client::TYPE_FISICAL)->get();
        $materials = Material::all();

        $inactiveStatusNames = ['Concluído', 'Descartado', 'Cancelado', 'Fechou com o concorrente'];
        $inactiveStatusIds = EventStatus::whereIn('name', $inactiveStatusNames)->pluck('id')->toArray();

        foreach ($materials as $material) {
            $this->materialUsage[$material->id] = 0;
        }

        foreach ($events as $event) {
            $legalRepresentativesToEvent = $legalRepresentatives->random(3)->pluck('id');
            $materialsToEvent = $materials->random(3)->pluck('id');
            $event->clients()->attach($legalRepresentativesToEvent);
            ExtraSale::factory(3)->state(['event_id' => $event->id])->create();

            $isActiveEvent = !in_array($event->event_status_id, $inactiveStatusIds);

            foreach ($materials as $material) {
                if ($materialsToEvent->contains($material->id)) {
                    if ($isActiveEvent) {
                        $availableQuantity = $material->quantity - ($this->materialUsage[$material->id] ?? 0);

                        if ($availableQuantity > 0) {
                            $maxQuantity = min(10, $availableQuantity);
                            $quantity = rand(1, $maxQuantity);
                            $event->materials()->attach($material, ['quantity' => $quantity, 'selected' => 1]);

                            $this->materialUsage[$material->id] = ($this->materialUsage[$material->id] ?? 0) + $quantity;
                        } else {
                            $event->materials()->attach($material, ['quantity' => 0, 'selected' => 0]);
                        }
                    } else {
                        $quantity = rand(1, 10);
                        $event->materials()->attach($material, ['quantity' => $quantity, 'selected' => 1]);
                    }
                    continue;
                }
                $event->materials()->attach($material, ['quantity' => 0, 'selected' => 0]);
            }
        }
    }
}
