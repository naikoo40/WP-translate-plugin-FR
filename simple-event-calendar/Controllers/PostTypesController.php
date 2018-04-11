<?php

namespace GDCalendar\Controllers;

use GDCalendar\GDCalendar;
use GDCalendar\Models\PostTypes\Calendar;
use GDCalendar\Models\PostTypes\Event;
use GDCalendar\Models\PostTypes\Organizer;
use GDCalendar\Models\PostTypes\Venue;

class PostTypesController
{
    public static function run()
    {
        if(!get_option('gd_calendar_default_calendar')){
            update_option('gd_calendar_default_calendar', 0);
        }
        if(!get_option('gd_calendar_default_event')){
            update_option('gd_calendar_default_event', 0);
        }
        if(!get_option('gd_calendar_default_venue')){
            update_option('gd_calendar_default_venue', 0);
        }
        if(!get_option('gd_calendar_default_organizer')){
            update_option('gd_calendar_default_organizer', 0);
        }

        self::createPostTypes();
        flush_rewrite_rules();

    }

    public static function createPostTypes()
    {
        self::customPostCalendars();
        self::customPostOrganizers();
        self::customPostVenues();
        self::customPostEvents();
        self::registerTaxonomyCategory();
        self::registerTaxonomyTag();
        add_action( 'init', array(__CLASS__, 'featuredImages'));
        add_action('trashed_post', array(__CLASS__, 'removeDefaultCalendar'));
        add_action('trashed_post', array(__CLASS__, 'removeDefaultEvent'));
        add_action('trashed_post', array(__CLASS__, 'removeDefaultVenue'));
        add_action('trashed_post', array(__CLASS__, 'removeDefaultOrganizer'));
    }

    /**
     * Print Calendar Menu
     */

    public static function customPostEvents() {
        register_post_type(Event::get_post_type(),
            array(
                'labels' => array(
                    'name' => __('Événements', 'gd-calendar'),
                    'singular_name' => __( 'Événement', 'gd-calendar' ),
                    'all_items' => __('Événements', 'gd-calendar'),
                    'add_new' => __('Ajouter un nouvel événement', 'gd-calendar'),
                    'add_new_item' => __('Ajouter nouvel evenement', 'gd-calendar'),
                    'new_item' => __('Nouvel evenement', 'gd-calendar'),
                    'edit_item' => __('Editer evenement', 'gd-calendar'),
                    'view_item' => __('Voir événement', 'gd-calendar'),
                    'view_items' => __('Voir événements', 'gd-calendar'),
                    'search_items' => __('Recherche evenements', 'gd-calendar'),
                    'not_found' => __('Aucuns événements trouvés', 'gd-calendar'),
                    'not_found_in_trash' => __('Aucuns événements trouvés dans la corbeille', 'gd-calendar'),
                ),
                'public' => true,
                'has_archive' => true,
                'menu_icon'   => \GDCalendar()->pluginUrl() . "/resources/assets/images/calendar_shortcode.png",
                'rewrite' => array('slug' => 'events'),
                'menu_position' => 80,
                'supports' => array('title', 'editor', 'excerpt')
            )
        );

        self::addDefaultEvent();
    }

    /**
     * Add default Event
     */

    public static function addDefaultEvent(){

        $title  = 'Mon premier événement';
        $status = get_option("gd_calendar_default_event");

        if (!get_page_by_title($title, OBJECT, 'gd_events') && $status == 0) :
            $post_data = array(
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_type'   => 'gd_events',
            );

            $post_id = wp_insert_post($post_data);
            $event = new Event($post_id);

            $event->set_start_date(date("d/m/Y h:i a"))
                ->set_end_date(date("d/m/Y h:i a", strtotime("tomorrow")))
                ->set_event_venue(self::addDefaultVenue())
                ->set_event_organizer(array(self::addDefaultOrganizer()));
            $event->save();
        endif;
    }

    public static function removeDefaultEvent(){
        global $post_type;
        if ( $post_type === Event::get_post_type()) {

            update_option('gd_calendar_default_event', 1);
        }
    }

    /**
     * Register taxonomies
     */

