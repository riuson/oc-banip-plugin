<?php namespace Filipac\Banip;

use Backend\Facades\Backend;
use Cms\Classes\Layout;
use Cms\Classes\Page;
use Cms\Classes\Theme;
use Filipac\Banip\Models\Settings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use October\Rain\Support\Traits\KeyParser;
use System\Classes\PluginBase;
use System\Traits\AssetMaker;
use System\Traits\ViewMaker;

/**
 * Banip Plugin Information File
 */
class Plugin extends PluginBase
{
    use ViewMaker;
    use KeyParser;
    use AssetMaker;


    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Ban IP',
            'description' => 'Simple plugin to ban certain IPs',
            'author'      => 'Filipac',
            'icon'        => 'icon-leaf',
            'version'     => '1.0.3'
        ];
    }

    public function registerNavigation()
    {
        return [
            'banip' => [
                'label'       => 'Banned IPs',
                'url'         => Backend::url('filipac/banip/ips'),
                'icon'        => 'icon-ban',
                'permissions' => ['filipac.banip.*'],
                'order'       => 500,

                'sideMenu' => [
                    'ips' => [
                        'label'       => 'IP List',
                        'icon'        => 'icon-list',
                        'url'         => Backend::url('filipac/banip/ips'),
                        'permissions' => ['filipac.banip.*'],
                    ],
                    'layout' => [
                        'label'       => 'Settings',
                        'icon'        => 'icon-cog',
                        'url'         => Backend::url('system/settings/update/filipac/banip/settings'),
                        'permissions' => ['filipac.banip.*'],
                    ]
                ]

            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'Banned ips behaviour',
                'description' => 'Manage user based settings.',
                'category'    => 'Banned IPS',
                'icon'        => 'icon-cog',
                'class'       => 'filipac\Banip\Models\Settings',
                'order'       => 500
            ]
        ];
    }


    public function register()
    {
    }


    public function boot()
    {
        $ip = Request::ip();
        try {
            $match = $this->getMatchedBanIp($ip);
        } catch (QueryException $e) {
            \Log::info('The Filipac.Banip was not properly installed (missing table)' . $e);
            return;
        }

        if(!$this->isAdmin() && !$this->isCommandLineInterface() && $match->count() >= 1) {
            Event::listen('cms.page.beforeRenderPage', function($cl, $page) {
                $default = 'Your IP has been banned!';
                $content = Settings::get('content',$default);
                if(Str::length($content) == 0) $content = $default;
                return $content;
            });
            Event::listen('cms.page.display', function($controller, $path, $page, $content) {
                $layout = Settings::get('layout');
                $page->layout = null;
                if(!empty($layout) AND Layout::load(Theme::getActiveTheme(), $layout) !== null)
                    $page->layout = $layout;
                else
                    Settings::set('layout',null);
                $res = $controller->runPage($page);
                return Response::make($res);
            });
        }
    }


    private function isAdmin(){
        $prefix = ltrim(Config::get('cms.backendUri', 'backend'), '/');
        return Str::startsWith(Request::path(), $prefix);
    }

    private function isCommandLineInterface()
    {
        return (php_sapi_name() === 'cli');
    }

    private function getMatchedBanIp($ip)
    {
        /*
            SET @ip:='10.0.0.1';

            SELECT *, (4294967295 << (32 - CAST(`mask` AS UNSIGNED))) AS bitmask
            FROM `filipac_banip_ips`
            HAVING (CAST(INET_ATON(`address`) AS UNSIGNED) & bitmask) = (CAST(INET_ATON(@ip) AS UNSIGNED) & bitmask);
        */

        $match = \Filipac\Banip\Models\Ip::select(
            [
                '*',
                \DB::raw('(4294967295 << (32 - CAST(`mask` AS UNSIGNED))) AS bitmask')
            ])
            ->havingRaw("(CAST(INET_ATON(`address`) AS UNSIGNED) & bitmask) = (CAST(INET_ATON('$ip') AS UNSIGNED) & bitmask)")
            ->get();
        return $match;
    }
}
