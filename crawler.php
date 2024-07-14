<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'vendor/autoload.php';
require_once 'getGoogleReviews.php';
require_once 'db.php';

if (!$db) {
    die("Could not connect to database: " . mysqli_connect_error());
}

use Goutte\Client;

if (isset($_GET["trustpilot"])) {
    $url = "https://www.trustpilot.com/review/cykelfaergen.info?stars=4&stars=5";
    $lang = "en-GB";
    $rated = "Rated ";
    $outof = " out of ";
    $client = new Client();
    $crawler = $client->request('GET', $url);

    // Check for crawler status
    /* if ($client->getResponse()->getStatus() == 200) {
        echo "Crawler is running";
    } else {
        echo "Crawler is not running";
    } */

    // Get the first element with the class 'styles_reviewCardInner__EwDq2'
    $elements = $crawler->filter('.styles_reviewCardInner__EwDq2');
    foreach ($elements as $element) {
        $item = new DOMElement($element->nodeName, $element->nodeValue);
        $html = $element->ownerDocument->saveHTML($element);
        /* Set encoding */
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");

        /* Find user name via the Saved html */
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $fullRating = $crawler->filter('.styles_rating__uyC6m');
        $totalReviews = $crawler->filter('[data-reviews-count-typography]')->text();

        $aggregateNumber = $crawler->filter('.styles_clickable__zQWyh')->text();

        if (strpos($url, 'location/langballigau') === false) {
            $totalReviewsPlus = $crawler->filter('.styles_clickable__zQWyh')->text();
        } else {
            $sql = "SELECT * FROM totalReviews WHERE lang = '" . $lang . "'";
            $result = $db->query($sql);
            $row = $result->fetch_assoc();
            $totalReviewsPlus = $row['total_reviews'];
        }


        $totalStars = $crawler->filter('.star-rating_medium__iN6Ty img');

        $totalReviews = str_replace(" total", "", $totalReviews);
        $totalReviews = str_replace(" i alt", "", $totalReviews);
        $totalReviews = str_replace("Insgesamt", "", $totalReviews);
        $reviewItems = array();
        $aggregateRating = '';
        $trustscore = '';
        foreach ($fullRating as $rating) {
            $rating = $rating->nodeValue;

            $sql = "SELECT aggregateNumber FROM totalReviews WHERE lang = '" . $lang . "'";
            $result = $db->query($sql);
            $row = $result->fetch_assoc();

            if (strpos($url, 'location/langballigau') === false) {
                $trustscore = $rating;
            } else {
                $trustscore = $row['aggregateNumber'];
            }

            $totalStars = $totalStars->attr('src');

            if (strpos($url, 'location/langballigau') === false) {
                $aggregateRating .= '<img src="' . $totalStars . '" alt="Rated to ' . $rating . '" class="trustRating">';

                $aggregateRatingMetaInfo = '
                <div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                    <meta itemprop="ratingValue" content="' . $rating . '">
                    <meta itemprop="reviewCount" content="' . $totalReviews . '">
                    <meta itemprop="starRating" content="' . $rating . '">
                    <meta itemprop="name" content="Cykelfærgen Flensborg Fjord">
                </div>
            ';
            } else {
                $aggregateRating .= '<img src="' . $totalStars . '" alt="Rated to ' . $rating . '" class="trustRating">';

                $aggregateRatingMetaInfo = $row['aggregateRatingMeta'];
            }


            /* echo $aggregateRating; */
            /* array_push($reviewItems, $itemRating); */
        }

        $userName = $xpath->query('//span[@data-consumer-name-typography="true"]');
        $author = $userName->item(0)->nodeValue;

        $author = htmlentities($author);

        $userName = $xpath->query('//a[@data-consumer-profile-link="true"]');
        foreach ($userName as $authorElement) {
            // Add a class property to the author element
            /* $authorElement->setAttribute('itemprop', 'author');
            $authorElement->setAttribute('itemscope', '');
            $authorElement->setAttribute('itemtype', 'https://schema.org/Person'); */

            $authorElement->setAttribute('target', '_blank');
        }

        $usersRating = $xpath->query('//div[@class="styles_reviewHeader__iU9Px"]');

        foreach ($usersRating as $userRating) {

            if (strpos($url, 'location/langballigau') === false) {
                $rating = $userRating->getAttribute('data-service-review-rating');
            } else {
                $sql = "SELECT aggregateRating FROM reviews WHERE lang = '" . $lang . "'";
                $result = $db->query($sql);
                $row = $result->fetch_assoc();
                $rating = $row['aggregateRating'];
            }

            $headline = $xpath->query('//h2[@data-service-review-title-typography="true"]')->item(0)->nodeValue;
            $reviewBody = $xpath->query('//p[@data-service-review-text-typography="true"]')->item(0)->nodeValue;

            $datePublished = $userRating->firstElementChild->nextElementSibling->firstChild->getAttribute('datetime');

            // Add a class property to the author element
            $metaRatingReview = "<div itemprop='review' itemscope itemtype='https://schema.org/Review'>";
            $metaRatingReview .= "<div itemprop='author' itemscope itemtype='https://schema.org/Person'>";
            $metaRatingReview .= "<meta itemprop='name' content='" . $author . "'>";
            $metaRatingReview .= "</div>";
            $metaRatingReview .= "<meta itemprop='headline' content='" . htmlentities($headline) . "'>";
            $metaRatingReview .= "<meta itemprop='reviewBody' content='" . htmlentities($reviewBody) . "'>";
            $metaRatingReview .= "<meta itemprop='datePublished' content='" . $datePublished . "'>";
            $metaRatingReview .= "<meta itemprop='itemReviewed' content='" . htmlentities("Cykelfærgen Flensborg Fjord") . "'>";
            $metaRatingReview .= "</div>";

            if (strpos($url, 'location/langballigau') === false) {
                $metaRating = "<div itemprop='reviewRating' itemscope itemtype='https://schema.org/Rating'>";
                $metaRating .= "<meta itemprop='ratingValue' content='" . $rating . "'>";
                $metaRating .= "<meta itemprop='bestRating' content='5'>";
                $metaRating .= "<meta itemprop='starRating' content='" . $rating . "'>";
                $metaRating .= "</div>";
            } else {
                $metaRating = "<div itemprop='reviewRating' itemscope itemtype='https://schema.org/Rating'>";
                $metaRating .= "<meta itemprop='ratingValue' content='" . $rating . "'>";
                $metaRating .= "<meta itemprop='bestRating' content='5'>";
                $metaRating .= "<meta itemprop='starRating' content='" . $rating . "'>";
                $metaRating .= "</div>";
            }

            array_push($reviewItems, $metaRatingReview);
            array_push($reviewItems, $metaRating);
        }

        $userName = $xpath->query('//span[@data-consumer-name-typography="true"]');
        $headline = $xpath->query('//h2[@data-service-review-title-typography="true"]')->item(0)->nodeValue;
        $reviewBody = $xpath->query('//p[@data-service-review-text-typography="true"]')->item(0)->nodeValue;
        $writtenDate = $xpath->query('//time[@data-service-review-date-time-ago="true"]')->item(0)->getAttribute('datetime');
        // Find the div element with the class star-rating_medium__iN6Ty
        $reviewStars = $xpath->query('//div[@class="styles_reviewHeader__iU9Px"]');
        // Convert reviewStars to string
        $reviewStars = $dom->saveHTML($reviewStars->item(0));
        // Get only the image tag from the reviewStars string
        $reviewStars = substr($reviewStars, strpos($reviewStars, '<img'));
        // Remove the rest of the string after the image tag
        $reviewStars = substr($reviewStars, 0, strpos($reviewStars, '>') + 1);

        // Get the button element with the data-review-label-tooltip-trigger attribute and get only its child div element
        $verified = $xpath->query('//button[@data-review-label-tooltip-trigger]')->item(0)->firstChild;
        // Convert the verified to string
        $verified = $verified->nodeValue;

        // Check the database if the review already exists in reviewsArchive table
        $check = $db->query("SELECT * FROM reviewsArchive WHERE title = '" . htmlentities($headline) . "' AND body = '" . htmlentities($reviewBody) . "' AND author = '" . $author . "' AND writtenDate = '" . $writtenDate . "' AND lang = '" . $lang . "'");
        $num = $check->num_rows;

        if ($num == 0) {
            $saveIntoTheDB = "INSERT INTO reviewsArchive (title, body, starImage, author, writtenDate, lang, `status`, verified) VALUES ('" . htmlentities($headline) . "', '" . htmlentities($reviewBody) . "', '" . $reviewStars . "', '" . $author . "', '" . $writtenDate . "', '" . $lang . "', '" . $verified . "', '" . $verified . "')";
            $db->query($saveIntoTheDB);
        }

        /* Review Item */
        $review = $xpath->query('//div[@class="styles_reviewCardInner__EwDq2"]');
        foreach ($review as $reviewItem) {
            // Add a class property to the review element
            /* $reviewItem->setAttribute('itemscope', '');
            $reviewItem->setAttribute('itemtype', 'https://schema.org/Review');
            $reviewItem->setAttribute('itemprop', 'review');
            $reviewItem->setAttribute('itemprop', 'reviewBody'); */

            /* Append item rating and metaRating divs */
            if (count($reviewItems) > 0) {
                foreach ($reviewItems as $itemRating) {
                    /* Convert string to DOM Element */
                    $domItemRating = new DOMDocument();
                    $domItemRating->loadHTML($itemRating, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    $nodeToImport = $dom->importNode($domItemRating->documentElement, true);
                    $reviewItem->appendChild($nodeToImport);
                }
            }
        }

        $dom->saveHTML();
        /* Convert the new saved html to string */
        $html = $dom->saveHTML();

        $modifyedHtml = str_replace("styles_reviewCardInner__EwDq2", "styles_reviewCardInner__EwDq2 reviewCard", $html);
        $modifyedHtml = str_replace('target="_self', 'target="_blank', $modifyedHtml);
        $modifyedHtml = str_replace('href="/', 'href="https://www.trustpilot.com/', $modifyedHtml);
        /* Remove DOCTYPE, html, head and body elements from the new saved html but still keep the content */
        $modifyedHtml = preg_replace('/^.*<body[^>]*>|<\/body>.*$/is', '', $modifyedHtml);
        $modifyedHtml = preg_replace('/^.*<head[^>]*>|<\/head>.*$/is', '', $modifyedHtml);
        $modifyedHtml = preg_replace('/^.*<html[^>]*>|<\/html>.*$/is', '', $modifyedHtml);
        $modifyedHtml = preg_replace('/^.*<!DOCTYPE[^>]*>|<html[^>]*>|<head[^>]*>|<body[^>]*>|<\/body>|<\/head>|<\/html>.*$/is', '', $modifyedHtml);

        echo $modifyedHtml;

        $delete = $db->query("DELETE FROM totalReviews WHERE lang = '" . $lang . "'");
        $insertTotalReviews = "INSERT INTO totalReviews (lang,total_reviews, aggregateRatingMeta, aggregateNumber) VALUES ('" . $lang . "','" . $totalReviewsPlus . "', '" . htmlentities($aggregateRatingMetaInfo) . "', '" . $trustscore . "')";
        $db->query($insertTotalReviews);

        $check = $db->query("SELECT * FROM reviews WHERE review = '" . htmlentities($modifyedHtml) . "'");
        if ($check->num_rows > 0) {
            continue;
        } else {
            $sql = "INSERT INTO reviews (review, lang, author, aggregateRating, writtenDate) VALUES ('" . htmlentities($modifyedHtml) . "', '" . $lang . "', '" . $author . "', '" . htmlentities($aggregateRating) . "', '" . $writtenDate . "')";
            $db->query($sql);
        }
    }
} else if (isset($_GET["google"])) {
    /* Getting all reviews from Google including accpeting cookies before loading */
    $lang = "de-DE";

    $options = array(
        'google_maps_review_cid' => '9389758487399257383', // Customer Identification (CID)
        'ucbcb' => '1',
        'show_only_if_with_text' => false, // true = show only reviews that have text
        'show_only_if_greater_x' => 0,     // (0-4) only show reviews with more than x stars
        'show_rule_after_review' => true,  // false = don't show <hr> Tag after each review (and before first)
        'show_blank_star_till_5' => true,  // false = don't show always 5 stars e.g. ⭐⭐⭐☆☆
        'your_language_for_tran' => 'de',  // give you language for auto translate reviews  
        'sort_by_reating_best_1' => true,  // true = sort by rating (best first)
        'show_cname_as_headline' => true,  // true = show customer name as headline
        'show_age_of_the_review' => true,  // true = show the age of each review
        'show_txt_of_the_review' => true,  // true = show the text of each review
        'show_author_of_reviews' => true,  // true = show the author of each review
        'your_lansort_by_reating_best_1guage_for_tran' => $lang,
    );

    echo getReviews($options);
}
