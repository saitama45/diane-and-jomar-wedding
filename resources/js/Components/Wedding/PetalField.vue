<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { reducedMotion } from '@/lib/motion';

const canvas = ref();

const COLORS = ['#e6d3b3', '#ebc9b0', '#b9c4a0', '#d99b77', '#f3e6d3'];

let frame;
let petals = [];
let ctx;
let width = 0;
let height = 0;

function resize() {
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.value.width = width * dpr;
    canvas.value.height = height * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
}

function spawn(initial = false) {
    return {
        x: Math.random() * width,
        y: initial ? Math.random() * height : -30,
        size: 7 + Math.random() * 9,
        speed: 0.35 + Math.random() * 0.6,
        drift: Math.random() * Math.PI * 2,
        sway: 0.6 + Math.random() * 1.2,
        spin: (Math.random() - 0.5) * 0.03,
        angle: Math.random() * Math.PI * 2,
        flip: Math.random() * Math.PI * 2,
        color: COLORS[Math.floor(Math.random() * COLORS.length)],
    };
}

function drawPetal(p) {
    ctx.save();
    ctx.translate(p.x, p.y);
    ctx.rotate(p.angle);
    ctx.scale(1, Math.abs(Math.cos(p.flip)) * 0.7 + 0.3); // tumbling in 3D
    ctx.beginPath();
    ctx.moveTo(0, -p.size);
    ctx.bezierCurveTo(p.size * 0.9, -p.size * 0.6, p.size * 0.7, p.size * 0.7, 0, p.size);
    ctx.bezierCurveTo(-p.size * 0.7, p.size * 0.7, -p.size * 0.9, -p.size * 0.6, 0, -p.size);
    ctx.fillStyle = p.color;
    ctx.globalAlpha = 0.85;
    ctx.fill();
    ctx.restore();
}

function loop() {
    ctx.clearRect(0, 0, width, height);
    for (const p of petals) {
        p.drift += 0.012;
        p.y += p.speed;
        p.x += Math.sin(p.drift) * p.sway * 0.5;
        p.angle += p.spin;
        p.flip += 0.02;
        if (p.y > height + 30) Object.assign(p, spawn());
        drawPetal(p);
    }
    frame = requestAnimationFrame(loop);
}

function onVisibility() {
    cancelAnimationFrame(frame);
    if (!document.hidden) frame = requestAnimationFrame(loop);
}

onMounted(() => {
    if (reducedMotion()) return;
    ctx = canvas.value.getContext('2d');
    resize();
    const count = window.innerWidth < 768 ? 10 : 18;
    petals = Array.from({ length: count }, () => spawn(true));
    frame = requestAnimationFrame(loop);
    window.addEventListener('resize', resize);
    document.addEventListener('visibilitychange', onVisibility);
});

onBeforeUnmount(() => {
    cancelAnimationFrame(frame);
    window.removeEventListener('resize', resize);
    document.removeEventListener('visibilitychange', onVisibility);
});
</script>

<template>
    <canvas ref="canvas" class="pointer-events-none fixed inset-0 z-40 h-full w-full opacity-70" aria-hidden="true"></canvas>
</template>
