document.addEventListener('DOMContentLoaded', function () {
    const containers = document.querySelectorAll('.glb-viewer-container');

    containers.forEach(container => {
        const src = container.dataset.src;
        const width = container.offsetWidth;
        const height = container.offsetHeight;
        const autoRotate = container.dataset.auto_rotate === 'true';
        const bgColor = container.dataset.background || '#000000';
        const lightType = container.dataset.light_type || 'hemisphere';
        const lightColor = container.dataset.light_color || '#ffffff';
        const lightIntensity = parseFloat(container.dataset.light_intensity) || 1.0;

        if (!src || !src.endsWith('.glb')) {
            container.innerHTML = '<p style="color:red;padding:1em;">⚠ Invalid .glb source!</p>';
            return;
        }

        const scene = new THREE.Scene();
        scene.background = new THREE.Color(bgColor);

        const camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000);
        camera.position.set(0, 0.5, 8); // Start further away for zoom-in effect

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(width, height);
        container.appendChild(renderer.domElement);

        const controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.1;
        controls.target.set(0, 0.5, 0);
        controls.update();

        if (autoRotate) {
            controls.autoRotate = true;
            controls.autoRotateSpeed = 1.0;
        }

        let light;
        switch (lightType.toLowerCase()) {
            case 'ambient':
                light = new THREE.AmbientLight(lightColor, lightIntensity);
                break;
            case 'directional':
                light = new THREE.DirectionalLight(lightColor, lightIntensity);
                light.position.set(1, 1, 1);
                break;
            case 'point':
                light = new THREE.PointLight(lightColor, lightIntensity);
                light.position.set(0, 1, 2);
                break;
            default:
                light = new THREE.HemisphereLight(lightColor, 0x444444, lightIntensity);
        }
        scene.add(light);

        const loader = new THREE.GLTFLoader();
        loader.load(src, function (gltf) {
            const model = gltf.scene;
            model.position.set(0, 0, 0);
            model.scale.set(0.1, 0.1, 0.1); // Start small for scale animation
            model.visible = false;
            scene.add(model);

            let zoomDone = false;
            let scaleStep = 0.05;
            let fadeInDelay = 10;
            let frame = 0;

            function animate() {
                requestAnimationFrame(animate);

                if (!zoomDone) {
                    camera.position.z -= 0.03;
                    if (camera.position.z <= 3) {
                        camera.position.z = 3;
                        zoomDone = true;
                    }
                }

                if (frame > fadeInDelay && !model.visible) {
                    model.visible = true;
                }

                if (model.visible && model.scale.x < 1) {
                    model.scale.x += scaleStep;
                    model.scale.y += scaleStep;
                    model.scale.z += scaleStep;
                    if (model.scale.x > 1) model.scale.set(1, 1, 1);
                }

                controls.update();
                renderer.render(scene, camera);
                frame++;
            }
            animate();

        }, undefined, function (error) {
            console.error('GLB Load Error:', error);
            container.innerHTML = '<p style="color:red;padding:1em;">❌ Failed to load model.</p>';
        });
    });
});
