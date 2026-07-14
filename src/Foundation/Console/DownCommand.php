<?php

namespace Winter\Storm\Foundation\Console;

use Illuminate\Foundation\Console\DownCommand as BaseCommand;
use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Support\Facades\View;

class DownCommand extends BaseCommand
{
    /**
     * Get the payload to be placed in the "down" file.
     *
     * @return array
     */
    protected function getDownFilePayload()
    {
        return [
            'except' => $this->excludedPaths(),
            'redirect' => $this->redirectPath(),
            'retry' => $this->getRetryTime(),
            'refresh' => $this->option('refresh'),
            'secret' => $this->option('secret'),
            'status' => (int) $this->option('status'),
            'template' => $this->prerenderView(),
        ];
    }

    /**
     * Prerender the specified view so that it can be rendered even before loading Composer.
     *
     * @return string|null
     */
    protected function prerenderView()
    {
        (new RegisterErrorViewPaths)();

        $selectedView = $this->option('render');
        if ($selectedView === 'false') {
            return null;
        }

        // Check if there is a project level view to override the system one
        View::addNamespace('base', base_path());
        if (View::exists('base::maintenance')) {
            $defaultView = 'base::maintenance';
        } else {
            $defaultView = 'system::maintenance';
        }

        return view($selectedView ?? $defaultView, [
            'message' => false,
            'wentDownAt' => false,
            'retryAfter' => $this->option('retry'),
            'willBeAvailableAt' => false,
        ])->render();
    }
}
