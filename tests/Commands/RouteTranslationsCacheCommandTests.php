<?php

namespace RichanFongdasen\I18n\Tests\Commands;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use RichanFongdasen\I18n\I18nService;
use RichanFongdasen\I18n\Tests\WithRouteTestCase;

class RouteTranslationsCacheCommandTests extends WithRouteTestCase
{
    use RouteTranslationTestTrait;

    protected $laravel;

    /**
     * Setup the test environment
     *
     * @return void
     */
    public function setUp() :void
    {
        parent::setUp();
 
        \Illuminate\Support\Facades\File::copy(
            realpath(__DIR__.'/../Supports/app.php'),
            $this->app->bootstrapPath().'/app.php'
        );
    }

    /** @test */
    public function it_will_get_error_because_no_route_exists()
    {
        static::$useRoute = false;
        $this->artisan('route:trans:cache')
            ->expectsOutput('Your application doesn\'t have any routes.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_will_generate_cache_files()
    {
        $this->laravel = $this->app;
        static::$useRoute = true;
        $this->doCache();
        static::$useRoute = false;
    }

    /** @test */
    public function it_can_load_routes_from_cache_file()
    {
        $this->laravel = $this->app;
        static::$useRoute = true;
        $this->doCache();

        $allSupportedLocale = array_keys(\I18n::getLocale()->toArray());
        array_push($allSupportedLocale, 'jp', null);

        foreach ($allSupportedLocale as $locale) {
            $this->request = \Mockery::mock(Request::class);

            $this->request->shouldReceive('segment')
                ->with(1)
                ->andReturn($locale);

            $this->loadCachedRoutes();
            $routes = app('router')->getRoutes()->getRoutes();
            $availableRoutes = Arr::pluck($routes, 'uri');
            if (!$locale || $locale = 'jp') {
                $locale = $this->getI18nService()->routePrefix();
            }
            $this->assertEquals($availableRoutes, [$locale.'/foo', $locale.'/bar']);
        }
        static::$useRoute = false;
    }

    protected function doCache()
    {
        $this->artisan('route:trans:cache')
            ->expectsOutput('Routes cached successfully for all locales!')
            ->assertExitCode(0);
        $this->assertTrueLocaleCache();
    }
}
