<?php
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$editing = $edit_id > 0;

$model = [
    'title' => '',
    'src' => '',
    'width' => '100%',
    'height' => '500',
    'auto_rotate' => 'true',
    'background' => '#000000',
    'light_type' => 'hemisphere',
    'light_color' => '#ffffff',
    'light_intensity' => '1.0',
];

if ($editing) {
    $model['title'] = get_the_title($edit_id);
    $model['src'] = get_post_meta($edit_id, '_glb_src', true);
    $model['width'] = get_post_meta($edit_id, '_glb_width', true) ?: '100%';
    $model['height'] = get_post_meta($edit_id, '_glb_height', true) ?: '500';
    $model['auto_rotate'] = get_post_meta($edit_id, '_glb_rotate', true) ?: 'false';
    $model['background'] = get_post_meta($edit_id, '_glb_bg', true) ?: '#000000';
    $model['light_type'] = get_post_meta($edit_id, '_glb_light_type', true) ?: 'hemisphere';
    $model['light_color'] = get_post_meta($edit_id, '_glb_light_color', true) ?: '#ffffff';
    $model['light_intensity'] = get_post_meta($edit_id, '_glb_light_intensity', true) ?: '1.0';
}
?>

<div class="wrap">
    <h1>GLB Viewer Settings</h1>
    <form id="glb-viewer-generator">
        <input type="hidden" id="glb-edit-id" value="<?php echo esc_attr($edit_id); ?>">

        <table class="form-table">
            <tr><th>Model Title</th><td>
                <input type="text" id="glb-title" class="regular-text" value="<?php echo esc_attr($model['title']); ?>">
            </td></tr>
            <tr><th>Model Source</th><td>
                <label><input type="radio" name="src_type" value="url" onchange="toggleInput()" <?php echo (strpos($model['src'], 'http') === 0 ? 'checked' : ''); ?>> URL</label><br>
                <input type="text" id="glb-src-url" class="regular-text" placeholder="https://example.com/model.glb" value="<?php echo esc_attr($model['src']); ?>">
                <br><br>
                <label><input type="radio" name="src_type" value="media" onchange="toggleInput()" <?php echo (strpos($model['src'], 'http') !== 0 ? 'checked' : ''); ?>> Media Library</label><br>
                <input type="text" id="glb-src-media" class="regular-text" placeholder="Choose from media..." value="<?php echo esc_attr($model['src']); ?>" readonly>
                <button type="button" class="button" onclick="openMediaUploader()">Select File</button>
            </td></tr>

            <tr><th>Width</th><td><input type="text" id="glb-width" value="<?php echo esc_attr($model['width']); ?>" /></td></tr>
            <tr><th>Height</th><td><input type="text" id="glb-height" value="<?php echo esc_attr($model['height']); ?>" /></td></tr>
            <tr><th>Auto Rotate</th><td><input type="checkbox" id="glb-auto-rotate" <?php checked($model['auto_rotate'], 'true'); ?> /></td></tr>
            <tr><th>Background</th><td><input type="color" id="glb-bg" value="<?php echo esc_attr($model['background']); ?>" /></td></tr>
            <tr><th>Light Type</th><td>
                <select id="glb-light-type">
                    <?php
                    $types = ['hemisphere', 'directional', 'ambient', 'point'];
                    foreach ($types as $type) {
                        echo '<option value="' . esc_attr($type) . '" ' . selected($model['light_type'], $type, false) . '>' . ucfirst($type) . '</option>';
                    }
                    ?>
                </select>
            </td></tr>
            <tr><th>Light Color</th><td><input type="color" id="glb-light-color" value="<?php echo esc_attr($model['light_color']); ?>" /></td></tr>
            <tr><th>Light Intensity</th><td><input type="range" id="glb-light-intensity" min="0" max="5" step="0.1" value="<?php echo esc_attr($model['light_intensity']); ?>" /></td></tr>
        </table>

        <p><button type="button" class="button button-primary" onclick="generateShortcode()">Generate Shortcode</button></p>
        <p><button type="button" class="button button-secondary" onclick="saveAndGenerate()">💾 Save & Generate Shortcode</button></p>

        <textarea id="glb-shortcode-output" rows="3" class="large-text" readonly></textarea>
    </form>

    <h2>Live Preview</h2>
    <div id="glb-live-preview-container" style="width: 100%; height: 600px; border: 1px solid #ccc;"></div>
</div>
