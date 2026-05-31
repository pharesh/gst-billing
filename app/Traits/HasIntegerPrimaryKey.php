<?php

namespace App\Traits;

use MongoDB\Driver\Exception\BulkWriteException;

trait HasIntegerPrimaryKey
{
    public function initializeHasIntegerPrimaryKey(): void
    {
        $this->incrementing = false;
        $this->keyType = 'int';
    }

    protected static function bootHasIntegerPrimaryKey(): void
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();

            if (!empty($model->getAttribute($key))) {
                return;
            }

            $model->setAttribute($key, static::nextIntegerId($model));
        });
    }

    protected static function nextIntegerId($model): int
    {
        $table  = $model->getTable();
        $db     = $model->getConnection()->getMongoDB();
        $counters = $db->selectCollection('_counters');

        // Bootstrap the counter from the current max _id if this collection
        // hasn't been seen before (e.g. first run after MySQL→MongoDB migration).
        if ($counters->findOne(['_id' => $table]) === null) {
            $lastDoc    = $db->selectCollection($table)->findOne(
                [],
                ['sort' => ['_id' => -1], 'projection' => ['_id' => 1]]
            );
            $currentMax = $lastDoc ? (int) $lastDoc['_id'] : 0;

            try {
                $counters->insertOne(['_id' => $table, 'seq' => $currentMax]);
            } catch (BulkWriteException) {
                // Concurrent request already initialised — that's fine.
            }
        }

        $result = $counters->findOneAndUpdate(
            ['_id' => $table],
            ['$inc' => ['seq' => 1]],
            [
                'upsert'         => true,
                'returnDocument' => \MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
            ]
        );

        return (int) $result['seq'];
    }
}
