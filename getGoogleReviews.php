<?php
$options = array(
    'google_maps_review_cid' => '9389758487399257383', // Customer Identification (CID)
    'show_only_if_with_text' => false, // true = show only reviews that have text
    'show_only_if_greater_x' => 3,     // (0-4) only show reviews with more than x stars
    'show_rule_after_review' => true,  // false = don't show <hr> Tag after each review (and before first)
    'show_blank_star_till_5' => true,  // false = don't show always 5 stars e.g. ⭐⭐⭐☆☆
    'your_language_for_tran' => 'de',  // give you language for auto translate reviews  
    'sort_by_reating_best_1' => true,  // true = sort by rating (best first)
    'show_cname_as_headline' => true,  // true = show customer name as headline
    'show_age_of_the_review' => true,  // true = show the age of each review
    'show_txt_of_the_review' => true,  // true = show the text of each review
    'show_author_of_reviews' => true,  // true = show the author of each review
    'your_lansort_by_reating_best_1guage_for_tran' => 'de-DE',
);


function getReviews($option)
{
    $ch = curl_init('https://www.google.com/maps?cid=' . $option['google_maps_review_cid']);                                                               /* GOOGLE REVIEWS BY cURL */
    if (isset($option['your_language_for_tran']) and !empty($option['your_language_for_tran'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Language: ' . $option['your_language_for_tran']));
    }
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $error = curl_error($ch);                                                                                                                         /* ERROR HANDLING */
    $result = curl_exec($ch);
    curl_close($ch);                                                                                                                                     /* </cURL END> */
    $pattern = '/window\.APP_INITIALIZATION_STATE(.*);window\.APP_FLAGS=/ms';                                                                            /* REVIEW REGEX PATTERN */
    if (preg_match($pattern, $result, $match)) {                                                                                                       /* CHECK IF REVIEWS FOUND */
        $match[1] = trim($match[1], ' =;');                                                                                                                /* DIRTY JSON FIX */
        $reviews  = json_decode($match[1]);                                                                                                                /* 2. JSON DECODE */
        $reviews  = ltrim($reviews[3][6], ")]}'");                                                                                                         /* DIRTY JSON FIX */
        $reviews  = json_decode($reviews);

        /* Print array of reviews structured */
        /* 2. JSON DECODE */
        $customer = $reviews[6][11];                                                                                                                       /* POSITION OF REVIEWS */
        $reviews  = $reviews[6][175][9][0][0][0][0][2][15][0];                                                                                                                    /* POSITION OF REVIEWS */
    } else {
        echo $result;                                                                                                                          /* NO REVIEWS FOUND */
    }                                                                                                                                                    /* END CHECK */
    $return = '';                                                                                                                                        /* INI VAR */
    if (isset($reviews)) {                                                                                                                               /* CHECK REVIEWS */
        if (isset($option['sort_by_reating_best_1']) and $option['your_lansort_by_reating_best_1guage_for_tran'] == true)                                /* CHECK SORT */
            array_multisort(array_map(function ($element) {
                return $element[4];
            }, $reviews), SORT_DESC, $reviews);                                           /* SORT */
        $return .= '<div class="quote">';                                                                                                                  /* OPEN DIV */
        if (isset($option['show_cname_as_headline']) and $option['show_cname_as_headline'] == true) $return .= '<strong>' . $customer . '</strong><br>';       /* CUSTOMER */
        if (isset($option['show_rule_after_review']) and $option['show_rule_after_review'] == true) $return .= '<hr size="1">';                            /* RULER */
        foreach ($reviews as $review) {                                                                                                                    /* START LOOP */
            if (isset($option['show_only_if_with_text']) and $option['show_only_if_with_text'] == true and empty($review[3])) continue;                      /* CHECK TEXT */
            if (isset($option['show_only_if_greater_x']) and $review[4] <= $option['show_only_if_greater_x']) continue;                                      /* CHECK RATING */
            for ($i = 1; $i <= $review[4]; ++$i) $return .= '⭐';                                                                                              /* RATING */
            if (isset($option['show_blank_star_till_5']) and $option['show_blank_star_till_5'] == true) for ($i = 1; $i <= 5 - $review[4]; ++$i) $return .= '☆'; /* RATING */
            $return .= '<br>';                                                                                                                               /* NEWLINE */
            if (isset($option['show_txt_of_the_review']) and $option['show_txt_of_the_review'] == true) $return .= $review[3] . '<br>';                        /* TEXT */
            if (isset($option['show_age_of_the_review']) and $option['show_age_of_the_review'] == true) $return .= '<small>' . $review[0][1] . ' </small>';      /* AUTHOR */
            if (
                isset($option['show_age_of_the_review']) and $option['show_age_of_the_review'] == true and                                                   /* IF AUTHOR & AGE */
                isset($option['show_age_of_the_review']) and $option['show_age_of_the_review'] == true
            ) $return .= '<small> &mdash; </small>';               /* PRINT — */
            if (isset($option['show_age_of_the_review']) and $option['show_age_of_the_review'] == true) $return .= '<small>' . $review[1] . ' </small>';         /* AGE */
            if (isset($option['show_rule_after_review']) and $option['show_rule_after_review'] == true) $return .= '<hr size="1">';                          /* RULER */
        }                                                                                                                                                  /* END LOOP */
        $return .= '</div>';                                                                                                                               /* CLOSE DIV */
    }                                                                                                                                                    /* CHECK REVIEWS */
    return $return;                                                                                                                                      /* RETURN DATA */
}
