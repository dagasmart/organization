<?php

namespace DagaSmart\Organization\Enums;

use DagaSmart\BizAdmin\Enums\Enum as Enums;

enum Enum
{

    /**
     * 学生状态
     */
    public const array StudentState = [
        ['value' => 1, 'label' => '正常', 'color' => 'success'],
        ['value' => 2, 'label' => '毕业', 'color' => 'info'],
        ['value' => 3, 'label' => '转学', 'color' => 'warning'],
        ['value' => 4, 'label' => '休学', 'color' => 'default'],
        ['value' => 5, 'label' => '退学', 'color' => 'danger'],
    ];

    /**
     * 在职状态
     */
    public const array WorkStatus = [
        ['value' => 0, 'label' => '未知'],
        ['value' => 1, 'label' => '正常'],
        ['value' => 2, 'label' => '病事假'],
        ['value' => 3, 'label' => '挂职'],
        ['value' => 4, 'label' => '停职'],
        ['value' => 5, 'label' => '离职'],
    ];

    /**
     * 是否全职
     */
    public const array IsFull = [
        ['value' => 1, 'label' => '是'],
        ['value' => 2, 'label' => '否'],
    ];

    /**
     * 员工状态
     */
    public const array State = [
        ['value' => 0, 'label' => '否'],
        ['value' => 1, 'label' => '是'],
    ];

    /**
     * 机构性质
     */
    public const array Nature = [
        ['label' => '公办学校‌', 'value' => 1],
        ['label' => '民办学校‌', 'value' => 2],
        ['label' => '独立学院‌', 'value' => 3],
        ['label' => '‌中外办学', 'value' => 4],
        ['label' => '私立学校‌', 'value' => 5],
    ];

    /**
     * 政治信仰
     */
    public const array Party = [
        ['label' => '无', 'value' => '无党派'],
        ['label' => '中国共产党', 'value' => '中国共产党'],
        ['label' => '民主政党', 'children' => [
            ['label' => '中国民革会', 'value' => '中国国民党革命委员会'],
            ['label' => '中国民建会', 'value' => '中国民主建国会'],
            ['label' => '中国民进会', 'value' => '中国民主促进会'],
            ['label' => '中国农工党', 'value' => '中国农工民主党'],
            ['label' => '中国致公党', 'value' => '中国致公党'],
            ['label' => '九三学社', 'value' => '九三学社'],
            ['label' => '台盟', 'value' => '台湾民主自治同盟'],
        ]],
    ];

    /**
     * 设备品牌-刷脸
     */
    public const array BrandFace = [
        ['label' => '支付宝', 'value'=>'alipay', 'children' => [
            ['label' => '蜻蜓2代', 'value' => '蜻蜓2代'],
            ['label' => '蜻蜓F4', 'value' => '蜻蜓F4'],
        ]],
        ['label' => '微信支付', 'value'=>'wechat', 'children' => [
            ['label' => '青蛙pro', 'value' => '青蛙pro'],
        ]],
    ];

    /**
     * 设备品牌-门禁
     */
    public const array BrandAccess = [
        ['label' => '品牌门禁', 'value'=>'access', 'children' => [
            ['label' => '宇视智能', 'value' => '宇视智能'],
            ['label' => 'TP-LINK', 'value' => 'TP-LINK'],
            ['label' => '其它', 'value' => '其它'],
        ]],
    ];

    /**
     * 设备品牌-监控
     */
    public const array BrandSurveillance = [
        ['label' => '品牌监控', 'value'=>'access', 'children' => [
            ['label' => '大华', 'value' => '大华'],
            ['label' => '威视', 'value' => '威视'],
            ['label' => '其它', 'value' => '其它'],
        ]],
    ];

    /**
     * 设备品牌-直播
     */
    public const array BrandLive = [
        ['label' => '品牌监控', 'value'=>'access', 'children' => [
            ['label' => '大华', 'value' => '大华'],
            ['label' => '海康', 'value' => '海康'],
            ['label' => '萤石', 'value' => '萤石'],
            ['label' => '其它', 'value' => '其它'],
        ]],
    ];

