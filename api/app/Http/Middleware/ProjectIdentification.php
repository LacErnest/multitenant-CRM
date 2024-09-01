<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Project;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProjectIdentification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        $project = Project::find($request->route('project_id'));
        if ($project || $user->role == UserRole::admin()->getIndex()) {
            return $next($request);
        } else {
            throw new ModelNotFoundException();
        }
    }
}
