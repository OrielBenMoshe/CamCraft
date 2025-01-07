<?php
/**
 * Plugin Name: CamCraft
 * Description: תוסף לעריכת תמונות ממצלמה עם אלמנטים ופילטרים בזמן אמת.
 * Version: 1.0
 * Author: oriel.bm
 * Text Domain: camcraft
 * Domain Path: /languages
 */

// מניעת גישה ישירה
if ( !defined('ABSPATH') ) exit;


// הוספת סקריפטים וסגנונות
wp_enqueue_script('three-js', 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js', array(), null, true);
wp_enqueue_script('gltf-loader', 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js', array('three-js'), null, true);
wp_enqueue_script('orbit-controls', 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js', array('three-js'), null, true);

require_once plugin_dir_path(__FILE__) . 'includes/camcraft-models.php';

// הוספת רובריקה לתפריט הצדדי
function camcraft_add_admin_menu() {
    add_menu_page(
        __( 'CamCraft Editor', 'camcraft' ), // שם התפריט
        __( 'CamCraft', 'camcraft' ), // שם שיופיע בסרגל הצד
        'manage_options', // הרשאות
        'camcraft', // slug ייחודי
        'camcraft_admin_page', // פונקציה שתוצג
        'dashicons-camera', // אייקון
        6 // מיקום בתפריט
    );
}
add_action( 'admin_menu', 'camcraft_add_admin_menu' );

// דף התוסף
function camcraft_admin_page() {
    $template_path = plugin_dir_path( __FILE__ ) . 'includes/admin-page.php';

    if ( file_exists( $template_path ) ) {
        include $template_path;
    } else {
        echo '<div class="wrap"><p>' . __( 'שגיאה: קובץ התצוגה לא נמצא.', 'camcraft' ) . '</p></div>';
    }
}



// הרשמת סקריפטים וסגנונות
function camcraft_enqueue_scripts() {
    // לבדוק אם השורטקוד נמצא בתוכן
    if (is_singular() && has_shortcode(get_post()->post_content, 'camcraft_editor')) {
        wp_enqueue_script('camcraft-js', plugins_url('/assets/js/camcraft.js', __FILE__), array('jquery'), '1.0', true);
        wp_enqueue_style('camcraft-css', plugins_url('/assets/css/camcraft.css', __FILE__));
    }
}
add_action('wp_enqueue_scripts', 'camcraft_enqueue_scripts');



// יצירת שורטקוד [camcraft_editor]
function camcraft_editor_shortcode() {
    ob_start(); // להתחלת לכידה של פלט
    include plugin_dir_path(__FILE__) . 'templates/editor-template.php'; // טעינת התבנית
    return ob_get_clean(); // החזרת התוכן כטקסט
}
add_shortcode('camcraft_editor', 'camcraft_editor_shortcode');

// Enqueue scripts and styles for CamCraft admin page
function camcraft_admin_enqueue_scripts( $hook ) {
    // בדוק אם זהו הדף של CamCraft
    if ( 'toplevel_page_camcraft' !== $hook ) {
        return;
    }

    // טעינת CSS
    wp_enqueue_style(
        'camcraft-admin-style',
        plugins_url( 'assets/css/camcraft.css', __FILE__ ),
        array(),
        '1.0.0'
    );

    // טעינת JS
    wp_enqueue_script(
        'camcraft-admin-script',
        plugins_url( 'assets/js/camcraft.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', 'camcraft_admin_enqueue_scripts' );




// AJAX שמירת תמונה
add_action('wp_ajax_save_camera_image', 'save_camera_image');
add_action('wp_ajax_nopriv_save_camera_image', 'save_camera_image');

function save_camera_image() {
    // בדיקה אם הקובץ קיים
    if (empty($_FILES['image'])) {
        wp_send_json_error('לא התקבלה תמונה.');
    }

    $file = $_FILES['image'];
    $upload = wp_handle_upload($file, ['test_form' => false]);

    // טיפול בשגיאות העלאה
    if (isset($upload['error'])) {
        wp_send_json_error($upload['error']);
    }

    // הוספת הקובץ למדיה של וורדפרס
    $attachment_id = wp_insert_attachment([
        'guid' => $upload['url'],
        'post_mime_type' => $upload['type'],
        'post_title' => sanitize_file_name($file['name']),
        'post_content' => '',
        'post_status' => 'inherit',
    ], $upload['file']);

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    // החזרת תשובה ל-AJAX
    wp_send_json_success([
        'attachment_id' => $attachment_id,
        'url' => $upload['url'],
    ]);
}


