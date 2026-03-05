<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\Role;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->get();
        
        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::all();
        $products = Product::active()->get()->mapWithKeys(function ($product) {
            return [$product->slug => ['name' => $product->name]];
        })->toArray();
        
        return view('admin.users.create', [
            'roles' => $roles,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'exists:roles,slug'],
            'products' => ['array'],
            'products.*' => ['string'],
            'has_health_access' => ['nullable', 'boolean'],
            'has_analytics_access' => ['nullable', 'boolean'],
            'send_welcome_email' => ['nullable', 'boolean'],
        ]);

        // For viewers, require at least one dashboard access
        if ($validated['role'] === 'viewer') {
            $hasHealth = $request->boolean('has_health_access');
            $hasAnalytics = $request->boolean('has_analytics_access');
            if (!$hasHealth && !$hasAnalytics) {
                return back()->withInput()->withErrors([
                    'has_health_access' => 'Viewer must have access to at least Health or Analytics.',
                ]);
            }
        }

        $plainPassword = $validated['password'];

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($plainPassword),
            'email_verified_at' => now(),
            'has_health_access' => $validated['role'] === 'viewer' ? $request->boolean('has_health_access') : true,
            'has_analytics_access' => $validated['role'] === 'viewer' ? $request->boolean('has_analytics_access') : true,
        ]);

        // Assign role
        $role = Role::where('slug', $validated['role'])->first();
        $user->roles()->attach($role);

        // Assign products (only for viewers)
        $productNames = [];
        if ($validated['role'] === 'viewer' && !empty($validated['products'])) {
            $user->syncProducts($validated['products']);
            $productNames = Product::whereIn('slug', $validated['products'])
                ->pluck('name')
                ->toArray();
        }

        // Send welcome email if requested
        if ($request->boolean('send_welcome_email')) {
            Mail::to($user->email)->send(new WelcomeUserMail(
                $user,
                $plainPassword,
                $role->name,
                $productNames
            ));

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully. Welcome email sent to ' . $user->email);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        $products = Product::active()->get()->mapWithKeys(function ($product) {
            return [$product->slug => ['name' => $product->name]];
        })->toArray();
        $assignedProducts = $user->assignedProducts();
        
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
            'products' => $products,
            'assignedProducts' => $assignedProducts,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', 'exists:roles,slug'],
            'products' => ['array'],
            'products.*' => ['string'],
            'has_health_access' => ['nullable', 'boolean'],
            'has_analytics_access' => ['nullable', 'boolean'],
        ]);

        // For viewers, require at least one dashboard access
        if ($validated['role'] === 'viewer') {
            $hasHealth = $request->boolean('has_health_access');
            $hasAnalytics = $request->boolean('has_analytics_access');
            if (!$hasHealth && !$hasAnalytics) {
                return back()->withInput()->withErrors([
                    'has_health_access' => 'Viewer must have access to at least Health or Analytics.',
                ]);
            }
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'has_health_access' => $validated['role'] === 'viewer' ? $request->boolean('has_health_access') : true,
            'has_analytics_access' => $validated['role'] === 'viewer' ? $request->boolean('has_analytics_access') : true,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Update role
        $role = Role::where('slug', $validated['role'])->first();
        $user->roles()->sync([$role->id]);

        // Update products (only for viewers)
        if ($validated['role'] === 'viewer') {
            $user->syncProducts($validated['products'] ?? []);
        } else {
            $user->syncProducts([]); // Admins don't need product assignments
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