    public static function registerTaxonomyCategory()
    {
        $labels = array(
            'name'              => _x('Catégorie des événements', 'categories', 'gd-calendar'),
            'singular_name'     => _x('Catégorie des événement', 'category', 'gd-calendar'),
            'search_items'      => __('Recherche de catégories', 'gd-calendar'),
            'all_items'         => __('Toutes les catégories', 'gd-calendar'),
            'parent_item'       => __('Catégories parentes', 'gd-calendar'),
            'parent_item_colon' => __('Catégories parentes:', 'gd-calendar'),
            'edit_item'         => __('Editer catégories', 'gd-calendar'),
            'update_item'       => __('Update catégories', 'gd-calendar'),
            'add_new_item'      => __('Ajouter nouvelle catégorie', 'gd-calendar'),
            'new_item_name'     => __('Nom de la nouvelle catégorie', 'gd-calendar'),
            'menu_name'         => __('Catégorie des événement', 'gd-calendar'),
        );
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'event-category'),
        );
        register_taxonomy('event_category', 'gd_events', $args);
    }

    public static function registerTaxonomyTag()
    {
        $labels = array(
            'name'              => _x('Événements Tags', 'tags', 'gd-calendar'),
            'singular_name'     => _x('Événements Tag', 'tag', 'gd-calendar'),
            'search_items'      => __('Recherche de Tags', 'gd-calendar'),
            'all_items'         => __('Tous les Tags', 'gd-calendar'),
            'parent_item'       => __('Parent Tag', 'gd-calendar'),
            'parent_item_colon' => __('Parent Tag:', 'gd-calendar'),
            'edit_item'         => __('Edition de Tag', 'gd-calendar'),
            'update_item'       => __('Update Tag', 'gd-calendar'),
            'add_new_item'      => __('Ajouter un nouveau Tag', 'gd-calendar'),
            'new_item_name'     => __('Nom du nouveau tag', 'gd-calendar'),
            'menu_name'         => __('Événements des tags', 'gd-calendar'),
        );
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'event-tag'),
        );
        register_taxonomy('event_tag', 'gd_events', $args);
    }

    /**
     * Register post types
     */

    public static function customPostCalendars() {
        register_post_type(Calendar::get_post_type(),
            array(
                'labels' => array(
                    'name' => __('Calendriers', 'gd-calendar'),
                    'singular_name' => __( 'Calendrier', 'gd-calendar' ),
                    'all_items' => __('Calendriers', 'gd-calendar'),
                    'add_new' => __('Ajouter nouveau calendrier', 'gd-calendar'),
                    'add_new_item' => __('Ajouter nouveau calendrier', 'gd-calendar'),
                    'edit_item' => __('Edition du calendrier', 'gd-calendar'),
                    'new_item' => __('Nouveau calendrier', 'gd-calendar'),
                    'view_item' => __('Voir calendrier', 'gd-calendar'),
                    'view_items' => __('Voir calendriers', 'gd-calendar'),
                    'search_items' => __('Rechercher un calendrier', 'gd-calendar'),
                    'not_found' => __('Aucuns calendriers trouvés', 'gd-calendar'),
                    'not_found_in_trash' => __('Aucuns calendriers trouvés dans la corbeille', 'gd-calendar'),
                ),
                'public' => true,
                'has_archive' => true,
                'rewrite' => array('slug' => 'calendar'),
                'show_in_menu' => 'edit.php?post_type=gd_events',
                'supports' => 'title',
            )
        );

        self::addDefaultCalendar();
    }

    /**
     * Add default Calendar
     */

    public static function addDefaultCalendar(){

        $title  = 'My First Calendar';
        $status = get_option("gd_calendar_default_calendar");

        if (!get_page_by_title($title, OBJECT, 'gd_calendar') && $status == 0) :
            $post_data = array(
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_type'   => 'gd_calendar',
            );

            $post_id = wp_insert_post($post_data);
            $calendar = new Calendar($post_id);
            $calendar->set_view_type(array(0, 1))
                ->set_theme(0);
            $calendar->save();
        endif;
    }

    public static function removeDefaultCalendar(){
        global $post_type;
        if ( $post_type === Calendar::get_post_type()) {

            update_option('gd_calendar_default_calendar', 1);
        }
    }

    public static function customPostOrganizers() {
        register_post_type(Organizer::get_post_type(),
            array(
                'labels' => array(
                    'name' => __('Organisateurs', 'gd-calendar'),
                    'singular_name' => __( 'Organisateur', 'gd-calendar' ),
                    'all_items' => __('Organisateurs', 'gd-calendar'),
                    'add_new' => __('Ajouter un nouvel organisateur', 'gd-calendar'),
                    'add_new_item' => __('Ajouter un nouvel organisateur', 'gd-calendar'),
                    'edit_item' => __('Editer un organisateur', 'gd-calendar'),
                    'new_item' => __('Nouvel organisateur', 'gd-calendar'),
                    'view_item' => __('Voir un organisateur', 'gd-calendar'),
                    'view_items' => __('Voir les organisateurs', 'gd-calendar'),
                    'search_items' => __('Rechercher un organisateur', 'gd-calendar'),
                    'not_found' => __('Aucuns organisateurs trouvés', 'gd-calendar'),
                    'not_found_in_trash' => __('Aucuns organisateurs trouvés dans la corbeille', 'gd-calendar'),
                ),
                'public' => true,
                'has_archive' => true,
                'rewrite' => array('slug' => 'organizer'),
                'show_in_menu' => 'edit.php?post_type=gd_events',
                'supports' => array('title'),
            )
        );
    }

    /**
     * Add default Organizer
     */

    public static function addDefaultOrganizer(){

        $title  = 'My First Organizer';
        $status = get_option("gd_calendar_default_organizer");
        $post_id = '';

        if (!get_page_by_title($title, OBJECT, 'gd_organizers') && $status == 0) :
            $post_data = array(
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_type'   => 'gd_organizers',
            );

            $post_id = wp_insert_post($post_data);
            $organizer = new Organizer($post_id);
            $organizer->set_organized_by('John Smith')
                ->set_organizer_address('Centre de conventions de Los Angeles, South Figueroa Street, Los Angeles, Californie')
                ->set_phone('+12016543210')
                ->set_website('http://grandwp.com')
                ->set_organizer_email('admin@grandwp.com');
            $organizer->save();
        endif;

        return $post_id;
    }

    public static function removeDefaultOrganizer(){
        global $post_type;
        if ( $post_type === Organizer::get_post_type()) {

            update_option('gd_calendar_default_organizer', 1);
        }
    }

    public static function customPostVenues() {
        register_post_type('gd_venues',
            array(
                'labels' => array(
                    'name' => __('Localisations', 'gd-calendar'),
                    'singular_name' => __( 'Localisation', 'gd-calendar' ),
                    'all_items' => __('Localisations', 'gd-calendar'),
                    'add_new' => __('Ajouter une nouvelle Localisation', 'gd-calendar'),
                    'add_new_item' => __('Ajouter une nouvelle Localisation', 'gd-calendar'),
                    'edit_item' => __('Editer une localisation', 'gd-calendar'),
                    'new_item' => __('Nouvelle localisation', 'gd-calendar'),
                    'view_item' => __('Voir localisation', 'gd-calendar'),
                    'view_items' => __('Voir localisations', 'gd-calendar'),
                    'search_items' => __('Rechercher une localisation', 'gd-calendar'),
                    'not_found' => __('Aucunes localisations trouvés', 'gd-calendar'),
                    'not_found_in_trash' => __('Aucunes localisations trouvés dans la corbeille', 'gd-calendar'),
                ),
                'public' => true,
                'has_archive' => true,
                'rewrite' => array('slug' => 'venue'),
                'show_in_menu' => 'edit.php?post_type=gd_events',
                'supports' => 'title',
            )
        );
    }

    /**
     * Add default Venue
     */

    public static function addDefaultVenue(){

        $title  = 'My First Venue';
        $status = get_option("gd_calendar_default_venue");
        $post_id = '';

        if (!get_page_by_title($title, OBJECT, 'gd_venues') && $status == 0) :
            $post_data = array(
                'post_title'  => $title,
                'post_status' => 'publish',
                'post_type'   => 'gd_venues',
            );
            $post_id = wp_insert_post($post_data);
            $venue = new Venue($post_id);
            $venue->set_address('1201 South Figueroa Street, Los Angeles, California 90015, United States')
                ->set_latitude('34.0413606')
                ->set_longitude('-118.2697771');
            $venue->save();
        endif;

        return $post_id;
    }

    public static function removeDefaultVenue(){
        global $post_type;
        if ( $post_type === Venue::get_post_type()) {
            update_option('gd_calendar_default_venue', 1);
        }
    }

    public static function featuredImages()
    {
        add_post_type_support( 'gd_calendar', 'thumbnail' );
        add_post_type_support( 'gd_events', 'thumbnail' );
        add_post_type_support( 'gd_venues', 'thumbnail' );
        add_post_type_support( 'gd_organizers', 'thumbnail' );
    }

}
