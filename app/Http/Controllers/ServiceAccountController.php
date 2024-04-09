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
        $perPage = $request->input('per_page') ?? 100;
        $bindings = [];
        // base query
        $query = "SELECT * FROM " . env('DB_SCHEMA_NAME') . "." . strtolower($tableName);

        // add WHERE clause if provided
        if(isset($where)) {
            $q = explode("~", $where);
            if(sizeof($q) === 3) {
                $query .= " WHERE {$q[0]} {$q[1]} ?";
                $bindings[] = $q[2];
            }
        }

        // add ORDER BY clause if provided
        if(isset($orderBy)) {
            $orderBy = explode("~", $orderBy);
            if(sizeof($orderBy) === 2) {
                $query .= " ORDER BY {$orderBy[0]} {$orderBy[1]}";
            }
        }

        // Pagination parameters
        $currentPage = $request->input('page', 1); // Current page, default is 1
        $offset = ($currentPage - 1) * $perPage;

        // Append LIMIT and OFFSET to the query
        $query .= " LIMIT $perPage OFFSET $offset";

        // execute the query with parameter bindings
        try {
            $data = DB::select($query, $bindings);
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
}
