<?php
// אם הקובץ לא נקרא דרך תהליך הסרה של וורדפרס, יש לעצור את הביצוע.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// 1. מחיקת אפשרויות שהתווספו ל-Database
delete_option( 'camcraft_3d_objects' ); // לדוגמה: נתונים על אובייקטים תלת-ממדיים
delete_option( 'camcraft_settings' ); // הגדרות כלליות של התוסף

// 2. מחיקת אפשרויות מהאתרים ברשת (אם מדובר בתוסף מולטי-סייט)
if ( is_multisite() ) {
    global $wpdb;
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );
        delete_option( 'camcraft_3d_objects' );
        delete_option( 'camcraft_settings' );
        restore_current_blog();
    }
}

// 3. מחיקת פוסטים מותאמים או תוכן אחר אם התוסף יוצר כאלו (אופציונלי)
// $custom_posts = get_posts( array( 'post_type' => 'your_custom_post_type', 'numberposts' => -1 ) );
// foreach ( $custom_posts as $post ) {
//     wp_delete_post( $post->ID, true );
// }

// 4. (אופציונלי) מחיקת טבלאות מותאמות שנוצרו על ידי התוסף
// global $wpdb;
// $table_name = $wpdb->prefix . 'camcraft_table';
// $wpdb->query( "DROP TABLE IF EXISTS $table_name" );

