<?php

namespace YourVendor\ManagedJobs\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use YourVendor\ManagedJobs\Contracts\JobPayload;
use YourVendor\ManagedJobs\Jobs\BaseJob;
use YourVendor\ManagedJobs\Models\ManagedJob;
use YourVendor\ManagedJobs\Support\JobRunner;
use YourVendor\ManagedJobs\Tests\TestCase;

class JobRunnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_owners', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        // Neutralize the queue dispatch that JobRunner fires after commit —
        // we only assert on the persisted ManagedJob record.
        Bus::fake();
    }

    public function test_dispatch_records_the_polymorphic_owner(): void
    {
        $owner = TestOwner::create();

        $job = JobRunner::dispatch(
            job:     ExampleJob::class,
            payload: new ArrayPayload(['foo' => 'bar']),
            owner:   $owner,
        );

        $this->assertSame($owner->getMorphClass(), $job->owner_type);
        $this->assertSame((string) $owner->getKey(), (string) $job->owner_id);
        $this->assertNull($job->triggered_by_type);
        $this->assertTrue($job->owner->is($owner));
    }

    public function test_dispatch_records_the_triggering_actor(): void
    {
        $owner = TestOwner::create();
        $admin = TestOwner::create();

        $job = JobRunner::dispatch(
            job:         ExampleJob::class,
            payload:     new ArrayPayload([]),
            owner:       $owner,
            triggeredBy: $admin,
        );

        $this->assertSame($admin->getMorphClass(), $job->triggered_by_type);
        $this->assertSame((string) $admin->getKey(), (string) $job->triggered_by_id);
    }

    public function test_owned_by_scope_matches_only_the_owner(): void
    {
        $owner = TestOwner::create();
        $other = TestOwner::create();

        $mine = JobRunner::dispatch(ExampleJob::class, new ArrayPayload([]), $owner);
        JobRunner::dispatch(ExampleJob::class, new ArrayPayload([]), $other);

        $result = ManagedJob::ownedBy($owner)->get();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($mine));
    }
}

class TestOwner extends Model
{
    protected $table = 'test_owners';

    protected $guarded = [];
}

class ArrayPayload implements JobPayload
{
    public function __construct(private array $data) {}

    public function toArray(): array
    {
        return $this->data;
    }
}

class ExampleJob extends BaseJob
{
    public function handle(): void
    {
        // no-op — the queue dispatch is faked in the test
    }
}
