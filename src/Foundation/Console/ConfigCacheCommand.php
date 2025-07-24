<?php

namespace Winter\Storm\Foundation\Console;

use Illuminate\Foundation\Console\ConfigCacheCommand as BaseCommand;

class ConfigCacheCommand extends BaseCommand
{
    public function handle()
    {
        $this->components->warn('Caching configuration files is not supported in Winter CMS. See https://github.com/wintercms/winter/issues/1297#issuecomment-2624578966');
    }
}
