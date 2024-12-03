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

        $paths = [
            base_path() . '/modules' => '/*/models',
            plugins_path() => '/*/*/models',
        ];

        return File::findModels($paths)
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
