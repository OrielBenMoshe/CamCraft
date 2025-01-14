<?php
defined('ABSPATH') || exit;

// בדיקת הרשאות
if (!current_user_can('manage_options')) {
    wp_die(__('אין לך הרשאות מספיקות לגשת לעמוד זה.', 'camcraft'));
}


// פונקציה להמרת גודל קובץ לפורמט קריא
function format_file_size($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

// טיפול במחיקת קובץ
if (isset($_POST['action']) && $_POST['action'] === 'delete_3d_file' && isset($_POST['file_name'])) {
    check_admin_referer('delete_3d_file_nonce');
    
    $file_name = sanitize_file_name($_POST['file_name']);
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/camcraft/3d_models/';
    $file_path = $target_dir . $file_name;
    
    if (file_exists($file_path) && unlink($file_path)) {
        $existing_files = get_option('camcraft_3d_files', array());
        $updated_files = array_filter($existing_files, function($file) use ($file_name) {
            return $file['name'] !== $file_name;
        });
        update_option('camcraft_3d_files', array_values($updated_files));
        
        add_settings_error('camcraft_messages', 'camcraft_message', 
            sprintf(__('הקובץ %s נמחק בהצלחה!', 'camcraft'), $file_name), 
            'updated');
    } else {
        add_settings_error('camcraft_messages', 'camcraft_message', 
            sprintf(__('שגיאה במחיקת הקובץ %s', 'camcraft'), $file_name), 
            'error');
    }
}

// טיפול בהעלאת קבצי תלת מימד
if (isset($_POST['submit_3d_file']) && isset($_FILES['3d_file'])) {
    check_admin_referer('camcraft_3d_upload_nonce');
    
    $file = $_FILES['3d_file'];
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/camcraft/3d_models/';
    
    // יצירת תיקייה אם לא קיימת
    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }
    
    $allowed_types = array('glb', 'gltf', 'obj', 'fbx');
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (in_array($file_ext, $allowed_types)) {
        $target_file = $target_dir . sanitize_file_name($file['name']);
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $existing_files = get_option('camcraft_3d_files', array());
            $existing_files[] = array(
                'name' => sanitize_file_name($file['name']),
                'path' => str_replace($upload_dir['basedir'], '', $target_file),
                'date' => current_time('mysql'),
                'size' => filesize($target_file)
            );
            update_option('camcraft_3d_files', $existing_files);
            
            add_settings_error('camcraft_messages', 'camcraft_message', 
                sprintf(__('הקובץ %s הועלה בהצלחה!', 'camcraft'), $file['name']), 
                'updated');
        }
    } else {
        add_settings_error('camcraft_messages', 'camcraft_message', 
            __('סוג הקובץ אינו נתמך. אנא העלה קובץ מסוג: GLB, GLTF, OBJ, או FBX', 'camcraft'), 
            'error');
    }
}

// טיפול בשמירת הגדרות
if (isset($_POST['submit_camcraft_settings'])) {
    check_admin_referer('camcraft_settings_nonce');
    
    update_option('camcraft_image_format', sanitize_text_field($_POST['image_format']));
    update_option('camcraft_image_quality', intval($_POST['image_quality']));
    update_option('camcraft_max_width', intval($_POST['max_width']));
    update_option('camcraft_max_height', intval($_POST['max_height']));
    update_option('camcraft_storage_path', sanitize_text_field($_POST['storage_path']));
    
    add_settings_error('camcraft_messages', 'camcraft_message', 
        __('ההגדרות נשמרו בהצלחה!', 'camcraft'), 'updated');
}

// הצגת הודעות שגיאה/הצלחה
settings_errors('camcraft_messages');

// קבלת הלשונית הנוכחית
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';

// קבלת סדר המיון
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'date';
$sort_order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';




// דיבאג לבדוק שכל הנתונים על הקבצי תלת מימד מגיעים מהתיקייה של הקבצים
// $files = get_option('camcraft_3d_files', array()); // הדרך הישנה שהשתמשנו בה
$data10 = camcraft_get_models();

