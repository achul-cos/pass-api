<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Penumpang\StorePenumpangRequest;
use App\Http\Requests\Api\V1\Penumpang\ValidatePenumpangRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\Penumpang;
use Illuminate\Http\Request;
use Throwable;

class PenumpangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $penumpangs = Penumpang::get();

            $jumlahPenumpangs = $penumpangs->count();

            return response()->json([
                'data' => $penumpangs,
                'meta' => [
                    'total' => $jumlahPenumpangs,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePenumpangRequest $request)
    {
        try {
            // Validasi data yang dimasukkan oleh user
            $data = $request->validated();

            // Membuat data akun
            $penumpang = Penumpang::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'nomor_telepon' => $data['nomor_telepon'],
                'password' => Hash::make($data['password']),
            ]);

            $responseData = [
                'id' => $penumpang->id,
                'name' => $penumpang->name,
                'email' => $penumpang->email,
                'nomorTelepon' => $penumpang->nomor_telepon,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Akun Berhasil DiDaftarkan',
                'data' => $responseData,
            ], 201);            

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function validate(ValidatePenumpangRequest $request) 
    {
        try {
            $data = $request->validated();

            $login = $data['login'];
            $password = $data['password'];

            $penumpang = Penumpang::where(function ($q) use ($login) {
                if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                    $q->where('email', $login);
                } else {
                    $q->where('nomor_telepon', $login);
                }
            })->first();

            if (! $penumpang || ! Hash::check($password, $penumpang->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Login atau password salah',
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'data' => [
                    'id' => $penumpang->id,
                    'nomorTelepon' => $penumpang->nomor_telepon,
                    'nama' => $penumpang->name,
                    'email' => $penumpang->email,
                ],
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Terjadi kesalahan pada server',
            ], 500);
        }
    }
}
