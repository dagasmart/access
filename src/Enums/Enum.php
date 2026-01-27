<?php

namespace DagaSmart\Access\Enums;

use DagaSmart\BizAdmin\Enums\Enum as Enums;

enum Enum
{

    /**
     * 用户类型 非学校模块
     */
    public const array USER_TYPE = [
        ['value' => 'worker', 'label' => '员工', 'color' => 'success'],
        ['value' => 'visitor', 'label' => '访客', 'color' => 'default'],
    ];

    /**
     * 用户类型 学校模块
     */
    public const array USER_TYPE_SCHOOL = [
        ['value' => 'student', 'label' => '学生', 'color' => 'info'],
        ['value' => 'patriarch', 'label' => '家长', 'color' => 'warning'],
    ];

    /**
     * 用户类型
     * @return array|array[]
     */
    public static function user_type(): array
    {
        $data = [];
        if (is_school_module()) {
            $data[] = ['value' => 'student', 'label' => '学生', 'color' => 'info'];
            $data[] = ['value' => 'patriarch', 'label' => '家长', 'color' => 'warning'];
            $data[] = ['value' => 'worker', 'label' => '教师', 'color' => 'success'];
        } else {
            $data[] = ['value' => 'worker', 'label' => '员工', 'color' => 'success'];
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
