<template>
    <!-- The watercolour filters and the gold of the "Watercolor Garden" theme, shared by its drawings. -->
    <defs>
        <filter id="garden-wash" x="-10%" y="-10%" width="120%" height="120%">
            <feTurbulence type="fractalNoise" baseFrequency="0.035" numOctaves="2" seed="7" result="noise" />
            <feDisplacementMap in="SourceGraphic" in2="noise" scale="4" xChannelSelector="R" yChannelSelector="G" result="wobble" />
            <feGaussianBlur in="wobble" stdDeviation="0.45" />
        </filter>
        <filter id="garden-bleed" x="-40%" y="-40%" width="180%" height="180%"><feGaussianBlur stdDeviation="9" /></filter>
        <filter id="garden-soft" x="-30%" y="-30%" width="160%" height="160%"><feGaussianBlur stdDeviation="1.1" /></filter>
        <radialGradient id="garden-gold" cx="35%" cy="30%" r="70%">
            <stop offset="0" stop-color="#ecd08a" />
            <stop offset="1" stop-color="#b8892f" />
        </radialGradient>
    </defs>
</template>
