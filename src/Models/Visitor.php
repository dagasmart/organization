<?php

namespace DagaSmart\Organization\Models;

use DagaSmart\BizAdmin\Scopes\BaseScope;

/**
 * 基础-家长模型类
 */
class Visitor extends Model
{
    protected $table = 'biz_visitor';

    protected $primaryKey = 'id';

    protected $casts = [
        'mobile' => 'int',
        'id_card' => 'string',
    ];

    protected $appends = ['id_card_enc', 'mobile_enc'];

    public $timestamps = true;

    protected static function booted(): void
    {
        admin_transaction(function () {
            static::deleting(function (Visitor $visitor) {
                $visitor->children()
                    ->withoutGlobalScopes([BaseScope::class])
                    ->delete();
            });
        });
    }

    /**
     * 头像
     */
    public function getAvatarAttribute($value): ?string
    {
        return admin_image_url($value) ?? admin_config('admin.default_avatar');
    }

    public function setAvatarAttribute($value): void
    {
        $this->attributes['avatar'] = admin_image_path($value);
    }

    /**
     * 身份证号加密
     */
    public function getIdCardEncAttribute(): false|string
    {
        return base64_encode($this->attributes['id_card']);
    }

    /**
     * 手机号加密
     */
    public function getMobileEncAttribute(): false|string
    {
        return base64_encode($this->attributes['mobile']);
    }

    /**
     * 手机号脱敏
     */
    public function getMobileAttribute($value): false|string
    {
        return admin_sensitive($value, 3, 5);
    }

    public function setMobileAttribute($value): void
    {
        if ($value && ! strpos($value, '*')) {
            $this->attributes['mobile'] = $value;
        }
    }

    /**
     * 身份证号脱敏
     */
    public function getIdCardAttribute($value): false|string
    {
        return admin_sensitive($value, 6, 8);
    }

    public function setIdCardAttribute($value): void
    {
        if ($value && ! strpos($value, '*')) {
            $this->attributes['id_card'] = $value;
        }
    }

}
