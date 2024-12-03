<?php

namespace Winter\Storm\Database\Console;

use Illuminate\Database\Console\PruneCommand as BasePruneCommand;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Prunable;
use Winter\Storm\Support\Facades\File;

class PruneCommand extends BasePruneCommand
{
    /**
     * Determine the models that should be pruned.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function models()
    {
        if (! empty($models = $this->option('model'))) {
            return collect($models)->filter(function ($model) {
                return class_exists($model);
            })->values();
        }

        $except = $this->option('except');

        if (! empty($models) && ! empty($except)) {
            throw new InvalidArgumentException('The --models and --except options cannot be combined.');
        }

        return $this->findModels()
            ->when(! empty($except), function ($models) use ($except) {
                return $models->reject(function ($model) use ($except) {
                    return in_array($model, $except);
                });
            })->filter(function ($model) {
                return $this->isPrunable($model);
            })->filter(function ($model) {
                return class_exists($model);
            })->values();
    }

    protected function findModels()
    {
        /**
         * @event system.console.model.prune.findModels
         * Give the opportunity to return a collection of Models to prune.
         *
         * Example usage:
         *
         *     Event::listen('system.console.model.prune.findModels', function () {
         *         return collect(['example model' => '\System\Models\File']);
         *     });
         *
         */
        $models = \Event::fire('system.console.model.prune.findModels', [$this], true);
        if ($models instanceof \Illuminate\Support\Collection) {
            return $models;
        }

        $paths = [
            base_path() . '/modules' => '/*/models',
            plugins_path() => '/*/*/models',
        ];
        return File::findModels($paths);
    }

    /**
     * Determine if the given model class is prunable.
     *
     * @param  string  $model
     * @return bool
     */
    protected function isPrunable($model)
    {
        try {
            $uses = class_uses_recursive($model);
        } catch (\Exception $e) {
            return false;
        }

        return in_array(Prunable::class, $uses) || in_array(MassPrunable::class, $uses);
    }
}
