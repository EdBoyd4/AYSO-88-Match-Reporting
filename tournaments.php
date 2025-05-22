<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $parentName = $_POST['parent_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $playerName = $_POST['player_name'] ?? '';
    $validationCode = $_POST['validation_code'] ?? '';
    $availability = $_POST['availability'] ?? [];

    echo "<pre>";
    echo "Parent: $parentName\nEmail: $email\nPlayer: $playerName\nCode: $validationCode\n";
    print_r($availability);
    echo "</pre>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>GSS88 Tournament Availability</title>
</head>
<body>
    <h1>Player Tournament Availability</h1>
    <form method="POST" action="tournaments.php">
        <label for="parent_name">Parent Name:</label><br>
        <input type="text" name="parent_name" required><br><br>

        <label for="email">Parent Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label for="player_name">Player Name:</label><br>
        <input type="text" name="player_name" required><br><br>

        <label for="validation_code">Validation Code:</label><br>
        <input type="text" name="validation_code" required><br><br>

        <h3>Tournament Availability</h3>

        <?php
        $tournaments = [
            "Apr 5 - 6" => "Apricot Jam - Moorpark",
            "Apr 12 - 13" => "Fontana Speedway Classic",
            "Apr 26 - 27" => "TBD",
            "May 3 - 4" => "TBD",
            "May 17 - 18" => "The players representing Region 88 at the Glendale Legends Cup will be invited on an individual basis. However, if there is sufficient interest, a second team may be formed to play in a different tournament.",
            "May 24 - 25" => "TBD",
            "May 31 - June 1" => "TBD",
            "June 7 - 8" => "TBD",
            "June 14 - 15" => "TBD",
            "June 21 - 22" => "TBD",
            "July 5 - 6" => "TBD",
        ];

        foreach ($tournaments as $date => $name) {
            $label = "$date — $name";
            $id = str_replace([' ', '-', '–'], '_', $date);
            echo "<label><input type='checkbox' name='availability[$id]' value='yes'> $label</label><br><br>";
        }
        ?>

        <input type="submit" value="Submit Availability">
    </form>
</body>
</html>
