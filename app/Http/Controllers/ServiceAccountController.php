<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Response;

class ServiceAccountController extends Controller
{
    public function fetchData(Request $request)
    {
        $tableName = $request->input('table');
        $where = $request->input('q');
        $orderBy = $request->input('order');
        $perPage = $request->input('per_page') ?? 5000;

        // base query
        $query = DB::table(env('DB_SCHEMA_NAME') . "." . strtolower($tableName));

        // add WHERE clause if provided
        if(isset($where)) {
            foreach ($where as $condition) {
                // Extract the column name, operator, and value from the condition
                $columnName = $condition['column'];
                $operator = $condition['operator'];
                $value = $condition['value'];

                // Check if the column is of type bytea
                if ($this->isByteaColumn($columnName)) {
                    // Convert the bytea value to a base64-encoded string
                    $value = base64_encode($value);
                }

                // Add the where condition to the query
                $query->where($columnName, $operator, $value);
            }
        }

        // add ORDER BY clause if provided
        if(isset($orderBy)) {
            $orderBy = explode("~", $orderBy);
            if(sizeof($orderBy) === 2) {
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

        // Fetch total count for pagination
        $totalCountQuery = "SELECT COUNT(*) AS total FROM " . env('DB_SCHEMA_NAME') . "." . strtolower($tableName);
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
WHERE table_type = 'BASE TABLE' AND table_schema='" . env('DB_SCHEMA_NAME') . "'");
        } catch (\Exception $exception) {
            return response()->json(['status' => false, 'body' => $exception->errorInfo[0]]);
        }

        return Response::json(['status' => true, 'body' => $tables], 200);
    }

    public function fetchColumns(Request $request)
    {
        $tableName = $request->input('table');

        try {
            $columns = Schema::getColumns(env('DB_SCHEMA_NAME') . "." .strtolower($tableName));
        } catch (\Exception $exception) {
            return response()->json(['status' => false, 'body' => $exception->errorInfo[0]]);
        }

        return Response::json(['status' => true, 'body' => $columns], 200);
    }

    private function isByteaColumn($columnName)
    {
        // Implement your logic to determine if the column is of type bytea
        // You can query the PostgreSQL information schema or inspect the column metadata
        // For simplicity, let's assume all columns named 'bytea_column' are of type bytea
        return $columnName === 'bytea_column';
    }
}
