(function ($) {
    
    
    // פונקציה גנרית לטעינת מודל
    function load3DModel(container, fileUrl, fileType) {
        
        console.log('מתחיל טעינת מודל:', fileUrl);

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

        // בקרי סיבוב
        const controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableZoom = false;
        controls.autoRotate = true;

        // טעינת המודל
        if (fileType === 'glb' || fileType === 'gltf') {
            const loader = new THREE.GLTFLoader();
            loader.load(fileUrl, function (gltf) {
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
                function (xhr) {
                    const percent = Math.round((xhr.loaded / xhr.total) * 100);
                    $(container).find('.preview-loading').text(`טוען... ${percent}%`);
                },
                // שגיאה בטעינה
                function (error) {
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
    }

    // אתחול טעינת כל המודלים
    $(document).ready(function () {
        $('.model-preview').each(function () {
            const container = this;
            const fileUrl = $(container).data('file');
            const fileType = $(container).data('type');

            load3DModel(container, fileUrl, fileType);
        });
    });
})(jQuery);
