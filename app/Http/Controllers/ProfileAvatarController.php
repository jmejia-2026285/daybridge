<?php

namespace App\Http\Controllers;

use App\Services\AvatarStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class ProfileAvatarController extends Controller
{
    public function __construct(private readonly AvatarStorage $avatars) {}

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            // ~5 MB binarios en base64 + cabecera data URI.
            'image_base64' => ['required', 'string', 'max:7000000'],
        ]);

        try {
            $filename = $this->avatars->storeFromDataUri($request->user(), $request->string('image_base64')->toString());
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'No se pudo guardar la imagen.'], 500);
        }

        return response()->json([
            'success' => true,
            // Ruta relativa: funciona igual navegando por localhost o por la IP de la red.
            'path' => 'storage/'.$filename,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $this->avatars->remove($request->user());
        } catch (Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'No se pudo eliminar la imagen.'], 500);
        }

        return response()->json(['success' => true, 'path' => null]);
    }
}
