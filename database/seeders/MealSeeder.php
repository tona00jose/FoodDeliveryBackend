<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Meal;

class MealSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Meal::insert([            
            [ "name" => "James Beard Pancakes", "restaurant_id" => 3, "price" => "24.00", "description" => "choice of topping on stack of three light and fluffy traditional flapjacks", "is_blocked" => 0 ],
            [ "name" => "Chicken Pancakes", "restaurant_id" => 3, "price" => "29.00", "description" => "world famous pancakes! Sullivan County Farms chicken, bourbon maple syrup", "is_blocked" => 0 ],
            [ "name" => "Griddle Special", "restaurant_id" => 3, "price" => "25.00", "description" => "two eggs, one pancake, bacon or sausage", "is_blocked" => 0 ],
            [ "name" => "Abe Lincoln Breakfast", "restaurant_id" => 3, "price" => "28.00", "description" => "two eggs, two silver dollar pancakes, bacon, sausage, grits, home fries", "is_blocked" => 0 ],
            [ "name" => "Spring Omelet", "restaurant_id" => 3, "price" => "26.00", "description" => "goat cheese, ramps, caulilini, asparagus, mixed mushrooms, choice of home fries or salad", "is_blocked" => 0 ],
            [ "name" => "Deviled Eggs", "restaurant_id" => 3, "price" => "13.00", "description" => "Brey's Farm eggs, horseradish, mustard, mayo", "is_blocked" => 0 ],
            [ "name" => "Apple Pie", "restaurant_id" => 3, "price" => "9.00", "description" => "cinnamon, cloves, lemon zest, sea salt, double crust. *pastry crust made with leaf lard", "is_blocked" => 0 ],
            [ "name" => "Peanut Butter Chocolate Pie", "restaurant_id" => 3, "price" => "9.00", "description" => "peanut butter mousse, ganache, chocolate cookie crust", "is_blocked" => 0 ],
            [ "name" => "Cookie and Ice Cream", "restaurant_id" => 3, "price" => "6.00", "description" => "salted chocolate chip cookie with homemade chocolate or vanilla ice cream", "is_blocked" => 0 ],
            [ "name" => "Cold Pressed Green Juice", "restaurant_id" => 3, "price" => "9.00", "description" => "fresh pressed daily in house", "is_blocked" => 0 ],
            [ "name" => "WILD PACIFIC CALAMARI", "restaurant_id" => 4, "price" => "17.99", "description" => "pickled cherry peppers, watercress, remoulade", "is_blocked" => 0 ],
            [ "name" => "Fried Oyster Deviled Eggs", "restaurant_id" => 4, "price" => "14.99", "description" => "bread & butter pickle relish, celery hearts, lots of herbs, smoked paprika", "is_blocked" => 0 ],
            [ "name" => "Zucchini Chips", "restaurant_id" => 4, "price" => "11.99", "description" => "chipotle-buttermilk dressing, parmesan", "is_blocked" => 0 ],
            [ "name" => "Oysters Rockefeller", "restaurant_id" => 4, "price" => "17.99", "description" => "spinach, watercress, garlic-herb butter, parmesan", "is_blocked" => 0 ],
            [ "name" => "CALAMARI FRA DIAVOLO", "restaurant_id" => 4, "price" => "27.99", "description" => "point judith rhode Island squid, spicy pomodoro, linguini, toasted garlic breadcrumbs", "is_blocked" => 0 ],
            [ "name" => "Shrimp Spaghettini", "restaurant_id" => 4, "price" => "28.99", "description" => "heirloom cherry tomatoes, sweet basil, white wine-lemon butter, chili-garlic gremolata", "is_blocked" => 0 ],
            [ "name" => "Spicy Sausage Garganelli", "restaurant_id" => 4, "price" => "25.99", "description" => "sausage ragù, san marzano tomatoes, tuscan kale, pecorino romano", "is_blocked" => 0 ],
        ]);
    }
}
