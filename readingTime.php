<?php 

/*
Plugin Name: Reading Time
Plugin URI: https://wordpress.org/plugins/ReadingTime
Description: Plugin that allow to know how much time you will spent reading the following text
Author: Yansli Mehdi
Version: 1.0
Author URI: http://mon-siteweb.com/
*/
 
function readingTime($post_id, $post, $update) {
	// Sinon la fonction se lance dès le clic sur "ajouter"
	if( ! $update ) {
		return;
	}
	
	// On ne veut pas executer le code lorsque c'est une révision
	if( wp_is_post_revision( $post_id ) ) {
		return;
	}
	
	// On évite les sauvegardes automatiques
	if( defined( 'DOING_AUTOSAVE' ) and DOING_AUTOSAVE ) {
		return;
	}
	
	// Seulement pour les articles
	if( $post->post_type != 'post' ) {
		return;
	}
	
	// Calculer le temps de lecture
	$word_count = str_word_count( strip_tags( $post->post_content ) );
	
    // Récupérer le type de public choisi dans les options
    $public_type = get_option('reading_time_public_type', 'adult');
    
    // Ajuster le nombre de mots par minute en fonction du type de public
    if ($public_type === 'adult') {
        $words_per_minute = 250; // Nombre de mots par minute pour le public adulte
    } elseif ($public_type === 'child') {
        $words_per_minute = 150; // Nombre de mots par minute pour le public enfant
    }
    
    // Recalculer le temps de lecture en utilisant le nouveau nombre de mots par minute
    $minutes = ceil($word_count / $words_per_minute);
	
	// On sauvegarde la meta
	update_post_meta( $post_id, 'readingTime', $minutes );

}
add_action('save_post', 'readingTime', 10, 3 );

// Fonction pour afficher le temps de lecture dans le contenu de l'article
function display_reading_time($title) {
    // if (is_singular('post')) { // S'assurer que l'on est sur une page d'article
        $reading_time = get_post_meta(get_the_ID(), 'readingTime', true);
        if ($reading_time) {
            $title .= ' | Temps de lecture : ' . $reading_time . ' minute(s)';
        // }
    }
    return $title;
}
add_filter('the_title', 'display_reading_time');


// Fonction pour vérifier si l'article appartient à un groupe d'âge spécifique
function is_in_age_group($post_id, $age_group) {
    // Ici, vous pouvez implémenter une logique pour déterminer si l'article appartient à un groupe d'âge spécifique
    // Par exemple, vous pouvez utiliser des catégories ou des balises spécifiques pour définir l'âge de l'article.
    // Retourne true si l'article appartient au groupe d'âge spécifié, sinon retourne false.
}

// Fonction pour vérifier si l'article appartient à une catégorie de contenu spécifique
function is_in_content_category($post_id, $content_category) {
    // Ici, vous pouvez implémenter une logique pour déterminer si l'article appartient à une catégorie de contenu spécifique
    // Par exemple, vous pouvez utiliser des catégories ou des balises spécifiques pour définir la catégorie de contenu de l'article.
    // Retourne true si l'article appartient à la catégorie de contenu spécifiée, sinon retourne false.
}


// Fonction pour calculer le temps de lecture pour tous les articles existants
function calculate_reading_time_for_all_posts() {

	
	$args = array(
		'post_type' => 'post',
        'posts_per_page' => -1,
    );
	
	// Seulement pour les articles

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            readingTime($post_id, $query->post, true);
        }
    }

    wp_reset_postdata();
}

// Action à déclencher une fois pour calculer le temps de lecture pour tous les articles existants
add_action('init', 'calculate_reading_time_for_all_posts');


// Fonction de rappel pour afficher le contenu de la section "Reading Time Settings"
function reading_time_settings_section_callback() {
    echo '<p>Choisissez le type de public pour le calcul du temps de lecture :</p>';
}

// Fonction de rappel pour afficher le champ de formulaire "Type de public"
function reading_time_public_type_callback() {
    $current_public_type = get_option('reading_time_public_type', 'adult'); // Valeur par défaut : adulte
    ?>
    <select name="reading_time_public_type">
        <option value="adult" <?php selected($current_public_type, 'adult'); ?>>Adulte</option>
        <option value="child" <?php selected($current_public_type, 'child'); ?>>Enfant</option>
        <option value="other" <?php selected($current_public_type, 'other'); ?>>Autre</option>
    </select>
    <?php
}

// Fonction pour enregistrer les paramètres
function reading_time_register_settings() {
    add_option('reading_time_public_type', 'adult'); // Valeur par défaut : adulte
    register_setting('reading_time_settings_group', 'reading_time_public_type');

		 // Ajout de la section "Reading Time Settings" dans la nouvelle page d'options du menu administrateur
		 add_menu_page('Reading Time Settings', 'Reading Time', 'manage_options', 'reading-time-settings', 'reading_time_options_page');
		 add_settings_section('reading_time_settings_section', 'Reading Time Settings', 'reading_time_settings_section_callback', 'reading-time-settings');
		 
		 // Ajout du champ de formulaire "Type de public"
		 add_settings_field('reading_time_public_type_field', 'Type de public', 'reading_time_public_type_callback', 'reading-time-settings', 'reading_time_settings_section');
		
}
add_action('admin_init', 'reading_time_register_settings');

// Fonction pour ajouter la page d'options et les paramètres
function reading_time_add_options_page() {
    add_options_page('Reading Time Settings', 'Reading Time', 'manage_options', 'reading-time-settings', 'reading_time_options_page');
}
add_action('admin_menu', 'reading_time_add_options_page');

// Fonction pour afficher le contenu de la page d'options
function reading_time_options_page() {
    // Vérifier les autorisations d'accès
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas l\'autorisation d\'accéder à cette page.'));
    }
    ?>
    <div class="wrap">
        <h1>Reading Time Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('reading_time_settings_group'); ?>
            <?php do_settings_sections('reading-time-settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

