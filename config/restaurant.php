<?php
return [
    'mealdb_key' => env('MEALDB_API_KEY', '1'),
    'import_price' => (int) env('MEALDB_DEFAULT_PRICE_CENTS', 650),
    'riel_per_dollar' => (int) env('KHR_PER_USD', 4100),
    'staff_email' => env('STAFF_EMAIL', 'manager@example.com'),
    // Customers must sign in (or register) before checkout. Set ORDER_REQUIRES_LOGIN=false to allow guest orders.
    'order_requires_login' => (bool) env('ORDER_REQUIRES_LOGIN', true),
    'menu' => [
        ['id'=>1,'name'=>'Fish amok','kh'=>'អាម៉ុកត្រី','category'=>'Khmer classics','price'=>650,'emoji'=>'🥥','description'=>'Tender fish with coconut curry, kroeung and banana leaf.','tags'=>['Popular',"Chef's pick",'Seafood']],
        ['id'=>2,'name'=>'Beef lok lak','kh'=>'ឡុកឡាក់សាច់គោ','category'=>'Khmer classics','price'=>700,'emoji'=>'🥩','description'=>'Wok-seared beef with Kampot pepper and lime sauce.','tags'=>['Popular','Spicy']],
        ['id'=>3,'name'=>'Nom banh chok','kh'=>'នំបញ្ចុក','category'=>'Noodles & rice','price'=>350,'emoji'=>'🍜','description'=>'Rice noodles with fragrant fish gravy and fresh herbs.'],
        ['id'=>4,'name'=>'Bai sach chrouk','kh'=>'បាយសាច់ជ្រូក','category'=>'Noodles & rice','price'=>400,'emoji'=>'🍚','description'=>'Grilled pork, jasmine rice and homemade pickles.'],
        ['id'=>5,'name'=>'Fresh garden rolls','kh'=>'ណែមស្រស់','category'=>'Small plates','price'=>300,'emoji'=>'🥬','description'=>'Fresh vegetable rolls with aromatic herbs and dipping sauce.','tags'=>['Vegetarian','Gluten free']],
        ['id'=>6,'name'=>'Crispy spring rolls','kh'=>'ណែមចៀន','category'=>'Small plates','price'=>350,'emoji'=>'🥟','description'=>'Golden vegetable rolls with a sweet and tangy dipping sauce.','tags'=>['Vegetarian']],
        ['id'=>7,'name'=>'Iced Khmer coffee','kh'=>'កាហ្វេទឹកកក','category'=>'Drinks','price'=>200,'emoji'=>'🧋','description'=>'Strong coffee and creamy condensed milk, poured over ice.','tags'=>['Popular']],
        ['id'=>8,'name'=>'Fresh coconut','kh'=>'ទឹកដូង','category'=>'Drinks','price'=>250,'emoji'=>'🥥','description'=>'Naturally sweet young coconut, served chilled.'],
        ['id'=>9,'name'=>'House cheeseburger','kh'=>'ប៊ឺហ្គឺសាច់គោឈីស','category'=>'Fast food','price'=>450,'emoji'=>'🍔','description'=>'Grilled beef patty, melted cheese, pickles and our house sauce.','tags'=>['New']],
        ['id'=>10,'name'=>'Crispy chicken burger','kh'=>'ប៊ឺហ្គឺមាន់បំពង','category'=>'Fast food','price'=>400,'emoji'=>'🍔','description'=>'Crispy chicken, crunchy slaw and creamy pepper mayonnaise.'],
        ['id'=>11,'name'=>'Golden fries','kh'=>'ដំឡូងបារាំងបំពង','category'=>'Fast food','price'=>200,'emoji'=>'🍟','description'=>'Golden potato fries, lightly salted and served with ketchup.'],
        ['id'=>12,'name'=>'Chicken nuggets','kh'=>'សាច់មាន់បំពង','category'=>'Fast food','price'=>350,'emoji'=>'🍗','description'=>'Six crispy chicken bites with your choice of dipping sauce.'],
        ['id'=>13,'name'=>'Grilled chicken wrap','kh'=>'រ៉ាប់សាច់មាន់អាំង','category'=>'Fast food','price'=>400,'emoji'=>'🌯','description'=>'Grilled chicken, fresh lettuce, tomato and tangy house dressing.'],
        ['id'=>14,'name'=>'Burger & fries combo','kh'=>'ឈុតប៊ឺហ្គឺនិងដំឡូងបំពង','category'=>'Fast food','price'=>600,'emoji'=>'🍔','description'=>'Our house cheeseburger with a side of golden fries.'],
    ],
];
