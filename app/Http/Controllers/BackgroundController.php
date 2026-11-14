<?php

namespace App\Http\Controllers;

use App\Services\BackgroundStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class BackgroundController extends Controller
{
    public function __construct(private readonly BackgroundStorage $backgrounds) {}

    public function update(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $request->validate([
            // ~5 MB binarios en base64 + cabecera data URI.
            'image_base64' => ['required', 'string', 'max:7000000'],
        ]);

        try {
            $path = $this->backgrounds->storeFromDataUri($request->string('image_base64')->toString());
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'No se pudo guardar la imagen.'], 500);
        }

        return response()->json([
            'success' => true,
            // Ruta relativa: funciona igual navegando por localhost o por la IP de la red.
            'path' => $path,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        try {
            $this->backgrounds->remove();
        } catch (Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'No se pudo eliminar la imagen.'], 500);
        }

        return response()->json(['success' => true, 'path' => null]);
    }
}
