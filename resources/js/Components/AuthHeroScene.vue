<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import {
    AmbientLight,
    BoxGeometry,
    Clock,
    DirectionalLight,
    Group,
    Mesh,
    MeshPhysicalMaterial,
    PerspectiveCamera,
    Scene,
    SphereGeometry,
    TorusGeometry,
    WebGLRenderer,
    PCFSoftShadowMap,
    SRGBColorSpace,
    FogExp2
} from 'three';

const container = ref(null);
const canvas = ref(null);
const webglFailed = ref(false);

let animationFrame;
let renderer;
let scene;
let camera;
let sceneGroup;
let resizeObserver;

const pointer = { x: 0, y: 0, targetX: 0, targetY: 0 };
const objects = [];

const material = (color, options = {}) => new MeshPhysicalMaterial({
    color,
    roughness: options.roughness ?? 0.2,
    metalness: options.metalness ?? 0.1,
    clearcoat: options.clearcoat ?? 0.5,
    clearcoatRoughness: options.clearcoatRoughness ?? 0.2,
});

const createShapes = () => {
    // Colors that fit the "modern and friendly" SaaS theme
    const colors = [
        0x0F766E, // Teal Primary
        0x14B8A6, // Teal Light
        0xF97316, // Orange Accent
        0x38BDF8, // Light Blue
        0xF4F4F5  // White/Slate-50
    ];

    // Sphere
    const sphere = new Mesh(
        new SphereGeometry(1.2, 64, 64),
        material(colors[0])
    );
    sphere.position.set(-2, 1, 0);
    sphere.userData = { 
        floatSpeed: 1.5, 
        floatOffset: 0, 
        rotSpeedX: 0.005, 
        rotSpeedY: 0.01 
    };
    sphere.castShadow = true;
    sphere.receiveShadow = true;
    
    // Torus (Donut)
    const torus = new Mesh(
        new TorusGeometry(0.8, 0.3, 32, 64),
        material(colors[2], { roughness: 0.4, clearcoat: 0.8 })
    );
    torus.position.set(2.5, -1, 1);
    torus.userData = { 
        floatSpeed: 2, 
        floatOffset: Math.PI / 2, 
        rotSpeedX: 0.01, 
        rotSpeedY: -0.01 
    };
    torus.castShadow = true;
    torus.receiveShadow = true;

    // Cube with rounded aesthetic (using many segments or just a smooth material)
    const cube = new Mesh(
        new BoxGeometry(1.5, 1.5, 1.5, 4, 4, 4),
        material(colors[1], { roughness: 0.1, metalness: 0.2 })
    );
    cube.position.set(0.5, 2.5, -1);
    cube.userData = { 
        floatSpeed: 1.2, 
        floatOffset: Math.PI, 
        rotSpeedX: -0.008, 
        rotSpeedY: 0.015 
    };
    cube.castShadow = true;
    cube.receiveShadow = true;

    // Small floating sphere
    const smallSphere = new Mesh(
        new SphereGeometry(0.5, 32, 32),
        material(colors[3])
    );
    smallSphere.position.set(-2.5, -2, 2);
    smallSphere.userData = { 
        floatSpeed: 2.5, 
        floatOffset: Math.PI * 1.5, 
        rotSpeedX: 0.02, 
        rotSpeedY: 0.02 
    };
    smallSphere.castShadow = true;
    smallSphere.receiveShadow = true;

    sceneGroup.add(sphere, torus, cube, smallSphere);
    objects.push(sphere, torus, cube, smallSphere);
};

const initScene = () => {
    renderer = new WebGLRenderer({
        canvas: canvas.value,
        alpha: true,
        antialias: true,
        powerPreference: 'high-performance',
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.outputColorSpace = SRGBColorSpace;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = PCFSoftShadowMap;

    scene = new Scene();
    // Soft bright fog to match the friendly theme
    scene.fog = new FogExp2(0xF0FDFA, 0.03);

    camera = new PerspectiveCamera(45, 1, 0.1, 100);
    camera.position.set(0, 0, 10);

    sceneGroup = new Group();
    scene.add(sceneGroup);

    createShapes();

    // Soft Ambient Light
    const ambientLight = new AmbientLight(0xffffff, 1.5);
    scene.add(ambientLight);

    // Main Directional Light (Sun)
    const mainLight = new DirectionalLight(0xffffff, 3);
    mainLight.position.set(5, 10, 7);
    mainLight.castShadow = true;
    mainLight.shadow.mapSize.width = 1024;
    mainLight.shadow.mapSize.height = 1024;
    mainLight.shadow.camera.near = 0.5;
    mainLight.shadow.camera.far = 25;
    mainLight.shadow.bias = -0.001;
    scene.add(mainLight);

    // Fill Light
    const fillLight = new DirectionalLight(0xE0F2FE, 1.5);
    fillLight.position.set(-5, 0, -5);
    scene.add(fillLight);

    resize();
    resizeObserver = new ResizeObserver(resize);
    resizeObserver.observe(container.value);
};

const resize = () => {
    if (!container.value || !camera || !renderer) return;
    const width = container.value.clientWidth;
    const height = container.value.clientHeight;
    
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    renderer.setSize(width, height, false);
};

const animate = () => {
    if (!renderer || !scene || !camera) return;
    
    animationFrame = requestAnimationFrame(animate);

    const time = performance.now() * 0.001;

    // Smooth pointer interpolation
    pointer.x += (pointer.targetX - pointer.x) * 0.05;
    pointer.y += (pointer.targetY - pointer.y) * 0.05;

    // Group parallax effect
    sceneGroup.rotation.y = pointer.x * 0.3;
    sceneGroup.rotation.x = -pointer.y * 0.3;

    // Animate individual objects
    objects.forEach((obj) => {
        const { floatSpeed, floatOffset, rotSpeedX, rotSpeedY } = obj.userData;
        
        // Floating up and down
        obj.position.y += Math.sin(time * floatSpeed + floatOffset) * 0.004;
        
        // Gentle rotation
        obj.rotation.x += rotSpeedX;
        obj.rotation.y += rotSpeedY;
    });

    renderer.render(scene, camera);
};

const onPointerMove = (e) => {
    if (!container.value) return;
    const rect = container.value.getBoundingClientRect();
    // Normalize coordinates to -1 to 1
    pointer.targetX = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.targetY = -((e.clientY - rect.top) / rect.height) * 2 + 1;
};

onMounted(() => {
    try {
        initScene();
        animate();
        window.addEventListener('mousemove', onPointerMove);
    } catch (error) {
        console.error('WebGL initialization failed:', error);
        webglFailed.value = true;
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('mousemove', onPointerMove);
    if (animationFrame) cancelAnimationFrame(animationFrame);
    if (resizeObserver) resizeObserver.disconnect();
    
    if (renderer) {
        renderer.dispose();
        renderer.forceContextLoss();
    }
});
</script>

<template>
    <div ref="container" class="relative h-full w-full overflow-hidden bg-teal-50" aria-label="Animasi bentuk 3D yang ramah dan modern">
        <canvas v-show="!webglFailed" ref="canvas" class="absolute inset-0 h-full w-full" />
    </div>
</template>
