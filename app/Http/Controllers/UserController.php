<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\User\UserIndexRequest;
use App\Http\Requests\User\UserStoreRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    private const LIST_FIELDS = ['id', 'name', 'email', 'is_active', 'created_at', 'avatar'];

    public function index(UserIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir  = $request->validated('dir') ?? 'desc';
        $searchable = ['name', 'email'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
            'filter_is_active' => $request->validated('filter_is_active'),
        ];

        $users = User::select(self::LIST_FIELDS)
            ->doesntHave('employee')
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('users/List', [
            'data' => $users,
            'sort' => $sort,
            'dir' => $dir,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('users/Form');
    }

    public function edit(User $user)
    {
        return Inertia::render('users/Form', [
            'user' => $user,
        ]);
    }

    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            $data['password'] = Hash::make(Str::random(12));
        }

        $user = User::create($data);

        return to_route('users.index')->with('success', "Usuario {$user->name} creado correctamente.");
    }

    public function update(UserStoreRequest $request, User $user)
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        
        $user->update($data);

        if ($user->is_active === false) {
            $user->tokens()->delete();
        }

        return to_route('users.index')->with('success', "Usuario {$user->name} actualizado correctamente.");
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
