<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

if (!function_exists('get_migration_file_for_table')) {
    /**
     * Find the migration file for a given table.
     */
    function get_migration_file_for_table(string $table): ?string
    {
        $path = database_path('migrations');
        $files = File::files($path);

        foreach ($files as $file) {
            $content = File::get($file->getPathname());
            if (strpos($content, "Schema::create('$table'") !== false) {
                return $file->getPathname();
            }
        }

        return null;
    }
}

if (!function_exists('get_table_columns')) {
    /**
     * Extract all columns from the migration file for a given table.
     */
    function get_table_columns(string $table): array
    {
        $file = get_migration_file_for_table($table);
        if (!$file) return [];

        $content = File::get($file);
        $pattern = '/\$table->(\w+)\([\'"]([^\'"]+)[\'"][^\;]*;/i';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $columns = [];
        foreach ($matches as $match) {
            $columns[$match[2]] = $match[1]; // column name => type
        }

        return $columns;
    }
}

if (!function_exists('get_column_line')) {
    /**
     * Get the migration line that defines a given column.
     */
    function get_column_line(string $column, string $table): ?string
    {
        $file = get_migration_file_for_table($table);
        if (!$file) return null;

        $content = File::get($file);
        $pattern = '/\$table->\w+\([\'"]' . $column . '[\'"][^\;]*;/i';

        return preg_match($pattern, $content, $match) ? $match[0] : null;
    }
}

if (!function_exists('is_column_required')) {
    /**
     * Determine if a column is marked as required in its migration.
     */
    function is_column_required(string $column, string $table): bool
    {
        $line = get_column_line($column, $table);
        if (!$line) return false;

        return stripos($line, '->required(') !== false;
    }
}

if (!function_exists('is_column_nullable')) {
    /**
     * Determine if a column is explicitly nullable.
     */
    function is_column_nullable(string $column, string $table): bool
    {
        $line = get_column_line($column, $table);
        if (!$line) return false;

        return stripos($line, '->nullable(') !== false;
    }
}

if (!function_exists('get_column_type')) {
    /**
     * Get the type of a column (e.g. string, integer).
     */
    function get_column_type(string $column, string $table): ?string
    {
        $columns = get_table_columns($table);
        return $columns[$column] ?? null;
    }
}

if (!function_exists('required_star')) {
    /**
     * Return a red asterisk if column is required.
     */
    function required_star(string $column, string $table): string
    {
        return is_column_required($column, $table)
            ? '<span style="color:red;font-weight:bold">*</span>'
            : '';
    }
}

if (!function_exists('field_info')) {
    /**
     * Get an array of metadata about a column.
     */
    function field_info(string $column, string $table): array
    {
        $type = get_column_type($column, $table);
        $line = get_column_line($column, $table);

        return [
            'column'    => $column,
            'table'     => $table,
            'type'      => $type,
            'required'  => is_column_required($column, $table),
            'nullable'  => is_column_nullable($column, $table),
            'definition'=> $line,
        ];
    }
}

if (!function_exists('describe_table')) {
    /**
     * Get a summary of all columns in a table.
     */
    function describe_table(string $table): array
    {
        $columns = get_table_columns($table);
        $data = [];

        foreach ($columns as $column => $type) {
            $data[$column] = [
                'type'      => $type,
                'required'  => is_column_required($column, $table),
                'nullable'  => is_column_nullable($column, $table),
            ];
        }

        return $data;
    }
}

if (!function_exists('cache_table_schema')) {
    /**
     * Cache the table schema for faster access.
     */
    function cache_table_schema(string $table): void
    {
        Cache::rememberForever("table_schema_{$table}", function () use ($table) {
            return describe_table($table);
        });
    }
}

if (!function_exists('get_cached_schema')) {
    /**
     * Get cached schema (if exists).
     */
    function get_cached_schema(string $table): ?array
    {
        return Cache::get("table_schema_{$table}");
    }
}
