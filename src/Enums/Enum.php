<?php

namespace DagaSmart\Access\Enums;

use DagaSmart\BizAdmin\Enums\Enum as Enums;
use DagaSmart\Organization\Enums\Enum as OrganizationEnum;

enum Enum
{
    /**
     * 权限码
     */
    const array PERMISSION_CODE = [
        ['value' => 0, 'label' => '通用码'],
        ['value' => 1, 'label' => '一类码'],
        ['value' => 2, 'label' => '二类码'],
        ['value' => 3, 'label' => '三类码'],
        ['value' => 4, 'label' => '四类码'],
        ['value' => 5, 'label' => '五类码'],
    ];

    /**
     * 开锁模式
     *
     * @return array|array[]
     */
    public static function open_type(): array
    {
        return [
            ['value' => 'face', 'label' => '人脸解锁', 'color' => 'info'],
            ['value' => 'finger', 'label' => '指纹解锁', 'color' => 'warning'],
            ['value' => 'card', 'label' => '开锁卡片', 'color' => 'success'],
        ];
    }

    /**
     * 用户类型
     *
     * @param array $hidden
     * @param array $disabled
     * @return array
     */
    public static function user_type(array $hidden = [], array $disabled = []): array
    {
        $data = [];

        if (is_school_module()) {
            $data[] = [
                'value' => 'student',
                'label' => '学生',
                'color' => 'info',
                'disabled' => in_array('student', $disabled),
                'hidden' => in_array('student', $hidden),
            ];
            $data[] = [
                'value' => 'patriarch',
                'label' => '家长',
                'color' => 'warning',
                'disabled' => in_array('patriarch', $disabled),
                'hidden' => in_array('patriarch', $hidden),
            ];
            $data[] = [
                'value' => 'worker',
                'label' => '教师',
                'color' => 'success',
                'disabled' => in_array('worker', $disabled),
                'hidden' => in_array('worker', $hidden),
            ];
        } else {
            $data[] = [
                'value' => 'worker',
                'label' => '员工',
                'color' => 'success',
                'disabled' => in_array('worker', $disabled),
                'hidden' => in_array('worker', $hidden),
            ];
        }
        $data[] = [
            'value' => 'visitor',
            'label' => '访客',
            'color' => 'default',
            'disabled' => in_array('visitor', $disabled),
            'hidden' => in_array('visitor', $hidden),
        ];

        return $data;
    }

    /**
     * 住校类型
     *
     * @return array|array[]
     */
    public static function board_type(): array
    {
        return [
            ['value' => 0, 'label' => '住校'],
            ['value' => 1, 'label' => '走读'],
        ];
    }

    /**
     * 分发状态
     */
    public static function dispatch_state(): array
    {
        return [
            ['label' => '待分发', 'value' => 0, 'icon' => 'schedule'],
            ['label' => '成功', 'value' => 1, 'icon' => 'success'],
            ['label' => '失败', 'value' => 2, 'icon' => 'fail'],
            ['label' => '排队中', 'value' => -1, 'icon' => 'rolling'],
            ['label' => '异常', 'value' => -2, 'icon' => 'warning'],
        ];
    }

    /**
     * 性别
     */
    public static function sex(): array
    {
        return Enums::sex();
    }

    /**
     * 星期列表
     *
     * @param  int|null  $type  1取星期值0123456，2取星期值1234567
     */
    public static function weeks(?int $type = null): array
    {
        return Enums::weeks($type);
    }

    /**
     * 有效状态
     */
    public static function workerActive(): array
    {
        return [
            OrganizationEnum::WORKER_STATE_NORMAL,
            OrganizationEnum::WORKER_STATE_LEAVE,
            OrganizationEnum::WORKER_STATE_ACTING,
            OrganizationEnum::WORKER_STATE_SUSPEND,
        ];

    }

}
