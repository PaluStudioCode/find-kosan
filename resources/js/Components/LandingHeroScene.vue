<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import {
    ACESFilmicToneMapping,
    AmbientLight,
    BoxGeometry,
    BufferGeometry,
    CanvasTexture,
    Clock,
    CylinderGeometry,
    DirectionalLight,
    Float32BufferAttribute,
    FogExp2,
    Group,
    IcosahedronGeometry,
    Mesh,
    MeshBasicMaterial,
    MeshStandardMaterial,
    PCFSoftShadowMap,
    PerspectiveCamera,
    PlaneGeometry,
    PointLight,
    Points,
    PointsMaterial,
    Scene,
    SphereGeometry,
    SRGBColorSpace,
    WebGLRenderer,
} from 'three';

const container = ref(null);
const canvas = ref(null);
const webglFailed = ref(false);

let animationFrame;
let renderer;
let scene;
let camera;
let buildingScene;
let resizeObserver;
let visibilityObserver;
let isVisible = true;

const pointer = { x: 0, y: 0 };

const material = (color, options = {}) => new MeshStandardMaterial({
    color,
    roughness: options.roughness ?? 0.72,
    metalness: options.metalness ?? 0.04,
    emissive: options.emissive,
    emissiveIntensity: options.emissiveIntensity,
});

const addBox = (parent, size, position, boxMaterial, castShadow = true) => {
    const mesh = new Mesh(new BoxGeometry(...size), boxMaterial);
    mesh.position.set(...position);
    mesh.castShadow = castShadow;
    mesh.receiveShadow = true;
    parent.add(mesh);

    return mesh;
};

const createWindow = (parent, x, y, z, width = 0.72, height = 0.72) => {
    const frameMaterial = material(0x173f42, { roughness: 0.55 });
    const glassMaterial = material(0x8fd9d2, {
        roughness: 0.18,
        metalness: 0.22,
        emissive: 0x174d4d,
        emissiveIntensity: 0.25,
    });

    addBox(parent, [width + 0.14, height + 0.14, 0.09], [x, y, z], frameMaterial);
    addBox(parent, [width, height, 0.055], [x, y, z + 0.058], glassMaterial, false);
    addBox(parent, [0.045, height, 0.07], [x, y, z + 0.092], frameMaterial, false);
    addBox(parent, [width, 0.04, 0.07], [x, y, z + 0.092], frameMaterial, false);
    addBox(
        parent,
        [width + 0.22, 0.08, 0.18],
        [x, y - height / 2 - 0.1, z + 0.04],
        material(0xd7dfd7),
    );
};

const createDoor = (parent, x, y, z) => {
    const frameMaterial = material(0x173f42, { roughness: 0.6 });
    const doorMaterial = material(0x9a5f36, { roughness: 0.7 });

    addBox(parent, [0.98, 1.34, 0.12], [x, y, z], frameMaterial);
    addBox(parent, [0.78, 1.18, 0.07], [x, y - 0.03, z + 0.09], doorMaterial);
    addBox(parent, [0.34, 0.42, 0.045], [x, y + 0.2, z + 0.14], material(0x69b8b0), false);
    addBox(parent, [0.035, 0.42, 0.055], [x, y + 0.2, z + 0.17], frameMaterial, false);
    addBox(parent, [0.34, 0.03, 0.055], [x, y + 0.2, z + 0.17], frameMaterial, false);

    const handle = new Mesh(
        new SphereGeometry(0.045, 16, 16),
        material(0xe5b75d, { metalness: 0.7, roughness: 0.25 }),
    );
    handle.position.set(x + 0.25, y - 0.05, z + 0.17);
    handle.castShadow = true;
    parent.add(handle);
};

