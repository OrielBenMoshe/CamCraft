<?php
/**
 * פונקציה לשליפת מודלים מתיקיית CamCraft
 * @return array רשימת קבצים בתיקייה עם נתונים נוספים
 */
function camcraft_get_models() {
    $upload_dir = wp_upload_dir();
    $models_dir = $upload_dir['basedir'] . '/camcraft/3d_models';
    $models_url = $upload_dir['baseurl'] . '/camcraft/3d_models';

    // בדיקת קיום התיקייה
    if (!file_exists($models_dir) || !is_dir($models_dir)) {
        return [];
    }

    // שליפת קבצים נתמכים
    $supported_extensions = ['glb', 'gltf', 'obj', 'fbx'];
    $files = scandir($models_dir);
    $models = [];

    foreach ($files as $file) {
        $file_path = $models_dir . '/' . $file;
        $file_url = $models_url . '/' . $file;

        if (is_file($file_path) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $supported_extensions)) {
            $file_info = [
                'name' => $file,
                'url' => $file_url,
                'size' => filesize($file_path), // גודל הקובץ בבייטים
                'created_at' => date("Y-m-d H:i:s", filectime($file_path)), // תאריך יצירה
                'modified_at' => date("Y-m-d H:i:s", filemtime($file_path)), // תאריך שינוי
                'type' => strtolower(pathinfo($file, PATHINFO_EXTENSION)), // סוג הקובץ
            ];
            $models[] = $file_info;
        }
    }

    return $models;
}
