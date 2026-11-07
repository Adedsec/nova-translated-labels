<?php

declare(strict_types=1);

namespace Adedsec\NovaTranslatedLabels\Tests;

use Adedsec\NovaTranslatedLabels\NovaTranslatedLabelsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [NovaCoreServiceProvider::class, NovaTranslatedLabelsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.locale', 'fa');
        $app['config']->set('database.default', 'testing');
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', static function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->foreignId('author_id')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id');
            $table->text('body');
            $table->timestamps();
        });
    }
}