function debug_to_console($data, $files) {
    echo '<script>';
    echo 'console.log("Model Data - new teacnic:");';
    echo 'console.log(' . json_encode($data) . ');';
    echo '</script>';
}

debug_to_console($data10, $files);

?>




<!-- לשוניות התצוגה של הדף -->
<div class="wrap">
    <h1><?php _e('CamCraft Editor', 'camcraft'); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=camcraft&tab=settings" 
           class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e('הגדרות', 'camcraft'); ?>
        </a>
        <a href="?page=camcraft&tab=3d_files" 
           class="nav-tab <?php echo $current_tab === '3d_files' ? 'nav-tab-active' : ''; ?>">
            <?php _e('קבצי תלת מימד', 'camcraft'); ?>
        </a>
    </nav>

    <?php if ($current_tab === 'settings'): ?>
        <!-- לשונית הגדרות -->
        <form method="post" action="">
            <?php wp_nonce_field('camcraft_settings_nonce'); ?>
            
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="image_format"><?php _e('פורמט תמונה', 'camcraft'); ?></label>
                    </th>
                    <td>
                        <select name="image_format" id="image_format">
                            <option value="jpeg" <?php selected(get_option('camcraft_image_format', 'jpeg'), 'jpeg'); ?>>JPEG</option>
                            <option value="png" <?php selected(get_option('camcraft_image_format', 'jpeg'), 'png'); ?>>PNG</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="image_quality"><?php _e('איכות תמונה', 'camcraft'); ?></label>
                    </th>
                    <td>
                        <input type="range" name="image_quality" id="image_quality" min="1" max="100" 
                               value="<?php echo esc_attr(get_option('camcraft_image_quality', 85)); ?>">
                        <span class="quality-value"></span>%
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('גודל תמונה מקסימלי', 'camcraft'); ?></th>
                    <td>
                        <label for="max_width"><?php _e('רוחב:', 'camcraft'); ?></label>
                        <input type="number" name="max_width" id="max_width" 
                               value="<?php echo esc_attr(get_option('camcraft_max_width', 1920)); ?>" class="small-text"> px
                        <br>
                        <label for="max_height"><?php _e('גובה:', 'camcraft'); ?></label>
                        <input type="number" name="max_height" id="max_height" 
                               value="<?php echo esc_attr(get_option('camcraft_max_height', 1080)); ?>" class="small-text"> px
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="storage_path"><?php _e('נתיב שמירת תמונות', 'camcraft'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="storage_path" id="storage_path" class="regular-text" 
                               value="<?php echo esc_attr(get_option('camcraft_storage_path', 'wp-content/uploads/camcraft')); ?>">
                        <p class="description">
                            <?php _e('נתיב יחסי לתיקיית WordPress', 'camcraft'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <hr>
            <p style="font-size: 1em; font-weight: bold; text-align: center;">
                <?php _e('יצירת שורטקוד [camcraft_editor]', 'camcraft'); ?><br>
                <?php _e('שים לב: יש להטמיע את השורטקוד הזה בתוכן העמוד בו תרצה להציג את העורך', 'camcraft'); ?>
            </p>
            <hr>

            <p class="submit">
                <input type="submit" name="submit_camcraft_settings" class="button button-primary" 
                       value="<?php esc_attr_e('שמור הגדרות', 'camcraft'); ?>">
            </p>
        </form>

        <?php else: ?>

<!-- לשונית קבצי תלת מימד -->
<div class="camcraft-3d-files">
    <!-- טופס העלאה -->
    <form method="post" enctype="multipart/form-data" class="upload-form">
        <?php wp_nonce_field('camcraft_3d_upload_nonce'); ?>
        <h2><?php _e('העלאת קובץ תלת מימד חדש', 'camcraft'); ?></h2>
        <p class="description">
            <?php _e('קבצים נתמך: GLB, GLTF, OBJ, FBX', 'camcraft'); ?>
        </p>
        <input type="file" name="3d_file" accept=".glb,.gltf,.obj,.fbx" required>
        <input type="submit" name="submit_3d_file" class="button button-primary" 
               value="<?php esc_attr_e('העלה קובץ', 'camcraft'); ?>">
    </form>

<!-- רשימת קבצים -->
<div class="existing-files">
<h2><?php _e('קבצים קיימים', 'camcraft'); ?></h2>
<table class="wp-list-table widefat fixed striped">
<thead>
    <tr>
        <th style="width: 150px"><?php _e('תצוגה מקדימה', 'camcraft'); ?></th>
        <th><?php _e('שם הקובץ', 'camcraft'); ?></th>
        <th class="sortable <?php echo $sort_by === 'date' ? 'sorted' : ''; ?> <?php echo $sort_order; ?>">
            <a href="?page=camcraft&tab=3d_files&sort=date&order=<?php echo $sort_by === 'date' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>">
                <span><?php _e('תאריך העלאה', 'camcraft'); ?></span>
                <span class="sorting-indicator"></span>
            </a>
        </th>
        <th class="sortable <?php echo $sort_by === 'size' ? 'sorted' : ''; ?> <?php echo $sort_order; ?>">
            <a href="?page=camcraft&tab=3d_files&sort=size&order=<?php echo $_sort_by === 'size' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>">
                <span><?php _e('גודל', 'camcraft'); ?></span>
                <span class="sorting-indicator"></span>
            </a>
        </th>
        <th><?php _e('נתיב', 'camcraft'); ?></th>
        <th><?php _e('פעולות', 'camcraft'); ?></th>
    </tr>
</thead>
<tbody>

    <?php
    // שליפת קבצים באמצעות הפונקציה
    $files = camcraft_get_models();

    if (empty($files)): ?>
        <tr>
            <td colspan="6"><?php _e('לא נמצאו קבצים', 'camcraft'); ?></td>
        </tr>
    <?php else:
        // מיון הקבצים
        usort($files, function($a, $b) use ($sort_by, $sort_order) {
            $result = 0;
            if ($sort_by === 'date') {
                $result = strtotime($b['created_at']) - strtotime($a['created_at']);
            } elseif ($sort_by === 'size') {
                $result = $b['size'] - $a['size'];
            }
            return $sort_order === 'asc' ? -$result : $result;
        });

        foreach ($files as $file): ?>
            <tr>
                <td>
                    <div class="model-preview" 
                         data-file="<?php echo esc_attr($file['url']); ?>"
                         data-type="<?php echo esc_attr($file['type']); ?>"
                         style="width: 150px; height: 150px; background: #f0f0f1; border: 1px solid #ccd0d4;">
                        <?php if ($file['type'] === 'glb' || $file['type'] === 'gltf'): ?>
                            <div class="preview-loading"><?php _e('טוען תצוגה מקדימה...', 'camcraft'); ?></div>
                        <?php else: ?>
                            <div class="preview-not-supported"><?php _e('תצוגה מקדימה לא נתמכת', 'camcraft'); ?></div>
                        <?php endif; ?>
                    </div>
                </td>
                <td><?php echo esc_html($file['name']); ?></td>
                <td><?php echo esc_html($file['created_at']); ?></td>
                <td><?php echo format_file_size($file['size']); ?></td>
                <td><?php echo esc_html($file['url']); ?></td>
                <td>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('delete_3d_file_nonce'); ?>
                        <input type="hidden" name="action" value="delete_3d_file">
                        <input type="hidden" name="file_name" value="<?php echo esc_attr($file['name']); ?>">
                        <button type="submit" class="button button-small button-link-delete" 
                                onclick="return confirm('<?php echo esc_js(__('האם אתה בטוח שברצונך למחוק קובץ זה?', 'camcraft')); ?>');">
                            <?php _e('מחק', 'camcraft'); ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach;
    endif; ?>
</tbody>
</table>
</div>
</div>

<?php endif; ?>
</div>

