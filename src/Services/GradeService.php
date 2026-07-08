<?php

namespace DagaSmart\Organization\Services;

use DagaSmart\Organization\Models\Enterprise;
use DagaSmart\Organization\Models\Grade;
use Illuminate\Database\Eloquent\Builder;

/**
 * 基础-年级服务类
 *
 * @method Grade getModel()
 * @method Grade|Builder query()
 */
class GradeService extends AdminService
{
    protected string $modelName = Grade::class;

    /**
     * 机构年级列表
     */
    public function EnterpriseGrade(int $school_id = 0): array
    {
        // 1. 如果未传入有效的机构ID，直接返回空数组
        if (! $school_id) {
            return [];
        }

        // 2. 获取机构配置的年级ID字符串并转换为数组
        $enterprise_grade = Enterprise::query()->where('id', $school_id)->value('grade_id');
        // 过滤掉空字符串或无效值
        $schoolGrade = array_filter(explode(',', (string) $enterprise_grade));

        // 3. 防御性判断：如果该机构没有配置任何年级，直接返回空数组，避免空数组传入 whereIn 导致潜在SQL问题
        if (empty($schoolGrade)) {
            return [];
        }

        // 4. 查询年级数据并转换为数组
        $data = Grade::query()
            ->whereIn('id', $schoolGrade)
            ->orderByRaw('id ASC, sort ASC')
            ->get(['id as value', 'grade_name as label', 'id', 'parent_id'])
            ->toArray();

        // 5. 将扁平数组转换为树形结构并返回
        return array2treeUltimate($data);
    }
}
