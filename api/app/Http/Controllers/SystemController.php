<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    /**
     * Get Enum Values
     *
     * Returns every key-value pair in the Enum.
     *
     * @OA\Post(
     *     path="/api/enum",
     *     summary="Get Enum Values",
     *     tags={"System"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Model not found",
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ModelNotFoundException
     */
    public function getEnumValues(Request $request)
    {
        $result = [];
        static $instance = 'App\Enums\\';
        $input = $request->all();
        foreach ($input as $item) {
            $property = '';
            $array = explode('.', $item);
            $modelDirty = explode('.', $item)[0];
            $modelArray = explode('_', $modelDirty);
            $modelArray = array_map('ucfirst', $modelArray);
            $model = implode('', $modelArray);
            if (count($array) > 1) {
                $property = explode('.', $item)[1];
                $property = ucfirst($property);
            }
            $enum = $model . $property;
            if (class_exists('\App\Enums\\' . $enum)) {
                $enum = $instance . $enum;
            } else {
                throw new ModelNotFoundException();
            }
            $enum_index = $enum::getIndices();
            $enum_values = $enum::getValues();
            $array_enum = array_combine($enum_index, $enum_values);
            $result[strtolower(str_replace($instance, '', $enum))] = $array_enum;
        }
        return response()->json($result, 200, [], JSON_FORCE_OBJECT);
    }
}
