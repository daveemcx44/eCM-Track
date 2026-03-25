<?php

namespace Tests\Unit\Models;

use App\Enums\ResourceRating;
use App\Models\Resource;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_can_be_created_with_factory(): void
    {
        $resource = Resource::factory()->create();

        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'survey_name' => $resource->survey_name,
        ]);
    }

    public function test_at_home_is_cast_to_resource_rating_enum(): void
    {
        $resource = Resource::factory()->create(['at_home' => ResourceRating::Better]);

        $resource->refresh();

        $this->assertInstanceOf(ResourceRating::class, $resource->at_home);
        $this->assertEquals(ResourceRating::Better, $resource->at_home);
    }

    public function test_at_work_is_cast_to_resource_rating_enum(): void
    {
        $resource = Resource::factory()->create(['at_work' => ResourceRating::Same]);

        $resource->refresh();

        $this->assertInstanceOf(ResourceRating::class, $resource->at_work);
        $this->assertEquals(ResourceRating::Same, $resource->at_work);
    }

    public function test_at_play_is_cast_to_resource_rating_enum(): void
    {
        $resource = Resource::factory()->create(['at_play' => ResourceRating::Worse]);

        $resource->refresh();

        $this->assertInstanceOf(ResourceRating::class, $resource->at_play);
        $this->assertEquals(ResourceRating::Worse, $resource->at_play);
    }

    public function test_resource_belongs_to_task(): void
    {
        $task = Task::factory()->started()->create();
        $resource = Resource::factory()->create(['task_id' => $task->id]);

        $this->assertInstanceOf(Task::class, $resource->task);
        $this->assertEquals($task->id, $resource->task->id);
    }

    public function test_resource_update_throws_logic_exception(): void
    {
        $resource = Resource::factory()->create();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Resources are immutable and cannot be updated.');

        $resource->update(['survey_name' => 'Updated Name']);
    }

    public function test_resource_delete_throws_logic_exception(): void
    {
        $resource = Resource::factory()->create();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Resources are immutable and cannot be deleted.');

        $resource->delete();
    }
}
