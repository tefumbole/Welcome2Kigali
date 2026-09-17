<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class SchemaColumns
{
    public static function forTable($table, array $data)
    {
        if (! Schema::hasTable($table)) {
            return [];
        }
        $columns = array_flip(Schema::getColumnListing($table));
        unset($data['_token'], $data['_method']);

        return array_intersect_key($data, $columns);
    }

    public static function has($table, $column)
    {
        try {
            return Schema::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
