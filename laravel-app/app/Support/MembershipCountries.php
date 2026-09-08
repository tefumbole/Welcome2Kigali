<?php

namespace App\Support;

class MembershipCountries
{
    public static function priority()
    {
        return [
            'Rwanda',
            'Cameroon',
            'United Kingdom',
            'United States',
            'Uganda',
            'Tanzania',
            'Burundi',
            'DR Congo',
            'Kenya',
        ];
    }

    public static function others()
    {
        $all = [
            'Belgium', 'France', 'Germany', 'India', 'China', 'Nigeria', 'South Africa',
            'Canada', 'Australia', 'Netherlands', 'Italy', 'Spain', 'Switzerland',
            'Sweden', 'Norway', 'Denmark', 'Ireland', 'Portugal', 'Poland',
            'United Arab Emirates', 'Saudi Arabia', 'Turkey', 'Egypt', 'Morocco',
            'Ghana', 'Ethiopia', 'South Sudan', 'Zambia', 'Zimbabwe', 'Malawi',
            'Mozambique', 'Angola', 'Congo', 'Gabon', 'Senegal', 'Ivory Coast',
            'Japan', 'South Korea', 'Brazil', 'Mexico', 'Israel', 'Lebanon',
            'Pakistan', 'Bangladesh', 'Philippines', 'Indonesia', 'Thailand',
            'Ukraine', 'Russia', 'Romania', 'Greece', 'Austria',
        ];
        $priority = self::priority();
        $out = [];
        foreach ($all as $name) {
            if (! in_array($name, $priority, true)) {
                $out[] = $name;
            }
        }
        sort($out);

        return $out;
    }
}
