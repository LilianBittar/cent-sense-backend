<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use App\Http\Requests\StoreUserPreferenceRequest;
use App\Http\Requests\UpdateUserPreferenceRequest;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return UserPreference::where('user_id', auth()->user()->id)->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->userPreferences()->where('name', $request->name)->where('preference_category', $request->preference_category)->exists()) {
            return response()->json([
                'message' => 'User preference already exists',
            ], 409);
        }
        $pref = UserPreference::create([
            'user_id' => $user->id,
            'preference_category' => $request->preference_category,
            'name' => $request->name,
            'value' => true,
        ]);
        if($pref->preference_category == 'exclude'){
            UserPreference::where('user_id', $user->id)
                            ->where('name', $request->name)
                            ->where('preference_category', '!=', 'exclude')
                            ->delete();
        }
        return response()->json([
            'message' => 'Successfully created user preference',
            'user_preference' => $pref,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserPreference $userPreference)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserPreference $userPreference)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserPreferenceRequest $request, UserPreference $userPreference)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        UserPreference::where('user_id', $request->user()->id)
                        ->where('name', $request->name)
                        ->where('preference_category', $request->preference_category)
                        ->delete();
        return response()->json([
            'message' => 'Successfully deleted user preference',
        ], 200);
    }
}
