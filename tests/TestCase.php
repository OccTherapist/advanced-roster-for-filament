<?php

namespace OccTherapist\AdvancedRosterForFilament\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use OccTherapist\AdvancedRosterForFilament\AdvancedRosterForFilamentServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function tearDown(): void
    {
        \Mockery::close();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            AdvancedRosterForFilamentServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('advanced-roster-for-filament', require __DIR__.'/../config/advanced-roster-for-filament.php');
        $app['config']->set('advanced-roster-for-filament.assignee_model', TestAssignee::class);
        $app['config']->set('auth.providers.users.model', TestUser::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Schema::create('test_assignees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }
}
