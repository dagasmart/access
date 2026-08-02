<?php

declare(strict_types=1);

namespace DagaSmart\Access;

use DagaSmart\BizAdmin\Extend\ServiceProvider;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\TextControl;
use Exception;

class AccessServiceProvider extends ServiceProvider
{
    protected $menu = [
        [
            'parent' => null,
            'title' => '门禁管家',
            'url' => '/extension/access',
            'url_type' => 1,
            'icon' => 'material-symbols-light:doorbell-chime-outline',
        ],
        [
            'parent' => '门禁管家',
            'title' => '门禁设备',
            'url' => '/extension/access/device',
            'url_type' => 1,
            'icon' => 'cbi:arlo-essential-indoor',
        ],
        [
            'parent' => '门禁管家',
            'title' => '权限管理',
            'url' => '/extension/access/permission',
            'url_type' => 1,
            'icon' => 'icon-park-outline:permissions',
        ],
        [
            'parent' => '门禁管家',
            'title' => '用户管理',
            'url' => '/extension/access/user',
            'url_type' => 1,
            'icon' => 'lucide:user-cog',
        ],
        [
            'parent' => '门禁管家',
            'title' => '数据分发',
            'url' => '/extension/access/dispatch',
            'url_type' => 1,
            'icon' => 'fluent-mdl2:distribute-down',
        ],
        [
            'parent' => '门禁管家',
            'title' => '进出记录',
            'url' => '/extension/access/log',
            'url_type' => 1,
            'icon' => 'carbon:catalog-publish',
        ],
        [
            'parent' => '门禁管家',
            'title' => '图表分析',
            'url' => '/extension/access/stat',
            'url_type' => 1,
            'icon' => 'mynaui:chart-bar',
        ],
    ];

    protected $auth = [
        // 设备
        ['name' => '新增', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.device', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.device', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.device', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.device', 'abbr' => 'showed', 'custom_order' => 4],
        ['name' => '筛选', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.device', 'abbr' => 'search', 'custom_order' => 5],
        ['name' => '设置', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.device', 'abbr' => 'config', 'custom_order' => 6],
        // 权限
        ['name' => '新增', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.permission', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.permission', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.permission', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.permission', 'abbr' => 'showed', 'custom_order' => 4],
        ['name' => '筛选', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.permission', 'abbr' => 'search', 'custom_order' => 5],
        // 用户
        ['name' => '新增', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.user', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.user', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.user', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.user', 'abbr' => 'showed', 'custom_order' => 4],
        ['name' => '筛选', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.user', 'abbr' => 'search', 'custom_order' => 5],
        ['name' => '下发至设备', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.user', 'abbr' => 'dispatch', 'custom_order' => 6],
        // 分发
        ['name' => '新增', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.dispatch', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.dispatch', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.dispatch', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.dispatch', 'abbr' => 'showed', 'custom_order' => 4],
        ['name' => '筛选', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.dispatch', 'abbr' => 'search', 'custom_order' => 5],
        // 记录
        ['name' => '新增', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.log', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.log', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.log', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.log', 'abbr' => 'showed', 'custom_order' => 4],
        ['name' => '筛选', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.log', 'abbr' => 'search', 'custom_order' => 5],
        // 分析
        ['name' => '新增', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.stat', 'abbr' => 'create', 'custom_order' => 1],
        ['name' => '删除', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.stat', 'abbr' => 'delete', 'custom_order' => 2],
        ['name' => '编辑', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.stat', 'abbr' => 'update', 'custom_order' => 3],
        ['name' => '查看', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.stat', 'abbr' => 'showed', 'custom_order' => 4],
        ['name' => '筛选', 'namespace' => 'dagasmart.access',  'code' => 'extension.access.stat', 'abbr' => 'search', 'custom_order' => 5],
    ];


    /**
     * @throws Exception
     */
    public function register(): void
    {
        parent::register();

        /**加载路由**/
        parent::registerRoutes(__DIR__.'/Http/routes.php');
        /**加载语言包**/
        if ($lang = parent::getLangPath()) {
            $this->loadTranslationsFrom($lang, $this->getCode());
        }

    }

    public function settingForm(): ?Form
    {
        return $this->baseSettingForm()->body([
            TextControl::make()->name('value')->label('Value')->required(),
        ]);
    }
}