const createKosSign = (parent) => {
    const signCanvas = document.createElement('canvas');
    signCanvas.width = 512;
    signCanvas.height = 192;

    const context = signCanvas.getContext('2d');
    context.clearRect(0, 0, signCanvas.width, signCanvas.height);
    context.fillStyle = '#77e2c3';
    context.font = '800 106px Inter, Arial, sans-serif';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.fillText('KOS', signCanvas.width / 2, signCanvas.height / 2 + 4);

    const texture = new CanvasTexture(signCanvas);
    texture.colorSpace = SRGBColorSpace;

    addBox(parent, [1.65, 0.66, 0.14], [0, 3.28, 0.02], material(0x102f32));

    const sign = new Mesh(
        new PlaneGeometry(1.45, 0.54),
        new MeshBasicMaterial({ map: texture, transparent: true }),
    );
    sign.position.set(0, 3.28, 0.096);
    parent.add(sign);
};

const createBalcony = (parent) => {
    const concreteMaterial = material(0xdce5df);
    const railMaterial = material(0x173f42, { metalness: 0.25, roughness: 0.5 });

    addBox(parent, [4.7, 0.14, 0.72], [0, 1.55, 1.73], concreteMaterial);
    addBox(parent, [4.48, 0.06, 0.06], [0, 2.08, 2.04], railMaterial);

    for (let x = -2.2; x <= 2.2; x += 0.44) {
        addBox(parent, [0.035, 0.52, 0.045], [x, 1.82, 2.04], railMaterial, false);
    }

    addBox(parent, [0.05, 0.52, 0.68], [-2.25, 1.82, 1.73], railMaterial, false);
    addBox(parent, [0.05, 0.52, 0.68], [2.25, 1.82, 1.73], railMaterial, false);
};

const createBoardingHouse = () => {
    const house = new Group();
    const lowerWall = material(0xe8dfce, { roughness: 0.82 });
    const upperWall = material(0xf2f3ea, { roughness: 0.78 });
    const darkTrim = material(0x173f42, { roughness: 0.62 });

    // Two clearly separated floors.
    addBox(house, [4.8, 1.4, 3], [0, 0.7, 0], lowerWall);
    addBox(house, [4.8, 1.4, 3], [0, 2.1, 0], upperWall);
    addBox(house, [4.96, 0.12, 3.12], [0, 1.42, 0], darkTrim);

    // Ground floor: one main door and two windows.
    createDoor(house, 0, 0.72, 1.54);
    createWindow(house, -1.55, 0.82, 1.535, 0.82, 0.7);
    createWindow(house, 1.55, 0.82, 1.535, 0.82, 0.7);

    // Second floor: three windows facing the visitor.
    createWindow(house, -1.55, 2.23, 1.535, 0.78, 0.66);
    createWindow(house, 0, 2.23, 1.535, 0.78, 0.66);
    createWindow(house, 1.55, 2.23, 1.535, 0.78, 0.66);

    // Side windows make the building readable in perspective.
    const sideFrame = material(0x173f42, { roughness: 0.55 });
    const sideGlass = material(0x8fd9d2, {
        roughness: 0.18,
        metalness: 0.2,
        emissive: 0x174d4d,
        emissiveIntensity: 0.2,
    });
    [0.82, 2.23].forEach((y) => {
        addBox(house, [0.07, 0.76, 0.82], [2.435, y, -0.35], sideFrame);
        addBox(house, [0.045, 0.64, 0.7], [2.478, y, -0.35], sideGlass, false);
    });

    createBalcony(house);

    // Entrance canopy, lighting, and steps.
    addBox(house, [1.45, 0.12, 0.72], [0, 1.47, 1.78], darkTrim);
    addBox(house, [1.16, 0.16, 0.22], [0, 1.35, 1.87], material(0xffca70, {
        emissive: 0xa64c0a,
        emissiveIntensity: 0.85,
    }), false);

    addBox(house, [1.3, 0.15, 0.48], [0, 0.08, 1.74], material(0xd5d9cf));
    addBox(house, [1.65, 0.14, 0.42], [0, -0.04, 1.95], material(0xc8cec6));
    addBox(house, [2, 0.13, 0.38], [0, -0.15, 2.15], material(0xbac2ba));

    // Modern flat roof and parapet.
    addBox(house, [5.18, 0.16, 3.38], [0, 2.88, 0], material(0xd2ddd6));
    addBox(house, [5, 0.34, 0.14], [0, 3.03, 1.58], darkTrim);
    addBox(house, [5, 0.34, 0.14], [0, 3.03, -1.58], darkTrim);
    addBox(house, [0.14, 0.34, 3.02], [-2.43, 3.03, 0], darkTrim);
    addBox(house, [0.14, 0.34, 3.02], [2.43, 3.03, 0], darkTrim);

    createKosSign(house);

    house.position.set(0, 0.45, -0.35);
    buildingScene.add(house);
};

