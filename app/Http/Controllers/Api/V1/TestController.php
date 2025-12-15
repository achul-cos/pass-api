<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        return [
            [
                'id' => 10,
                'title' => 'Ini adalah Judul',
                'body' => 'This is body'
            ]
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $data = $request->all();
        $data = $request->only('title', 'body');
        return response()->json([
            'id' => 1,
            'title' => $data['title'],
            'body' => $data['body']  
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json([
            'message' => 'Ambatutest Berhasil',
            'data' => [
                'id' => 1,
                'title' => 'Jadwal Test',
                'body' => 'Jadwal Body'                
            ]
        ])
        ->header('Test', 'Ambamuwani')
        ->header('Test2', 'Ambadeblou')
        ->setStatusCode(201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'title' => 'required|string|min:2',
            'body' => ['required', 'string', 'min:2']
        ]);

        return $data;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return response()->noContent();
    }    
}
