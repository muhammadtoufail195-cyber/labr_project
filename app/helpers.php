<?php

if (!function_exists('money')) {
    function money($amount) {
        return number_format((float)$amount, 2);
    }
}
