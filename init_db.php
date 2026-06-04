<?php
// init_db.php

$db = new PDO('sqlite:database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create the recipes table
$db->exec("CREATE TABLE IF NOT EXISTS recipes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    ingredients TEXT NOT NULL, -- Stored as a comma-separated list
    instructions TEXT NOT NULL
)");

// Clear old data for a clean slate if rerun
$db->exec("DELETE FROM recipes");

// Insert sample recipe data
$recipes = [
    [
        'name' => 'The Late-Night Panic Scramble',
        'ingredients' => 'eggs,cheese,sriracha',
        'instructions' => 'Whisk eggs aggressively. Throw into a pan. Realize you forgot butter. Add cheese to cover your mistakes. Drizzle in sriracha.'
    ],
    [
        'name' => 'Anarchist Protein Pancakes',
        'ingredients' => 'eggs,banana,protein_powder',
        'instructions' => 'Mash the banana until it looks sad. Mix in one scoop of protein powder and two eggs. Fry. Eat straight from the pan to avoid dishes.'
    ],
    [
        'name' => 'The Struggle Toast',
        'ingredients' => 'bread,butter,sugar',
        'instructions' => 'Toast the bread. Apply a generous layer of butter. Sprinkle sugar on top until it looks like a winter wonderland. Cry happy tears.'
    ]
];

$stmt = $db->prepare("INSERT INTO recipes (name, ingredients, instructions) VALUES (:name, :ingredients, :instructions)");

foreach ($recipes as $recipe) {
    $stmt->execute([
        ':name' => $recipe['name'],
        ':ingredients' => $recipe['ingredients'],
        ':instructions' => $recipe['instructions']
    ]);
}

echo "Database initialized with standalone SQLite data successfully!\n";
