<div class="wrap">
    <h1>Saved GLB Models</h1>
    <?php
    $models = get_posts([
        'post_type' => 'glb_model',
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    ?>
    <?php if (empty($models)): ?>
        <p>No models saved yet.</p>
    <?php else: ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Source</th>
                    <th>Dimensions</th>
                    <th>Auto Rotate</th>
                    <th>Background</th>
                    <th>Light</th>
                    <th>Shortcode</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($models as $model):
                $id = $model->ID;
                $title = get_the_title($id);
                $src = get_post_meta($id, '_glb_src', true);
                $width = get_post_meta($id, '_glb_width', true);
                $height = get_post_meta($id, '_glb_height', true);
                $rotate = get_post_meta($id, '_glb_rotate', true);
                $bg = get_post_meta($id, '_glb_bg', true);
                $light = get_post_meta($id, '_glb_light_type', true);
                $shortcode = '[glb_viewer_' . $id . ']';
                ?>
                <tr>
                    <td><?php echo esc_html($title); ?></td>
                    <td><code><?php echo esc_url($src); ?></code></td>
                    <td><?php echo esc_html($width . 'x' . $height); ?></td>
                    <td><?php echo esc_html($rotate); ?></td>
                    <td><div style="width:30px;height:20px;background:<?php echo esc_attr($bg); ?>;border:1px solid #ccc;"></div></td>
                    <td><?php echo esc_html($light); ?></td>
                    <td><input type="text" value="<?php echo esc_attr($shortcode); ?>" readonly onclick="this.select();" style="width: 180px;" /></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=glb-viewer-settings&edit=' . $id); ?>" class="button button-small">Edit</a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=glb_delete_model&id=' . $id), 'glb_delete_model_' . $id); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete this model?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
