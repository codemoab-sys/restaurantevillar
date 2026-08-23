<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductRecipeTest extends TestCase
{
    use WithFaker;

    public function test_admin_can_create_product_with_recipe_ingredients(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::create([
            'name' => 'Platos principales',
            'is_active' => true,
        ]);

        $ingredient = Product::create([
            'name' => 'Pan de hamburguesa',
            'category_id' => $category->id,
            'price' => 1.50,
            'cost' => 0.80,
            'stock' => 50,
            'is_active' => true,
            'is_saleable' => false,
            'is_new' => false,
            'is_chef_recommendation' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/products', [
                'name' => 'Hamburguesa especial',
                'category_id' => $category->id,
                'price' => 24.90,
                'cost' => 12.00,
                'stock' => 0,
                'is_saleable' => 'on',
                'is_new' => 'on',
                'is_chef_recommendation' => 'on',
                'ingredients' => [
                    $ingredient->id => '0.50',
                ],
            ]);

        $response->assertRedirect('/products');

        $product = Product::where('name', 'Hamburguesa especial')->firstOrFail();

        $this->assertDatabaseHas('rest_product_ingredients', [
            'product_id' => $product->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => '0.50',
        ]);

        $this->assertTrue($product->ingredients()->where('ingredient_id', $ingredient->id)->exists());
    }

    public function test_admin_can_update_product_recipe_ingredients(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::create([
            'name' => 'Platos principales',
            'is_active' => true,
        ]);

        $ingredientA = Product::create([
            'name' => 'Pan',
            'category_id' => $category->id,
            'price' => 1.00,
            'cost' => 0.50,
            'stock' => 30,
            'is_active' => true,
            'is_saleable' => false,
            'is_new' => false,
            'is_chef_recommendation' => false,
        ]);

        $ingredientB = Product::create([
            'name' => 'Carne',
            'category_id' => $category->id,
            'price' => 6.50,
            'cost' => 4.00,
            'stock' => 20,
            'is_active' => true,
            'is_saleable' => false,
            'is_new' => false,
            'is_chef_recommendation' => false,
        ]);

        $product = Product::create([
            'name' => 'Burger de prueba',
            'category_id' => $category->id,
            'price' => 18.00,
            'cost' => 8.00,
            'stock' => 0,
            'is_active' => true,
            'is_saleable' => true,
            'is_new' => false,
            'is_chef_recommendation' => false,
        ]);

        $product->ingredients()->sync([
            $ingredientA->id => ['quantity' => 0.30],
        ]);

        $this->actingAs($user)
            ->put('/products/' . $product->id, [
                'name' => 'Burger de prueba',
                'category_id' => $category->id,
                'price' => 18.00,
                'cost' => 8.00,
                'stock' => 0,
                'is_saleable' => 'on',
                'is_new' => 'off',
                'is_chef_recommendation' => 'off',
                'ingredients' => [
                    $ingredientB->id => '0.75',
                ],
            ]);

        $this->assertDatabaseHas('rest_product_ingredients', [
            'product_id' => $product->id,
            'ingredient_id' => $ingredientB->id,
            'quantity' => '0.75',
        ]);

        $this->assertDatabaseMissing('rest_product_ingredients', [
            'product_id' => $product->id,
            'ingredient_id' => $ingredientA->id,
        ]);
    }
}
