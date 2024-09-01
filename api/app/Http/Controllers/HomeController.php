<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="OZ Finance API Documentation",
 *      description="API documentation using Swagger",
 *      @OA\Contact(
 *          email="tim@cyrextech.net"
 *       ),
 * ),
 * @OA\Server(
 *      url="http://localhost:8080",
 *      description="Local server"
 * ),
 * @OAS\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 */

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * @OA\Get(
     *     path="/api/home",
     *     operationId="home",
     *     tags={"Home"},
     *     summary="Home",
     *     description="Returns home",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource not found")
     * )
     *
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return 'home';
    }
}
