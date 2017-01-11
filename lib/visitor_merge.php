<?php

// Group visits by person
function group_visits(&$list) {
    $o = [];
    foreach ($list as $a) {
        // Get list of already added visits
        $nick = $a['nick'];
        $visits = @$o[$nick] ?: [];
        unset($a['nick']);

        // Add to array
        array_push($visits, $a);
        $o[$nick] = $visits;
    }
    return $o;
}

// Merge overlapping visits by a person to a single visit
function merge_visits(&$raw_list) {
    // Group by person
    $list = group_visits($raw_list);
    
    foreach ($list as &$items) {
        // Sort by enter time
        usort($items, function (&$a, &$b) {
            return $a['enter'] > $b['enter'];
        });

        // Iterate items and merge if an item overlaps with previous
        $prev_i = NULL;
        foreach ($items as $i => &$item) {
            if ($prev_i === NULL) {
                // Do not process the first item
                $prev_i = $i;
            } else {
                if ($items[$prev_i]['leave'] < $item['enter']) {
                    // Lines are not overlapping, keep the item
                    $prev_i = $i;
                } else {
                    // Lines are overlapping, merge
                    $items[$prev_i]['leave'] = max($items[$prev_i]['leave'], $item['leave']);
                    unset($items[$i]);
                }
            }
        }
    }
    return $list;
}
