<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Response;

class ServiceAccountController extends Controller
{
    public function fetchData(Request $request)
    {
        $tableName = $request->input('table');
        $where = $request->input('q');
        $orderBy = $request->input('order');
        $perPage = $request->input('per_page') ?? 5000;
        //        $columnInfo = $this->fetchColumns($request);

        // base query
        $query = DB::table(env('DB_SCHEMA_NAME').'.'.strtolower($tableName));

        // add WHERE clause if provided
        if (isset($where)) {
            foreach ($where as $condition) {
                // Extract the column name, operator, and value from the condition
                $columnName = $condition['column'];
                $operator = $condition['operator'];
                $value = $condition['value'];

                // Add the where condition to the query
                $query->where($columnName, $operator, $value);
            }
        }

        // add ORDER BY clause if provided
        if (isset($orderBy)) {
            $orderBy = explode('~', $orderBy);
            if (count($orderBy) === 2) {
                $query->orderBy($orderBy[0], $orderBy[1]);
            }
        }

        // pagination parameters
        $currentPage = $request->input('page', 1); // Current page, default is 1
        $offset = ($currentPage - 1) * $perPage;

        // append LIMIT and OFFSET to the query
        $query->limit($perPage)->offset($offset);

        // execute the query with parameter bindings
        try {
            $data = $query->get();
        } catch (\Exception $exception) {
            return response()->json(['status' => false, 'body' => $exception->errorInfo[0]]);
        }

        // Convert bytea column values
        $data = $this->convertByteaColumns($data, $tableName);

        // Fetch total count for pagination
        $totalCountQuery = 'SELECT COUNT(*) AS total FROM '.env('DB_SCHEMA_NAME').'.'.strtolower($tableName);
        $totalCount = DB::selectOne($totalCountQuery);

        // Create pagination object
        $paginatedData = new LengthAwarePaginator(
            $data, $totalCount->total, $perPage, $currentPage
        );

        return response()->json(['status' => true, 'body' => $paginatedData]);
    }

    public function fetchTables(Request $request)
    {
        try {
            $tables = DB::select("SELECT table_name FROM information_schema.tables
WHERE table_type = 'BASE TABLE' AND table_schema='".env('DB_SCHEMA_NAME')."'");
        } catch (\Exception $exception) {
            return response()->json(['status' => false, 'body' => $exception->errorInfo[0]]);
        }

        return Response::json(['status' => true, 'body' => $tables], 200);
    }

    public function fetchColumns(Request $request)
    {
        $tableName = $request->input('table');

        try {
            $columns = Schema::getColumns(env('DB_SCHEMA_NAME').'.'.strtolower($tableName));
        } catch (\Exception $exception) {
            return response()->json(['status' => false, 'body' => $exception->errorInfo[0]]);
        }

        return Response::json(['status' => true, 'body' => $columns], 200);
    }

    // bytea in pg needs to be converted to a string
    private function convertByteaColumns($data, $tableName)
    {
        $columnInfo = Schema::getColumns(env('DB_SCHEMA_NAME').'.'.strtolower($tableName));
        // convert bytea column values to a suitable format for JSON serialization
        foreach ($data as $row) {
            foreach ($columnInfo as $column) {
                if ($column['type_name'] === 'bytea') {
                    // convert bytea value to base64-encoded string
                    $columnName = $column['name'];

                    // convert the value to a string before encoding it
                    $txt = stream_get_contents($row->{$columnName});
                    $row->{$columnName} = base64_encode($txt);
                }
            }
        }

        return $data;
    }
}