    public static function brand($name = null): array
    {
        $data = [
            'face' => self::BrandFace,
            'access' => self::BrandAccess,
            'surveillance' => self::BrandSurveillance,
            'live' => self::BrandLive,
        ];
        return $name ? $data[$name] : $data;
    }

    /**
     * 设备类型
     */
    public const array DeviceType = [
        ['label' => '刷脸设备', 'value' => 'face', 'tag' => '刷脸'],
        ['label' => '门禁设备', 'value' => 'access', 'tag' => '门禁'],
        ['label' => '监控设备', 'value' => 'surveillance', 'tag' => '监控'],
        ['label' => '直播设备', 'value' => 'live', 'tag' => '直播'],
    ];

    /**
     * 安装位置
     */
    public const array DevicePos = [
        ['label' => '进口入场', 'value' => 'in'],
        ['label' => '出口离场', 'value' => 'out'],
    ];

    /**
     * 机构职务
     */
    public const array JOB = [
        ['label' => '行政类', 'tag' => '主要负责学校日常运营的管理工作', 'children' =>
            [
                ['label' => '校长', 'value' => 100, 'tag' => '全面负责学校的行政和党建工作'],
                ['label' => '党支部书记', 'value' => 101, 'tag' => '协助校长处理日常事务，并负责党支部的日常工作'],
                ['label' => '教学副校长', 'value' => 102, 'tag' => '主管学校的教育教学工作'],
                ['label' => '科研副校长', 'value' => 103, 'tag' => '负责教育科研工作'],
                ['label' => '德育副校长', 'value' => 104, 'tag' => '主管学生的思想政治工作'],
                ['label' => '行政副校长', 'value' => 105, 'tag' => '负责学校后勤和安全管理工作'],
                ['label' => '工会主席', 'value' => 106, 'tag' => '主持工会的各项工作'],
                ['label' => '办公室主任', 'value' => 107, 'tag' => '协助校长处理学校的日常行政事务'],
                ['label' => '团支委书记', 'value' => 108, 'tag' => '负责学校团组织的各项工作'],
                ['label' => '人事处长', 'value' => 109, 'tag' => '负责师资引进和教师考核工作'],
                ['label' => '人事副处长', 'value' => 110, 'tag' => '负责协助处长工作'],
                ['label' => '教导处主任', 'value' => 111, 'tag' => '主管教育教学工作'],
                ['label' => '教导处副主任', 'value' => 112, 'tag' => '分别负责语文、数学和综合学科的教学工作'],
                ['label' => '德育处主任', 'value' => 113, 'tag' => '主管班主任和学生思想政治工作'],
                ['label' => '德育处副主任', 'value' => 114, 'tag' => '负责少先队工作'],
                ['label' => '总务处主任', 'value' => 115, 'tag' => '主管学校后勤工作'],
                ['label' => '总务处副主任', 'value' => 116, 'tag' => '负责学校财务管理工作'],
                ['label' => '教科室主任', 'value' => 117, 'tag' => '主管学校的教科研工作'],
                ['label' => '教科室副主任', 'value' => 118, 'tag' => '负责学校的课程建设'],
                ['label' => '财务处长', 'value' => 119, 'tag' => '负责学校财务统筹工作'],
                ['label' => '财务副处长', 'value' => 120, 'tag' => '负责学校财务协助处长工作'],
                ['label' => '会计员', 'value' => 121, 'tag' => '负责学校财务会计核算工作'],
                ['label' => '出纳员', 'value' => 122, 'tag' => '负责学校财务出纳工作'],
                ['label' => '办事员', 'value' => 123, 'tag' => '负责学校的部门科室工作'],
            ]
        ],
        ['label' => '教学类', 'tag' => '主要负责教育教学工作', 'children' =>
            [
                ['label' => '教务主任', 'value' => 200, 'tag' => '负责组织和管理学校的教学工作'],
                ['label' => '教研组长', 'value' => 201, 'tag' => '统筹学科教学计划制定与实施、组织集体备课与教学研讨活动'],
                ['label' => '年级组长', 'value' => 202, 'tag' => '全面管理本年级的教育教学活动'],
                ['label' => '班主任', 'value' => 203, 'tag' => '负责学生的全面教育和管理工作'],
                ['label' => '任课教师', 'value' => 204, 'tag' => '按教学计划和课程标准组织教学'],
            ]
        ],
        ['label' => '科研类', 'tag' => '主要从事科学研究工作', 'children' =>
            [
                ['label' => '研究所长', 'value' => 300, 'tag' => '引领研究所的发展并推动科学研究的进步'],
                ['label' => '实验室主任', 'value' => 301, 'tag' => '全面负责实验室的建设、管理和运行工作'],
                ['label' => '课题组组长', 'value' => 302, 'tag' => '全面负责课题的规划、实施和管理工作'],
                ['label' => '科研助理', 'value' => 303, 'tag' => '为科研项目提供实验技术支持、数据采集与分析'],
            ]
        ],
        ['label' => '教辅类', 'tag' => '为教学和科研提供辅助支持', 'children' =>
            [
                ['label' => '图书馆长', 'value' => 400, 'tag' => '全面负责图书馆的规划、建设与管理工作'],
                ['label' => '阅览室管理员', 'value' => 401, 'tag' => '负责维护阅览室的秩序，管理图书资源'],
                ['label' => '实验室管理员', 'value' => 402, 'tag' => '负责实验室的日常运作、设备维护、安全管理及教学支持'],
                ['label' => '资料室管理员', 'value' => 403, 'tag' => '负责资料的收集、整理、保管和提供服务'],
            ]
        ],
        ['label' => '工勤类', 'tag' => '为学校的正常运转提供必要的支持服务', 'children' =>
            [
                ['label' => '校医', 'value' => 500, 'tag' => '全面负责学校的日常医疗保健、健康教育与宣教'],
                ['label' => '心理咨询师', 'value' => 501, 'tag' => '负责学生心理健康教育和心理辅导'],
                ['label' => '网络管理员', 'value' => 502, 'tag' => '主管学校的网络建设与安全维护工作'],
                ['label' => '保安', 'value' => 503, 'tag' => '负责人员、车辆、物品出入，治安巡逻管理工作'],
                ['label' => '保洁员', 'value' => 504, 'tag' => '主管学校日常清洁卫生工作'],
            ]
        ],
    ];

