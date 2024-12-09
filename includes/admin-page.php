<?php
defined('ABSPATH') || exit;

// בדיקת הרשאות
if (!current_user_can('manage_options')) {
    wp_die(__('אין לך הרשאות מספיקות לגשת לעמוד זה.', 'camcraft'));
}

// הוספת סקריפטים וסגנונות
wp_enqueue_script('three-js', 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js', array(), null, true);
wp_enqueue_script('gltf-loader', 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js', array('three-js'), null, true);
wp_enqueue_script('orbit-controls', 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js', array('three-js'), null, true);

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

?>

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
                                <a href="?page=camcraft&tab=3d_files&sort=size&order=<?php echo $sort_by === 'size' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>">
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
                        $files = get_option('camcraft_3d_files', array());
                        
                        // מיון הקבצים
                        if (!empty($files)) {
                            usort($files, function($a, $b) use ($sort_by, $sort_order) {
                                $result = 0;
                                if ($sort_by === 'date') {
                                    $result = strtotime($b['date']) - strtotime($a['date']);
                                } elseif ($sort_by === 'size') {
                                    $result = $b['size'] - $a['size'];
                                }
                                return $sort_order === 'asc' ? -$result : $result;
                            });
                        }

                        if (empty($files)): ?>
                            <tr>
                                <td colspan="6"><?php _e('לא נמצאו קבצים', 'camcraft'); ?></td>
                            </tr>
                        <?php else:
                            foreach ($files as $file): 
                                $upload_dir = wp_upload_dir();
                                $file_url = $upload_dir['baseurl'] . '/camcraft/3d_models/' . $file['name'];
                                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            ?>
                                <tr>
                                    <td>
                                        <div class="model-preview" 
                                             data-file="<?php echo esc_attr($file_url); ?>"
                                             data-type="<?php echo esc_attr($file_ext); ?>"
                                             style="width: 150px; height: 150px; background: #f0f0f1; border: 1px solid #ccd0d4;">
                                            <?php if ($file_ext === 'glb' || $file_ext === 'gltf'): ?>
                                                <div class="preview-loading"><?php _e('טוען תצוגה מקדימה...', 'camcraft'); ?></div>
                                            <?php else: ?>
                                                <div class="preview-not-supported"><?php _e('תצוגה מקדימה לא נתמכת', 'camcraft'); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($file['name']); ?></td>
                                    <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($file['date']))); ?></td>
                                    <td><?php echo format_file_size($file['size']); ?></td>
                                    <td><?php echo esc_html($file['path']); ?></td>
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

        <style>
            .camcraft-3d-files .upload-form {
                margin: 20px 0;
                padding: 20px;
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .camcraft-3d-files .upload-form input[type="file"] {
                margin: 10px 0;
                display: block;
            }
            .camcraft-3d-files .existing-files {
                margin-top: 30px;
            }
            .button-link-delete {
                color: #a00;
            }
            .button-link-delete:hover {
                color: #dc3232;
                border-color: #dc3232;
            }
            .model-preview {
                position: relative;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .model-preview canvas {
                width: 100% !important;
                height: 100% !important;
            }
            .preview-loading,
            .preview-not-supported {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                text-align: center;
                font-size: 12px;
                color: #666;
            }
            .preview-not-supported {
                background: rgba(255,255,255,0.9);
                padding: 5px;
                border-radius: 3px;
            }
            .preview-loading.preview-error {
                color: #dc3232;
            }
            .sortable .sorting-indicator {
                display: none;
            }
            .sortable.sorted .sorting-indicator {
                display: block;
            }
            .sortable.sorted.asc .sorting-indicator:before {
                content: '\f142';
            }
            .sortable.sorted.desc .sorting-indicator:before {
                content: '\f140';
            }
            .sortable a {
                display: inline-block;
                text-decoration: none;
                color: #23282d;
            }
            .sortable a:hover {
                color: #0073aa;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // עדכון תצוגת ערך האיכות
                const qualityInput = $('#image_quality');
                const qualityDisplay = $('.quality-value');
                
                function updateQualityDisplay() {
                    qualityDisplay.text(qualityInput.val());
                }
                
                qualityInput.on('input', updateQualityDisplay);
                updateQualityDisplay();

                // אתחול תצוגות מקדימות של מודלים
                $('.model-preview').each(function() {
                    const container = this;
                    const fileUrl = $(container).data('file');
                    const fileType = $(container).data('type');

                    // יצירת סצנה
                    const scene = new THREE.Scene();
                    scene.background = new THREE.Color(0xf0f0f1);

                    // יצירת מצלמה
                    const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 1000);
                    camera.position.z = 5;

                    // יצירת renderer
                    const renderer = new THREE.WebGLRenderer({ antialias: true });
                    renderer.setSize(150, 150);
                    container.appendChild(renderer.domElement);

                    // תאורה
                    const light = new THREE.AmbientLight(0xffffff, 0.5);
                    scene.add(light);
                    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.5);
                    directionalLight.position.set(0, 1, 0);
                    scene.add(directionalLight);
                    git log --oneline
                    // בקרי סיבוב
                    const controls = new THREE.OrbitControls(camera, renderer.domElement);
                    controls.enableZoom = false;
                    controls.autoRotate = true;

                    // טעינת המודל
                    if (fileType === 'glb' || fileType === 'gltf') {
                        const loader = new THREE.GLTFLoader();
                        loader.load(fileUrl, function(gltf) {
                            // הסתרת הודעת הטעינה
                            $(container).find('.preview-loading').hide();
                            
                            const model = gltf.scene;
                            scene.add(model);

                            // מיקום המודל במרכז
                            const box = new THREE.Box3().setFromObject(model);
                            const center = box.getCenter(new THREE.Vector3());
                            const size = box.getSize(new THREE.Vector3());
                            const maxDim = Math.max(size.x, size.y, size.z);
                            const fov = camera.fov * (Math.PI / 180);
                            let cameraZ = Math.abs(maxDim / 2 / Math.tan(fov / 2));
                            camera.position.z = cameraZ * 1.5;
                            
                            model.position.x = -center.x;
                            model.position.y = -center.y;
                            model.position.z = -center.z;
                        }, 
                        // התקדמות הטעינה
                        function(xhr) {
                            const percent = Math.round((xhr.loaded / xhr.total) * 100);
                            $(container).find('.preview-loading').text(`טוען... ${percent}%`);
                        },
                        // שגיאה בטעינה
                        function(error) {
                            $(container).find('.preview-loading')
                                .text('שגיאה בטעינת המודל')
                                .addClass('preview-error');
                            console.error('שגיאה בטעינת המודל:', error);
                        });
                    }

                    // אנימציה
                    function animate() {
                        requestAnimationFrame(animate);
                        controls.update();
                        renderer.render(scene, camera);
                    }
                    animate();
                });
            });
        </script>
    <?php endif; ?>
</div>