const createTree = (x, z, scale = 1) => {
    const tree = new Group();
    const trunk = new Mesh(
        new CylinderGeometry(0.07 * scale, 0.09 * scale, 0.48 * scale, 8),
        material(0x765039, { roughness: 1 }),
    );
    trunk.position.y = 0.24 * scale;
    trunk.castShadow = true;

    const crown = new Mesh(
        new IcosahedronGeometry(0.38 * scale, 1),
        material(0x3cab7f, { roughness: 0.92 }),
    );
    crown.position.y = 0.78 * scale;
    crown.castShadow = true;

    tree.add(trunk, crown);
    tree.position.set(x, 0.35, z);
    buildingScene.add(tree);
};

const createEnvironment = () => {
    const base = addBox(
        buildingScene,
        [8.3, 0.42, 6.5],
        [0, 0, 0],
        material(0x17484a, { roughness: 0.88 }),
    );
    base.receiveShadow = true;

    addBox(
        buildingScene,
        [7.85, 0.1, 6.05],
        [0, 0.25, 0],
        material(0x4e8b72, { roughness: 0.98 }),
    );

    // Walkway leading directly to the front door.
    addBox(
        buildingScene,
        [2.25, 0.06, 2.4],
        [0, 0.34, 2.15],
        material(0xd5d5c8, { roughness: 0.95 }),
    );

    for (let z = 1.18; z <= 3.1; z += 0.42) {
        addBox(
            buildingScene,
            [1.78, 0.018, 0.035],
            [0, 0.378, z],
            material(0x9daaa2),
            false,
        );
    }

    createTree(-3.2, 1.65, 0.9);
    createTree(3.25, 1.5, 0.82);
    createTree(-3.35, -1.75, 0.7);

    // Small shrubs frame the entrance without hiding the facade.
    [-2.8, -2.35, 2.35, 2.8].forEach((x, index) => {
        const shrub = new Mesh(
            new IcosahedronGeometry(0.2 + (index % 2) * 0.04, 1),
            material(0x58b58a, { roughness: 1 }),
        );
        shrub.position.set(x, 0.52, 2.05);
        shrub.castShadow = true;
        buildingScene.add(shrub);
    });
};

const createParticles = () => {
    const geometry = new BufferGeometry();
    const positions = [];

    for (let index = 0; index < 70; index += 1) {
        positions.push(
            (Math.random() - 0.5) * 14,
            Math.random() * 7 + 0.5,
            (Math.random() - 0.5) * 10,
        );
    }

    geometry.setAttribute('position', new Float32BufferAttribute(positions, 3));
    const particles = new Points(
        geometry,
        new PointsMaterial({
            color: 0x8df4d6,
            size: 0.025,
            transparent: true,
            opacity: 0.42,
        }),
    );
    scene.add(particles);
};

const initScene = () => {
    renderer = new WebGLRenderer({
        canvas: canvas.value,
        alpha: true,
        antialias: true,
        powerPreference: 'high-performance',
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.75));
    renderer.outputColorSpace = SRGBColorSpace;
    renderer.toneMapping = ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.08;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = PCFSoftShadowMap;

    scene = new Scene();
    scene.fog = new FogExp2(0x071a1d, 0.05);

    camera = new PerspectiveCamera(38, 1, 0.1, 100);
    camera.position.set(7.2, 5.1, 9.8);
    camera.lookAt(0, 1.45, 0);

    buildingScene = new Group();
    buildingScene.rotation.y = -0.13;
    scene.add(buildingScene);

    scene.add(new AmbientLight(0x9ed8cd, 1.15));

    const keyLight = new DirectionalLight(0xffefd3, 4.8);
    keyLight.position.set(5, 9, 7);
    keyLight.castShadow = true;
    keyLight.shadow.mapSize.set(1024, 1024);
    keyLight.shadow.camera.left = -7;
    keyLight.shadow.camera.right = 7;
    keyLight.shadow.camera.top = 7;
    keyLight.shadow.camera.bottom = -7;
    scene.add(keyLight);

    const tealLight = new PointLight(0x36d7b3, 20, 15);
    tealLight.position.set(-4, 3, -2);
    scene.add(tealLight);

    const warmLight = new PointLight(0xffa65d, 13, 10);
    warmLight.position.set(1, 2.5, 5);
    scene.add(warmLight);

    createEnvironment();
    createBoardingHouse();
    createParticles();
};