    /**
     * 职务
     * @return array|array[]
     */
    public static function job(): array
    {
        return Enum::JOB;
    }

    /**
     * 性别
     * @return array
     */
    public static function sex(): array
    {
        return Enums::sex();
    }

    /**
     * 民族
     * @return array
     */
    public static function nation(): array
    {
        return Enums::nation();
    }


    /**
     * 民族
     * @return array
     */
    public static function student_state(): array
    {
        $data = [];
        $list = Enum::StudentState;
        if ($list) {
            foreach ($list as $item) {
                $label = $item['label'];
                $color = $item['color'];
                $data[$item['value']] = "<span class='label label-{$color} rounded-full font-thin'>{$label}</span>";
            }
        }
        return $data;
    }

    /**
     * 家庭关系
     * @return array
     */
    public static function family(): array
    {
        return Enums::family();
    }

    /**
     * 主要关系
     * @return array
     */
    public static function is_primary(): array
    {
        return Enums::is_primary();
    }

    /**
     * 机构性质
     * @return array
     */
    public static function nature(): array
    {
        $school = [
            ['label' => '公办学校‌', 'value' => 1],
            ['label' => '民办学校‌', 'value' => 2],
            ['label' => '独立学院‌', 'value' => 3],
            ['label' => '‌中外办学', 'value' => 4],
            ['label' => '私立学校‌', 'value' => 5],
        ];
        $company = [
            ['label' => '政府机关', 'value' => 11],
            ['label' => '事业单位', 'value' => 12],
            ['label' => '国有企业', 'value' => 13],
            ['label' => '集体企业', 'value' => 14],
            ['label' => '民营企业', 'value' => 15],
            ['label' => '外资企业', 'value' => 16],
            ['label' => '‌合资企业', 'value' => 17],
            ['label' => '股份公司', 'value' => 18],
            ['label' => '责任公司', 'value' => 19],
            ['label' => '个体工商户', 'value' => 20],
        ];
        return is_school_module() ? $school : $company;
    }



}
