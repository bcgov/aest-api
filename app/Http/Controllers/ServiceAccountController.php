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
        $schema    = env('DB_SCHEMA_NAME');
        $table     = strtolower($request->input('table'));
        $perPage   = $request->input('per_page', 5000);
        $currentPage = $request->input('page', 1);

        // 0) Validate table exists
        $tableExists = (bool) DB::table('information_schema.tables')
            ->where('table_schema', $schema)
            ->where('table_name',  $table)
            ->count();

        if (! $tableExists) {
            return response()->json([
                'status' => false,
                'body'   => "Table '{$schema}.{$table}' not found"
            ], 404);
        }

        // 1) Build your base query
        $query = DB::table("{$schema}.{$table}");

        if ($request->filled('q')) {
            foreach ($request->input('q') as $cond) {
                $query->where($cond['column'], $cond['operator'], $cond['value']);
            }
        }
        if ($request->filled('order')) {
            [$col, $dir] = explode('~', $request->input('order'));
            $query->orderBy($col, $dir);
        }

        // 2) Paginate (no manual limit/offset!)
        $paginator = $query->paginate(
            $perPage,      // “per page”
            ['*'],         // columns
            'page',        // page‐param key
            $currentPage   // the page number
        )
        // force the base URL to “/”
        ->withPath('/');


        // 3) Convert any bytea columns
        $byteas = collect(Schema::getColumns("{$schema}.{$table}"))
            ->where('type_name','bytea')
            ->pluck('name')
            ->all();

        $converted = $paginator->getCollection()->map(function($row) use ($byteas) {
            foreach ($byteas as $col) {
                if (isset($row->$col) && is_resource($row->$col)) {
                    $row->$col = base64_encode(stream_get_contents($row->$col));
                }
            }
            return $row;
        });

        $paginator->setCollection($converted);

        // 4) Return the paginator—Laravel will include 'data' + all pagination meta
        return response()->json([
            'status' => true,
            'body'   => $paginator
        ]);
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

}
