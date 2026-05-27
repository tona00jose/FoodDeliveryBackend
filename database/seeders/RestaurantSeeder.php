<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Restaurant;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Restaurant::insert([            
            [ "name" => "Ember & Ivory", "user_id" => 3, "description" => "Located in the heart of the Seaport district, Salt & Ember offers a modern dining experience centered around coastal-inspired, wood-fired cooking. Featuring an expansive raw bar and a menu that highlights locally sourced seafood, our kitchen utilizes a custom hearth to bring out bold, natural flavors. Step into a warm, industrial-chic space where craft cocktails and relaxed hospitality set the stage for an unforgettable meal.", "is_blocked" => 0 ],
            [ "name" => "Osteria del Sole", "user_id" => 6, "description" => "Tucked away in the historic North End, Osteria del Sole brings the rustic warmth of the Italian countryside directly to your table. Specializing in handmade pastas, wood-fired pizzas, and time-honored family recipes, every dish is crafted with imported artisanal ingredients. Pair your meal with a selection from our extensive Tuscan wine list, and enjoy an intimate, candle-lit evening with friends and family.", "is_blocked" => 0 ],
            [ "name" => "Bubby's", "user_id" => 3, "description" => "Bubby's opened on Thanksgiving Day 1990. Chef / Owner Ron Silver began baking pies and selling them to restaurants and his neighbors out of a small kitchen at the corner of Hudson and North Moore St. in Tribeca. Today, NYC's beloved restaurant and pie shop celebrates 27 years of classic, made from scratch American cooking.", "is_blocked" => 0 ],
            [ "name" => "Old Ebbitt Grill", "user_id" => 9, "description" => "The Old Ebbitt Grill, Washington's oldest saloon, was founded in 1856 when, according to legend, innkeeper William E. Ebbitt bought a boarding house. Today, no one can pinpoint the house's exact location, but it was most likely on the edge of present-day Chinatown.", "is_blocked" => 0 ],
            [ "name" => "Hops & Harvest", "user_id" => 9, "description" => "Hops & Harvest is a neighborhood gathering place that pairs elevated comfort food with an impressive curation of craft beer. Our seasonally rotating menu features gourmet burgers, artisanal flatbreads, and shareable plates made from farm-fresh, regional ingredients. Whether you are catching the game at our reclaimed wood bar or relaxing on our dog-friendly outdoor patio, you'll find a welcoming, lively environment perfect for any occasion.", "is_blocked" => 0 ],
            [ "name" => "Cutlets", "user_id" => 3, "description" => "The fabric of Cutlets was born out of love and respect for these humble deli creations, met with a desire to bring quality ingredients to the table. Simply put, we're here to bring you a sandwich experience you can feel good about.", "is_blocked" => 0 ],
            [ "name" => "Los Tacos No. 1", "user_id" => 3, "description" => "Los Tacos No. 1 was created after three close friends from Tijuana, Mexico, and Brawley, California, decided to bring the authentic Mexican taco to the east coast.", "is_blocked" => 0 ],
            [ "name" => "Ci Siamo", "user_id" => 3, "description" => "Ci Siamo bridges the traditional with the contemporary, bringing live-fire cooking, housemade pasta, and a robust Italian wine list to the heart of Manhattan West.", "is_blocked" => 0 ],
            [ "name" => "Baba's", "user_id" => 3, "description" => "A chicken sandwich is only as good as its ingredients. That's why we import our spices and use top-quality ingredients in each of our Nashville Hot Chicken tenders, as well as our other offerings.", "is_blocked" => 0 ],
            [ "name" => "Teranga", "user_id" => 3, "description" => "Here, ancient customs and future trends coexist, and the intricacies of African culture blend seamlessly together over an Ethiopian-brewed coffee, or a bowl of Senegalese black-eyed pea stew and a side of spicy Ghanaian plantains.", "is_blocked" => 0 ],
            [ "name" => "PLNT Burger", "user_id" => 3, "description" => "PLNT Burger is dedicated to serving the best burgers on the planet, and for the planet!", "is_blocked" => 0 ],
            [ "name" => "The Green Room", "user_id" => 3, "description" => "The Green Room is fashioned after the green rooms in theaters and studios where performers relax when they are not on stage or camera. Everyone is a star at The Green Room with our immersive cocktail experiences, VIP service and the best views of the city.", "is_blocked" => 0 ],
            [ "name" => "She Wolf Bakery", "user_id" => 3, "description" => "After dinner service, we would make a fire. Early in the morning, our baker Austin Hall would come in, rake out the coals and bake bread for the day. This bread became a part of Roman's at every service and staff meal. Soon the chefs at Marlow & Sons and Diner wanted to bring the same quality and integrity to the bread they served.", "is_blocked" => 0 ],
            [ "name" => "Gramercy Tavern", "user_id" => 3, "description" => "One of America's most beloved restaurants, Gramercy Tavern has welcomed guests to enjoy its contemporary American cuisine, warm hospitality, and unparalleled service in New York City for over two decades. Chef Michael Anthony's ever-evolving seasonal menu showcases the restaurant's relationships with local farms and purveyors.", "is_blocked" => 0 ],
            [ "name" => "Big Gay Ice Cream", "user_id" => 3, "description" => "Beginning as a seasonal food truck in 2009, Big Gay Ice Cream has been named best ice cream parlor—as well as best food truck—in the country, along with numerous other accolades. Now the company has multiple locations in New York City & Philadelphia.", "is_blocked" => 0 ],
        ]);
    }
}
