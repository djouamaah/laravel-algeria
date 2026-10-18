<?php

namespace Djouamaah\Algeria\Http\Controllers;

use Djouamaah\Algeria\Algeria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AlgeriaController extends Controller
{
    public function __construct(protected Algeria $algeria)
    {
    }

    public function wilayas(): JsonResponse
    {
        return $this->respond($this->algeria->wilayas());
    }

    public function wilaya(string $wilaya): JsonResponse
    {
        return $this->respond($this->algeria->wilaya($wilaya) ?? abort(404));
    }

    public function dairas(string $wilaya): JsonResponse
    {
        $this->algeria->wilaya($wilaya) ?? abort(404);

        return $this->respond($this->algeria->dairas($wilaya));
    }

    public function wilayaCommunes(Request $request, string $wilaya): JsonResponse
    {
        $this->algeria->wilaya($wilaya) ?? abort(404);

        return $this->respond($request->filled('q')
            ? $this->algeria->search((string) $request->query('q'), $wilaya)
            : $this->algeria->communes($wilaya));
    }

    public function dairaCommunes(string $daira): JsonResponse
    {
        $this->algeria->daira($daira) ?? abort(404);

        return $this->respond($this->algeria->communesOfDaira($daira));
    }

    protected function respond(mixed $data): JsonResponse
    {
        return response()->json(['data' => $data], 200, [], JSON_UNESCAPED_UNICODE)
            ->setPublic()
            ->setMaxAge(86400);
    }
}
