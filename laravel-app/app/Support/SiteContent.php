<?php

namespace App\Support;

use App\SiteSetting;

/**
 * Editable front-end content. Values are stored in site_settings under the
 * "content." prefix. The schema() drives the admin editor and the defaults
 * keep the public site unchanged until an admin overrides a field.
 */
class SiteContent
{
    /** Raw stored value for a content key, or the given default. */
    public static function get($key, $default = '')
    {
        $val = SiteSetting::getValue('content.' . $key, null);

        return ($val === null || $val === '') ? $default : $val;
    }

    public static function text($key, $default = '')
    {
        return self::get($key, $default);
    }

    public static function html($key, $default = '')
    {
        return self::get($key, $default);
    }

    /** Resolve an image field to a usable URL, falling back to the default. */
    public static function image($key, $default = '')
    {
        $val = SiteSetting::getValue('content.' . $key, null);
        if (! $val) {
            return $default;
        }
        // Absolute URLs / root-relative paths are returned as-is.
        if (preg_match('#^(https?:)?//#', $val) || strpos($val, '/') === 0) {
            return $val;
        }

        return url('public/' . ltrim($val, '/'));
    }

    /** Persist a scalar content value. */
    public static function put($key, $value)
    {
        SiteSetting::setValue('content.' . $key, $value);
    }

