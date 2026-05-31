<?php

namespace App\Traits;

trait HasIntegerPrimaryKey
{
    public $incrementing = false;

    protected $keyType = 'int';

    protected static function bootHasIntegerPrimaryKey(): void
    {
        static::creating(function ($model) {
            if (empty($model->getAttribute($model->getKeyName()))) {
                $model->setAttribute(
                    $model->getKeyName(),
                    (static::max($model->getKeyName()) ?? 0) + 1
                );
            }
        });
    }
}
