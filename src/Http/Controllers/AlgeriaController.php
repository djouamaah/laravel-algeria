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

    public function postalCodes(string $commune): JsonResponse
    {
        $this->algeria->commune($commune) ?? abort(404);

        return $this->respond($this->algeria->postalCodes($commune));
    }

    /**
     * The postal code with its commune, daira and wilaya.
     */
    public function postalCode(string $code): JsonResponse
    {
        $postalCode = $this->algeria->postalCode($code) ?? abort(404);
        $commune = $this->algeria->commune($postalCode['commune_id']);

        return $this->respond($postalCode + [
            'commune' => $commune,
            'daira' => $this->algeria->daira($commune['daira_id']),
            'wilaya' => $this->algeria->wilaya($commune['wilaya_id']),
        ]);
    }

    protected function respond(mixed $data): JsonResponse
    {
        return response()->json(['data' => $data], 200, [], JSON_UNESCAPED_UNICODE)
            ->setPublic()
            ->setMaxAge(86400);
    }
}
