<?php

namespace Tests\Feature;

use App\Models\Receivable;
use App\Services\DataFixSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DataFixSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private const NAME = 'test_data_fix_snapshot';

    protected function tearDown(): void
    {
        File::delete(File::glob(storage_path('app/data-fixes/' . self::NAME . '*.json')));
        parent::tearDown();
    }

    public function test_reverting_restores_the_values_saved_before_the_fix(): void
    {
        $trashed = Receivable::factory()->create();
        $trashed->delete();
        $active = Receivable::factory()->create();
        $deletedAt = $trashed->fresh()->deleted_at->toDateTimeString();

        $count = $this->snapshots()->apply(self::NAME, [
            ['table' => 'receivables', 'id' => $trashed->id, 'set' => ['deleted_at' => null]],
            ['table' => 'receivables', 'id' => $active->id, 'set' => ['deleted_at' => '2026-11-27 12:00:00']],
            ['table' => 'receivables', 'id' => 999999, 'set' => ['deleted_at' => null]],
        ]);

        $this->assertSame(2, $count);
        $this->assertNull($trashed->fresh()->deleted_at);
        $this->assertTrue($active->fresh()->trashed());

        $this->artisan('data-fix:revert', ['name' => self::NAME])->assertSuccessful();

        $this->assertSame($deletedAt, $trashed->fresh()->deleted_at->toDateTimeString());
        $this->assertNull($active->fresh()->deleted_at);
        $this->assertFileDoesNotExist($this->snapshots()->path(self::NAME));
    }

    public function test_rows_changed_after_the_fix_are_only_reverted_with_force(): void
    {
        $receivable = Receivable::factory()->create();

        $this->snapshots()->apply(self::NAME, [
            ['table' => 'receivables', 'id' => $receivable->id, 'set' => ['description' => 'Corrigida']],
        ]);
        $originalDescription = $receivable->description;
        $receivable->fresh()->update(['description' => 'Editada pelo usuário']);

        $this->artisan('data-fix:revert', ['name' => self::NAME])->assertFailed();
        $this->assertSame('Editada pelo usuário', $receivable->fresh()->description);
        $this->assertFileExists($this->snapshots()->path(self::NAME));

        $this->artisan('data-fix:revert', ['name' => self::NAME, '--force' => true])->assertSuccessful();
        $this->assertSame($originalDescription, $receivable->fresh()->description);
    }

    public function test_nothing_is_written_when_there_is_nothing_to_change(): void
    {
        $this->assertSame(0, $this->snapshots()->apply(self::NAME, [
            ['table' => 'receivables', 'id' => 999999, 'set' => ['deleted_at' => null]],
        ]));

        $this->assertFileDoesNotExist($this->snapshots()->path(self::NAME));
    }

    private function snapshots(): DataFixSnapshot
    {
        return app(DataFixSnapshot::class);
    }
}