    /**
     * Editable page schema. Each page: label, url, and fields keyed by name.
     * Field: [type, label, default]. type in {text, textarea, html, image}.
     */
    public static function schema()
    {
        return [
            'home' => [
                'label' => 'Home',
                'url' => '/',
                'fields' => [
                    'hero_title'            => ['html', 'Hero title (HTML allowed)', 'A destination. A community. An <span class="text-brand-gold">experience</span>.'],
                    'hero_subtitle'         => ['textarea', 'Hero subtitle', 'Welcome 2 Kigali Expats Club — live, connect, and thrive in Rwanda.'],
                    'hero_image'            => ['image', 'Hero background image', '/branding/w2k-landing.jpg'],
                    'cta_primary'           => ['text', 'Join the Club button', 'Join the Club'],
                    'cta_events'            => ['text', 'Events button', 'Events'],
                    'cta_cafe'              => ['text', 'Cafe menu button', 'Cafe Menu'],
                    'services_heading'      => ['text', 'Spaces heading', 'Restaurant. Lounge. Events. Cafe.'],
                    'services_subheading'   => ['text', 'Spaces subheading', 'A premium hospitality destination for the international community in Kigali'],
                    'why_heading'           => ['text', 'Why-us heading', 'Live. Connect. Thrive.'],
                    'why_subheading'        => ['text', 'Why-us subheading', 'The pillars of the Welcome 2 Kigali experience'],
                    'industries_heading'    => ['text', 'Pillars heading', 'What we stand for'],
                    'industries_subheading' => ['text', 'Pillars subheading', 'Hospitality, urban culture, and a place to belong'],
                    'testimonials_heading'  => ['text', 'Cafe heading', 'Cafe & Restaurant'],
                    'testimonials_subheading' => ['text', 'Cafe subheading', 'Crafted beverages and food — dine in or take away'],
                    'cta_heading'           => ['text', 'Bottom CTA heading', 'Experience Rwanda. Belong in Kigali.'],
                    'cta_text'              => ['textarea', 'Bottom CTA text', 'Register for membership, join our events, or visit us at the club.'],
                ],
            ],
            'about' => [
                'label' => 'About',
                'url' => '/about',
                'fields' => [
                    'hero_title'        => ['text', 'Hero title', 'More than a logo. A destination experience.'],
                    'hero_subtitle'     => ['textarea', 'Hero subtitle', 'A place where people arrive, connect, discover Rwanda, experience culture, build relationships, and create memories.'],
                    'vision_heading'    => ['text', 'Vision heading', 'Our Vision'],
                    'vision_text'       => ['textarea', 'Vision text', 'To be Kigali’s leading expat community where people from diverse backgrounds connect, belong, and thrive together.'],
                    'vision_image'      => ['image', 'Vision photo (shown next to the vision)', ''],
                    'mission_heading'   => ['text', 'Mission heading', 'Our Mission'],
                    'mission_text'      => ['textarea', 'Mission text', 'To create a welcoming lifestyle community that brings expatriates and locals together through meaningful connections, quality experiences, social events, hospitality, and opportunities to experience the best of Rwanda.'],
                    'about_image'       => ['image', 'Mission photo (shown next to the mission)', ''],
                    'leadership_heading' => ['text', 'Leadership heading', 'Our Leadership'],
                    'leadership_subtext' => ['text', 'Leadership subtext', 'The people hosting Welcome 2 Kigali'],
                    'values_heading'    => ['text', 'Core values heading', 'The identity combines'],
                    'cta_heading'       => ['text', 'CTA heading', 'Ready to belong in Kigali?'],
                    'cta_text'          => ['text', 'CTA text', 'Join the club, come to an event, or visit the cafe.'],
                ],
            ],
            'services' => [
                'label' => 'Services',
                'url' => '/services',
                'fields' => [
                    'kicker'            => ['text', 'Kicker', 'Welcome 2 Kigali'],
                    'heading'           => ['text', 'Heading', 'Our services'],
                    'intro'             => ['textarea', 'Intro', 'Everything you need in one place. Members get 10% off.'],
                    'supermarket'       => ['text', 'Supermarket title', 'Convenience store & supermarket'],
                    'supermarket_body'  => ['textarea', 'Supermarket text', 'Daily groceries, drinks, snacks, toiletries and household items, open when you need them.'],
                    'restaurant'        => ['text', 'Bar & restaurant title', 'Bar & restaurant'],
                    'restaurant_body'   => ['textarea', 'Bar & restaurant text', 'Breakfast, lunch and dinner, cocktails, beers and wines in a relaxed lounge.'],
                    'coffee'            => ['text', 'Coffee shop title', 'Coffee shop'],
                    'coffee_body'       => ['textarea', 'Coffee shop text', 'Espresso, cappuccino, iced drinks, fresh juices and pastries.'],
                    'laundry'           => ['text', 'Laundry title', 'Laundry'],
                    'laundry_body'      => ['textarea', 'Laundry text', 'Washing, ironing and dry cleaning. Drop off and collect when it is ready.'],
                    'fashion'           => ['text', 'Fashion store title', 'Fashion store'],
                    'fashion_body'      => ['textarea', 'Fashion store text', 'Clothing, shoes and accessories for every occasion.'],
                ],
            ],
            'projects' => [
                'label' => 'Projects',
                'url' => '/projects',
                'fields' => [
                    'hero_title'    => ['html', 'Hero title (HTML allowed)', 'Our <span class="text-brand-gold">Projects</span>'],
                    'hero_subtitle' => ['text', 'Hero subtitle', 'See our engineering precision in action'],
                ],
            ],
            'contact' => [
                'label' => 'Contact',
                'url' => '/contact',
                'fields' => [
                    'heading'       => ['text', 'Page heading', 'Get in Touch'],
                    'intro'         => ['textarea', 'Intro text', 'Reach us on WhatsApp. Tap the number or send a message and we will reply there.'],
                    'office_name'   => ['text', 'Office name', 'Welcome 2 Kigali Expats Club'],
                    'office_line1'  => ['text', 'Office address line 1', 'Kigali'],
                    'office_line2'  => ['text', 'Office address line 2', 'Rwanda'],
                    'person_name'   => ['text', 'Contact person name', ''],
                    'person_role'   => ['text', 'Contact person role', ''],
                    'phone'         => ['text', 'Phone', ''],
                    'email'         => ['text', 'Email', 'info@welcome2kigali.net'],
                    'website'       => ['text', 'Website', 'www.welcome2kigali.net'],
                    'hours_weekday' => ['text', 'Business hours (Mon-Fri)', '9:00 AM - 6:00 PM'],
                    'hours_weekend' => ['text', 'Business hours (Sat-Sun)', 'Closed'],
                ],
            ],
            'gallery' => [
                'label' => 'Gallery',
                'url' => '/gallery',
                'fields' => [
                    'hero_title'    => ['html', 'Hero title (HTML allowed)', 'Our <span class="text-brand-gold">Gallery</span>'],
                    'hero_subtitle' => ['text', 'Hero subtitle', 'Events, gatherings, and moments from Welcome 2 Kigali'],
                ],
            ],
            'menu' => [
                'label' => 'Menu',
                'url' => '/menu',
                'fields' => [
                    'hero_title'    => ['html', 'Hero title (HTML allowed)', 'Cafe & <span class="text-brand-gold">Restaurant</span>'],
                    'hero_subtitle' => ['text', 'Hero subtitle', 'Coffee, tea, juices, smoothies, and food. Tap an item to add it.'],
                ],
            ],
            'events' => [
                'label' => 'Events',
                'url' => '/events',
                'fields' => [
                    'hero_title'    => ['html', 'Hero title (HTML allowed)', 'Club Events'],
                    'hero_subtitle' => ['text', 'Hero subtitle', 'Gatherings, culture nights, and community moments in Kigali.'],
                ],
            ],
            'reviews' => [
                'label' => 'Reviews',
                'url' => '/reviews',
                'fields' => [
                    'heading'      => ['text', 'Heading', 'How was your visit?'],
                    'intro'        => ['textarea', 'Intro', 'Score each part from 1 to 5. Your review helps the club.'],
                    'thanks_title' => ['text', 'Thank-you heading', 'Thank you'],
                    'thanks_body'  => ['textarea', 'Thank-you text', 'Your review has been received.'],
                ],
            ],
            'register' => [
                'label' => 'Become a Member',
                'url' => '/register-now',
                'fields' => [
                    'hero_title'    => ['text', 'Page heading', 'Become a Member'],
                    'hero_subtitle' => ['textarea', 'Page subtitle', 'Join Welcome 2 Kigali Expats Club and belong in Kigali.'],
                ],
            ],
            'footer' => [
                'label' => 'Footer',
                'url' => '/',
                'fields' => [
                    'services_heading' => ['text', 'Services heading', 'Services'],
                    'service_dining'   => ['text', 'Dining link label', 'Dining & Cafe'],
                    'service_lounge'   => ['text', 'Lounge link label', 'Lounge & Community'],
                    'contact_heading'  => ['text', 'Contact heading', 'Contact'],
                    'rights'           => ['text', 'Copyright line', 'All rights reserved.'],
                ],
            ],
        ];
    }

    public static function pageSchema($page)
    {
        $schema = self::schema();

        return $schema[$page] ?? null;
    }

    /** Keys for editable content pages (Home, About, …). */
    public static function contentTabItems()
    {
        $items = [];
        foreach (self::schema() as $key => $page) {
            $items[$key] = $page['label'];
        }

        return $items;
    }

    /** Saved order of content page tabs in Site Content admin. */
    public static function contentTabOrder()
    {
        return SiteMenu::ordered('content_tabs_order', self::contentTabItems());
    }

    /** Page schema keyed by page, sorted for the admin tab bar. */
    public static function orderedSchema()
    {
        $schema = self::schema();
        $ordered = [];
        foreach (self::contentTabOrder() as $key) {
            if (isset($schema[$key])) {
                $ordered[$key] = $schema[$key];
            }
        }
        foreach ($schema as $key => $page) {
            if (! isset($ordered[$key])) {
                $ordered[$key] = $page;
            }
        }

        return $ordered;
    }
}
