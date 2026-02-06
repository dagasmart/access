<?php

namespace DagaSmart\Access\Enums;

use DagaSmart\BizAdmin\Enums\Enum as Enums;

enum Enum
{

    /**
     * 权限码
     */
    const array PERMISSION_CODE = [
        ['value' => 0, 'label' => '初级'],
        ['value' => 1, 'label' => '一级'],
        ['value' => 2, 'label' => '二级'],
        ['value' => 3, 'label' => '三级'],
        ['value' => 4, 'label' => '四级'],
        ];

    /**
     * 开锁模式
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
     * @return array|array[]
     */
    public static function user_type(): array
    {
        $data = [];
        if (is_school_module()) {
            $data[] = ['value' => 'student', 'label' => '学生', 'color' => 'info', 'disabled' => true];
            $data[] = ['value' => 'patriarch', 'label' => '家长', 'color' => 'warning', 'disabled' => true];
            $data[] = ['value' => 'worker', 'label' => '教师', 'color' => 'success', 'disabled' => true];
        } else {
            $data[] = ['value' => 'worker', 'label' => '员工', 'color' => 'success', 'disabled' => true];
        }
        $data[] = ['value' => 'visitor', 'label' => '访客', 'color' => 'default'];
        return $data;
    }

    /**
     * 性别
     * @return array
     */
    public static function sex(): array
    {
        return Enums::sex();
    }


}
