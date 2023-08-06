<?php

namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    // public function register(): void
    // {
    //     //
    // }

    /**
     * Bootstrap services.
     */
    public function register()
    {
        \Illuminate\Database\Query\Builder::macro('toRawSql', function (Builder $build) {
            return array_reduce($build->getBindings(), function ($sql, $binding) {
                return preg_replace('/\?/', is_numeric($binding) ? $binding : "'" . $binding . "'", $sql, 1);
            }, $build->toSql());
        });

        \Illuminate\Database\Eloquent\Builder::macro('toRawSql', function (EloquentBuilder $build) {
            return ($build->getQuery()->toRawSql());
        });
    }
}
