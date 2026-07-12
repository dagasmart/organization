<?php

// 自定义辅助函数

use DagaSmart\Organization\OrganizationServiceProvider;

if (! function_exists('test')) {
    function test(): bool
    {
        return true;
    }
}

if (! function_exists('extend_trans')) {
    /**
     * 语言包
     */
    function extend_trans($key): array|string|null
    {
        return OrganizationServiceProvider::trans($key) ?? null;
    }
}

if (! function_exists('app_module_all')) {
    /**
     * 获取所有应用模块名
     */
    function app_module_all(): array
    {
        $module_all = admin_module_all();
        $current_module = admin_current_module();

        return array_filter($module_all, function ($item) use ($current_module) {
            return $current_module ? $item == $current_module : $item;
        });
    }
}

if (! function_exists('is_school_module')) {
    /**
     * 是否学校模块
     */
    function is_school_module(): bool
    {
        return admin_current_module() == 'school';
    }
    /**
     * 模块下机构别名
     */
    function module_enterprise_alias(): string
    {
        return is_school_module() ? '学校' : '机构';
    }
}
