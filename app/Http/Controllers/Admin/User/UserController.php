<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends AdminController
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'filter.name' => ['sometimes', 'string', 'max:255'],
            'filter.email' => ['sometimes', 'string', 'email', 'max:255'],
            'sort' => ['sometimes', 'string', 'in:name,email,created_at,-name,-email,-created_at'],
        ]);

        $users = QueryBuilder::for(User::class)
            ->allowedFilters(['name', 'email'])
            ->allowedSorts(['name', 'email', 'created_at'])
            ->paginate(10)
            ->withQueryString();

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        $user = User::create($data);
        $user->assignRoleWithHierarchy($data['role']);
        $user->refresh();
        $user->load('roles');

        return $this->resource(new UserResource($user), 'User created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();
        $user->update($data);

        if (isset($data['role'])) {
            $user->assignRoleWithHierarchy($data['role']);
        }

        $user->refresh();
        $user->load('roles');

        return $this->resource(new UserResource($user), 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return $this->success(null, 'User deleted successfully.');
    }
}
