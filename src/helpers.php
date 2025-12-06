<?php

// 自定义辅助函数

if (!function_exists('test')) {
    /**
     * @return bool
     */
    function test(): bool
    {
        return true;
    }
}


if (!function_exists('app_module_all')) {
    /**
     * 获取所有应用模块名
     */
    function app_module_all(): array
    {
        $module_all = admin_module_all();
        $current_module = admin_current_module();
        return array_filter($module_all, function($item) use ($current_module) {
            return $current_module ? $item == $current_module : $item;
        });
    }
}


if (!function_exists('is_school_module')) {
    /**
     * 是否学校模块
     */
    function is_school_module(): bool
    {
        return admin_current_module() == 'school';
    }
}
