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
        );

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
