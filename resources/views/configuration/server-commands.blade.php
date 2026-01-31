<!-- Page Heading -->
<section>
    <h1>{!! __tr('Server Commands') !!}</h1>
    <p class="text-muted">{{ __tr('Run artisan commands directly from the dashboard. Use with caution.') }}</p>

    <!-- Cache Commands -->
    <fieldset class="mb-4">
        <legend>{{ __tr('Cache Commands') }}</legend>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-broom text-warning"></i> {{ __tr('Clear All Cache') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Clears application, config, route, and view cache at once.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'optimize-clear']) }}"
                           class="btn btn-warning lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to clear all cache?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-database text-info"></i> {{ __tr('Clear Application Cache') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Clears the application cache storage.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'cache-clear']) }}"
                           class="btn btn-info lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to clear application cache?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-cog text-secondary"></i> {{ __tr('Clear Config Cache') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Clears the configuration cache file.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'config-clear']) }}"
                           class="btn btn-secondary lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to clear config cache?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-route text-primary"></i> {{ __tr('Clear Route Cache') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Clears the route cache file.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'route-clear']) }}"
                           class="btn btn-primary lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to clear route cache?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-eye text-success"></i> {{ __tr('Clear View Cache') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Clears all compiled view files.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'view-clear']) }}"
                           class="btn btn-success lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to clear view cache?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-calendar text-danger"></i> {{ __tr('Clear Event Cache') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Clears all cached events and listeners.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'event-clear']) }}"
                           class="btn btn-danger lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to clear event cache?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>

    <!-- Optimization Commands -->
    <fieldset class="mb-4">
        <legend>{{ __tr('Optimization Commands') }}</legend>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-rocket text-success"></i> {{ __tr('Optimize Application') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Cache the framework bootstrap files for faster performance.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'optimize']) }}"
                           class="btn btn-success lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to optimize the application?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-cogs text-info"></i> {{ __tr('Cache Config') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Create a cache file for faster configuration loading.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'config-cache']) }}"
                           class="btn btn-info lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to cache config?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-sitemap text-primary"></i> {{ __tr('Cache Routes') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Create a route cache file for faster route registration.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'route-cache']) }}"
                           class="btn btn-primary lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to cache routes?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>

    <!-- Queue Commands -->
    <fieldset class="mb-4">
        <legend>{{ __tr('Queue Commands') }}</legend>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-sync text-warning"></i> {{ __tr('Restart Queue Workers') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Restart queue worker daemons after their current job.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'queue-restart']) }}"
                           class="btn btn-warning lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to restart queue workers?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-trash text-danger"></i> {{ __tr('Clear Failed Jobs') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Delete all of the failed queue jobs.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'queue-flush']) }}"
                           class="btn btn-danger lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to delete all failed jobs?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>

    <!-- Storage Commands -->
    <fieldset class="mb-4">
        <legend>{{ __tr('Storage Commands') }}</legend>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-link text-info"></i> {{ __tr('Create Storage Link') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Create symbolic link from public/storage to storage/app/public.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'storage-link']) }}"
                           class="btn btn-info lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('Are you sure you want to create storage link?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>

    <!-- Database Commands -->
    <fieldset class="mb-4">
        <legend>{{ __tr('Database Commands') }} <span class="text-danger">{{ __tr('(Use with caution)') }}</span></legend>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-danger">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-database text-danger"></i> {{ __tr('Run Migrations') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Run the database migrations. Make sure you have a backup!') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'migrate']) }}"
                           class="btn btn-outline-danger lw-ajax-link-action"
                           data-method="post"
                           data-confirm="{{ __tr('WARNING: This will run database migrations. Are you absolutely sure?') }}">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-list text-secondary"></i> {{ __tr('Migration Status') }}</h5>
                        <p class="card-text text-muted">{{ __tr('Check the status of each migration.') }}</p>
                        <a href="{{ route('manage.configuration.run_command', ['command' => 'migrate-status']) }}"
                           class="btn btn-secondary lw-ajax-link-action"
                           data-method="post">
                            <i class="fa fa-play"></i> {{ __tr('Run') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>

    <!-- System Info -->
    <fieldset class="mb-4">
        <legend>{{ __tr('System Information') }}</legend>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>{{ __tr('PHP Version:') }}</strong>
                                <span class="text-info">{{ PHP_VERSION }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __tr('Laravel Version:') }}</strong>
                                <span class="text-info">{{ app()->version() }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __tr('Server:') }}</strong>
                                <span class="text-info">{{ $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __tr('Environment:') }}</strong>
                                <span class="badge badge-{{ app()->environment('production') ? 'danger' : 'success' }}">
                                    {{ app()->environment() }}
                                </span>
                            </div>
                        </div>
                        <hr>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <strong>{{ __tr('Cache Driver:') }}</strong>
                                <span class="text-info">{{ config('cache.default') }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __tr('Queue Driver:') }}</strong>
                                <span class="text-info">{{ config('queue.default') }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __tr('Session Driver:') }}</strong>
                                <span class="text-info">{{ config('session.driver') }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __tr('Broadcast Driver:') }}</strong>
                                <span class="text-info">{{ config('broadcasting.default') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
</section>
