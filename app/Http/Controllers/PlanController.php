<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\UserPreference;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use Illuminate\Http\Request;
use Google\Cloud\Translate\V2\TranslateClient;
use Illuminate\Support\Facades\Http;

class PlanController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $plans = Plan::where('user_id', $user->id)->get();
        foreach ($plans as $key => $plan) {
            $plan->recipes = Recipe::where('plan_id', $plan->id)->get();
            foreach ($plan->recipes as $key => $recipe) {
                $recipe->recipeIngredients = $recipe->recipeIngredients()->with(['ingredientProduct', 'ingredient'])->get();
                $recipe->total_price = 0;
                foreach ($recipe->recipeIngredients as $key => $recipeIngredient) {
                    if ($recipeIngredient->ingredientProduct) {
                        $recipe->total_price += $recipeIngredient->ingredientProduct->price;
                        $plan->total_price += $recipeIngredient->ingredientProduct->price;
                    }
                }
            }
        }
        return response()->json([
            'message' => 'Successfully retrieved plans',
            'plans' => $plans,
        ]);
    }

    public function generate(Request $request)
    {
        $user = $request->user();
        $meals = $request->meals;

        foreach ($meals as &$meal) {
            $meal['recipes'] = [];

            $mealTypes = ['breakfast', 'lunch', 'dinner'];

            foreach ($mealTypes as $mealType) {
                if ($meal[$mealType]) {
                    $ingredients = UserPreference::where('user_id', $user->id)
                        ->where('preference_category', $mealType)
                        ->get()
                        ->pluck('name');

                    $recipe = $this->createRecipe($mealType, $ingredients);
                    $recipe = trim(preg_replace('/\s+/', ' ', $recipe));
                    $recipe = json_decode($recipe, true);

                    foreach ($recipe['ingredients_used'] as $key => $ingredient_used) {
                        $ingredient = Ingredient::where('name', $ingredient_used)->first();
                        if ($ingredient) {
                            $suggestion_list = $this->getProductSuggestions($ingredient->name_dk)['suggestions'];
                            usort($suggestion_list, function ($a, $b) {
                                return $b['price'] - $a['price'];
                            });
                            
                            $selected_suggestion_index = count($suggestion_list);
                            $selected_suggestion_index = floor($selected_suggestion_index / 2); 
                            // $selected_suggestion_index = count($suggestion_list) - 1; 
                            $suggestion_list[$selected_suggestion_index]['selected'] = true;
                            $recipe['suggestions'][$ingredient->name] = $suggestion_list;
                        }
                    }

                    $meal['recipes'][$mealType] = $recipe;
                }
            }
        }

        return response()->json([
            'message' => 'Successfully generated plan',
            'plan' => $meals,
            'budget' => $request->budget,
        ]);
    }

    public function store(Request $request)
    {
        $plan = Plan::create([
            'user_id' => $request->user()->id,
            'start_date' => $request->start_date,
            'budget' => $request->budget,
            'number_of_days' => $request->number_of_days
        ]);

        $mealTypes = ['breakfast', 'lunch', 'dinner'];

        foreach ($request->meal_list as $key => $meal) {
            foreach ($mealTypes as $mealType) {
                if (!empty($meal['recipes'][$mealType])) {
                    $recipe = Recipe::create([
                        'name' => $meal['recipes'][$mealType]['name'],
                        'instructions' => $meal['recipes'][$mealType]['instructions'],
                        'recipe_date' => date('Y-m-d', $meal['date'] / 1000),
                        'meal_type' => $mealType,
                        'plan_id' => $plan->id,
                    ]);
                    $ingredients_used =  $meal['recipes'][$mealType]['ingredients_used'];
                    foreach ($ingredients_used as $key => $ingredient_used) {
                        $ingredient = Ingredient::where('name', $ingredient_used)->first();
                        if ($ingredient) {
                            $recipe_ingredient = $recipe->recipeIngredients()->create([
                                'ingredient_id' => $ingredient->id,
                            ]);
                            $selected_suggested_product = null;
                            foreach ($meal['recipes'][$mealType]['suggestions'][$ingredient_used] as $suggestion_key => $suggestion) {
                                if (!empty($suggestion['selected'])) {
                                    $selected_suggested_product = $suggestion;
                                    break;
                                }
                            }
                            $recipe_ingredient->ingredientProduct()->create([
                                'recipe_ingredient_id' => $recipe_ingredient->id,
                                'prod_id' => $selected_suggested_product['prod_id'],
                                'price' => $selected_suggested_product['price'],
                                'title' => $selected_suggested_product['title'],
                                'description' => $selected_suggested_product['description'],
                                'link' => $selected_suggested_product['link'],
                                'img' => $selected_suggested_product['img'],
                                
                            ]);
                        }
                    }
                }
            }
        }



        return response()->json([
            'message' => 'Successfully created plan',
            'plan' => $plan,
        ]);
    }

    public function createRecipe($meal, $ingredients)
    {
        $ingredients = str_replace(['"', "[", "]"], '', $ingredients);
        $preprompt = "create a " . $meal . " recipe using some or all the following ingredients: " . $ingredients .
        '.  place the list of used ingredients into a list called "ingredients_used" . 
        Use only the names of the ingredients used. The format of the json object should be as follows: 
        { "ingredients_used": ["ingredient1", "ingredient2", "ingredient3"], "instructions": "instructions for the recipe", "name": "name of the recipe"}' ;
        $data = [
            'prompt' => $preprompt,
            'max_tokens' => 400,
        ];
        
        // Initializing cURL session
        $ch = curl_init('https://api.openai.com/v1/engines/text-davinci-003/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . env('OPENAI_API_KEY')
        ]);
        
        // Executing the POST request
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Decoding the response
        $result = json_decode($response, true);
        
        // Outputting the generated recipe
        return $result['choices'][0]['text'];
    } 

    public function getProductSuggestions($product_name){
        $apiKey = env('SALLING_GROUP_API_KEY');
        $url = "https://api.sallinggroup.com/v1-beta/product-suggestions/relevant-products?query=" . urlencode($product_name);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ));
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        return $result;
    }


   


}
