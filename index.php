<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crawler</title>
</head>

<body>
    <form method="get" action="crawler.php">
        <label for="url">URL:</label>
        <input type="text" name="url" id="url">
        <input type="hidden" name="trustpilot">
        <label for="page">Page:</label>
        <input type="text" id="page" name="page">
        <select name="lang">
            <option value="da-DK">Danish</option>
            <option value="en-GB">English</option>
            <option value="de-DE">German</option>
        </select>
        <input type="hidden" name="stars" value="5">
        <input type="hidden" name="stars" value="4">
        <button type="submit">Submit</button>
    </form>
</body>

</html>