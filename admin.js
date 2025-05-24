// admin.js
const GLB_AJAX_URL = GLB_VARS.ajax_url;

window.addEventListener('DOMContentLoaded', () => {
    const srcUrlInput = document.getElementById('glb-src-url');
    const srcMediaInput = document.getElementById('glb-src-media');
    const srcTypeRadios = document.querySelectorAll('input[name="src_type"]');
    const lightType = document.getElementById('glb-light-type');
    const lightColor = document.getElementById('glb-light-color');
    const lightIntensity = document.getElementById('glb-light-intensity');
    const bgColor = document.getElementById('glb-bg');
    const autoRotate = document.getElementById('glb-auto-rotate');
    const output = document.getElementById('glb-shortcode-output');
    const modelTitle = document.getElementById('glb-title');

    window.toggleInput = () => {
        const type = document.querySelector('input[name="src_type"]:checked').value;
        srcUrlInput.disabled = (type !== 'url');
        srcMediaInput.disabled = (type !== 'media');
        renderLivePreview();
    };

    window.openMediaUploader = () => {
        const mediaUploader = wp.media({
            title: 'Select GLB File',
            button: { text: 'Use This File' },
            library: { type: ['model/gltf-binary'] },
            multiple: false
        });
        mediaUploader.on('select', () => {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            srcMediaInput.value = attachment.url;
            renderLivePreview();
        });
        mediaUploader.open();
    };

    window.generateShortcode = () => {
        const src = getSource();
        const width = document.getElementById('glb-width').value;
        const height = document.getElementById('glb-height').value;
        const rotate = autoRotate.checked ? 'true' : 'false';
        const bg = bgColor.value;
        const lt = lightType.value;
        const lc = lightColor.value;
        const li = lightIntensity.value;
        const sc = `[glb_viewer src="${src}" width="${width}" height="${height}" auto_rotate="${rotate}" background="${bg}" light_type="${lt}" light_color="${lc}" light_intensity="${li}"]`;
        output.value = sc;
        output.select();
        document.execCommand('copy');
        alert('✅ Shortcode copied to clipboard!');
    };

    window.saveAndGenerate = () => {
        const src = getSource();
        const data = {
            action: 'glb_save_model',
            title: modelTitle.value.trim() || `GLB Model ${Date.now()}`,
            src,
            width: document.getElementById('glb-width').value,
            height: document.getElementById('glb-height').value,
            auto_rotate: autoRotate.checked ? 'true' : 'false',
            background: bgColor.value,
            light_type: lightType.value,
            light_color: lightColor.value,
            light_intensity: lightIntensity.value
        };
        output.value = 'Saving...';
        fetch(GLB_AJAX_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        }).then(res => res.json()).then(res => {
            if (res.success) {
                output.value = res.data;
                output.select();
                document.execCommand('copy');
                alert('✅ Saved & copied!');
            } else {
                output.value = '❌ Failed to save';
            }
        }).catch(() => output.value = '❌ AJAX Error');
    };

    function getSource() {
        return document.querySelector('input[name="src_type"]:checked').value === 'url'
            ? srcUrlInput.value
            : srcMediaInput.value;
    }

    window.renderLivePreview = () => {
        const container = document.getElementById('glb-live-preview-container');

        // Dispose old renderer if it exists
        if (container.renderer) {
            container.renderer.dispose();
            container.renderer = null;
        }
        container.innerHTML = '';

        const width = container.offsetWidth;
        const height = container.offsetHeight;
        const src = getSource();

        if (!src || !src.endsWith('.glb')) {
            container.innerHTML = '<p style="color:red;padding:1em;">⚠ Invalid .glb source!</p>';
            return;
        }

        const scene = new THREE.Scene();
        scene.background = new THREE.Color(bgColor.value);

        const camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000);
        camera.position.set(0, 1, 3);

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(width, height);
        container.appendChild(renderer.domElement);
        container.renderer = renderer;

        const controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.1;
        if (autoRotate.checked) {
            controls.autoRotate = true;
            controls.autoRotateSpeed = 1.0;
        }

        let light;
        switch (lightType.value) {
            case 'ambient':
                light = new THREE.AmbientLight(lightColor.value, parseFloat(lightIntensity.value));
                break;
            case 'directional':
                light = new THREE.DirectionalLight(lightColor.value, parseFloat(lightIntensity.value));
                light.position.set(1, 1, 1);
                break;
            case 'point':
                light = new THREE.PointLight(lightColor.value, parseFloat(lightIntensity.value));
                light.position.set(0, 1, 2);
                break;
            default:
                light = new THREE.HemisphereLight(lightColor.value, 0x444444, parseFloat(lightIntensity.value));
        }
        scene.add(light);

        const loader = new THREE.GLTFLoader();
        loader.load(src, gltf => {
            scene.add(gltf.scene);
            animate();
        }, undefined, err => {
            console.error('GLB load failed', err);
            container.innerHTML = '<p style="color:red;padding:1em;">❌ Failed to load model.</p>';
        });

        function animate() {
            requestAnimationFrame(animate);
            controls.update();
            renderer.render(scene, camera);
        }
    };

    [...srcTypeRadios, srcUrlInput, srcMediaInput, lightType, lightColor, lightIntensity, bgColor, autoRotate, modelTitle].forEach(input => {
        input.addEventListener('input', renderLivePreview);
    });

    toggleInput();
});