const resize = () => {
    if (!renderer || !container.value) return;

    const width = container.value.clientWidth;
    const height = container.value.clientHeight;
    renderer.setSize(width, height, false);
    camera.aspect = width / Math.max(height, 1);
    camera.updateProjectionMatrix();
};

const handlePointerMove = (event) => {
    if (!container.value) return;

    const bounds = container.value.getBoundingClientRect();
    pointer.x = ((event.clientX - bounds.left) / bounds.width - 0.5) * 2;
    pointer.y = ((event.clientY - bounds.top) / bounds.height - 0.5) * 2;
};

onMounted(() => {
    try {
        initScene();
        resize();
    } catch (error) {
        webglFailed.value = true;
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const clock = new Clock();

    const animate = () => {
        animationFrame = window.requestAnimationFrame(animate);
        if (!isVisible) return;

        const elapsed = clock.getElapsedTime();
        const motionScale = prefersReducedMotion ? 0 : 1;
        const targetRotation = -0.13 + pointer.x * 0.075 * motionScale;
        const targetTilt = pointer.y * 0.018 * motionScale;

        buildingScene.rotation.y += (targetRotation - buildingScene.rotation.y) * 0.035;
        buildingScene.rotation.x += (targetTilt - buildingScene.rotation.x) * 0.035;
        buildingScene.position.y = Math.sin(elapsed * 0.62) * 0.055 * motionScale;

        renderer.render(scene, camera);
    };

    resizeObserver = new ResizeObserver(resize);
    resizeObserver.observe(container.value);

    visibilityObserver = new IntersectionObserver(([entry]) => {
        isVisible = entry.isIntersecting;
    });
    visibilityObserver.observe(container.value);

    container.value.addEventListener('pointermove', handlePointerMove);
    animate();
});

onBeforeUnmount(() => {
    window.cancelAnimationFrame(animationFrame);
    resizeObserver?.disconnect();
    visibilityObserver?.disconnect();
    container.value?.removeEventListener('pointermove', handlePointerMove);

    scene?.traverse((object) => {
        object.geometry?.dispose();

        if (Array.isArray(object.material)) {
            object.material.forEach((meshMaterial) => {
                meshMaterial.map?.dispose();
                meshMaterial.dispose();
            });
        } else {
            object.material?.map?.dispose();
            object.material?.dispose();
        }
    });

    renderer?.dispose();
});
</script>

<template>
    <div ref="container" class="relative h-full min-h-[420px] w-full overflow-hidden" aria-label="Animasi bangunan kos dua lantai">
        <canvas v-show="!webglFailed" ref="canvas" class="absolute inset-0 h-full w-full" />

        <div v-if="webglFailed" class="absolute inset-10 flex items-end justify-center rounded-[2rem] border border-white/10 bg-gradient-to-br from-teal-400/20 to-orange-300/10">
            <div class="mb-10 h-52 w-64 rounded-t-xl border border-teal-200/20 bg-teal-100/10">
                <div class="grid h-full grid-cols-3 grid-rows-2 gap-5 p-7">
                    <span class="rounded bg-teal-200/20" />
                    <span class="rounded bg-teal-200/20" />
                    <span class="rounded bg-teal-200/20" />
                    <span class="rounded bg-teal-200/20" />
                    <span class="rounded-t bg-orange-200/30" />
                    <span class="rounded bg-teal-200/20" />
                </div>
            </div>
        </div>
    </div>
</template>
