<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        $breakfast_defaults = [
            'Cheese',
            'Eggs',
            'Cold cuts',
            'Bread',
            'Butter',
            'Raspberry Jam',
            'Puff Pastry',
            'Yoghurt',
            'Cucumber',
            'Cherry Tomatoes',
            'Avocado',
            'Dill',
            'Parsley',
        ];

        $lunch_defaults = [
            'Beetroot',
            'Carrots',
            'Cherry Tomatoes',
            'Potatoes',
            'Lettuce',
            'Beef',
            'Chicken Breast',
            'Vermicelli Pasta',
            'Rice',
            'Avocado',
            'Dill',
            'Parsley',
        ];

        $dinner_defaults = [
            'Potatoes',
            'Beef',
            'Chicken Breast',
            'Vermicelli Pasta',
            'Rice',
            'Tomato Sauce',
            'Pork',
            'Salmon',
            'Avocado',
            'Dill',
            'Parsley',
        ];

        foreach($breakfast_defaults as $default){
            UserPreference::create([
                'user_id' => $user->id,
                'preference_category' => 'breakfast',
                'name' => $default,
                'value' => 1,
            ]);
        }

        foreach($lunch_defaults as $default){
            UserPreference::create([
                'user_id' => $user->id,
                'preference_category' => 'lunch',
                'name' => $default,
                'value' => 1,
            ]);
        }

        foreach($dinner_defaults as $default){
            UserPreference::create([
                'user_id' => $user->id,
                'preference_category' => 'dinner',
                'name' => $default,
                'value' => 1,
            ]);
        }

        return response()->json([
            'message' => 'Successfully registered',
            'user' => $user
        ]);
    }

    public function login(Request $request){
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required'],
        ]);
       
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'The provided credentials are incorrect.'], 401);
        }else{
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Successfully logged in',
                'user' => $user->toArray(),
                'token' => $token
            ]);
        }
    }

    public function edit(Request $request){
        $user = $request->user();
        $user->zip_code = $request->zip_code;
        $user->radius = $request->radius;
        $user->save();
        return response()->json([
            'message' => 'Successfully updated user',
            'user' => $user
        ]);
        
    }
}
