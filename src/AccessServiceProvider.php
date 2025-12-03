<?php
declare(strict_types=1);
namespace DagaSmart\Access;

use Exception;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\TextControl;
use DagaSmart\BizAdmin\Extend\ServiceProvider;


class AccessServiceProvider extends ServiceProvider
{
	protected $menu = [
        [
            'parent' => NULL,
            'title' => '门禁管家',
            'url' => '/biz/access',
            'url_type' => 1,
            'icon' => 'material-symbols-light:doorbell-chime-outline',
        ],
        [
            'parent' => '门禁管家',
            'title' => '门禁设备',
            'url' => '/biz/access/device',
            'url_type' => 1,
            'icon' => 'cbi:arlo-essential-indoor',
        ],
        [
            'parent' => '门禁管家',
            'title' => '进出记录',
            'url' => '/biz/access/log',
            'url_type' => 1,
            'icon' => 'carbon:catalog-publish',
        ],
        [
            'parent' => '门禁管家',
            'title' => '数据分发',
            'url' => '/biz/access/dispatch',
            'url_type' => 1,
            'icon' => 'fluent-mdl2:distribute-down',
        ],
        [
            'parent' => '门禁管家',
            'title' => '图表分析',
            'url' => '/biz/access/stat',
            'url_type' => 1,
            'icon' => 'mynaui:chart-bar',
        ]
    ];

    /**
     * @return void
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
