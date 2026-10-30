/**
 * A tiny music box that plays Pachelbel's Canon in D (public domain) with the Web Audio API.
 * Used as the background music until the couple uploads their own song.
 */

const BAR = 2.4; // seconds per chord
const STEP = BAR / 8; // eight arpeggio notes per bar

// [bass, arpeggio, melody] as MIDI note numbers. The melody is the Canon's famous descending line.
const PROGRESSION = [
    [50, [62, 66, 69, 74, 78, 74, 69, 66], 78], // D
    [45, [61, 64, 69, 73, 76, 73, 69, 64], 76], // A
    [47, [62, 66, 71, 74, 78, 74, 71, 66], 74], // Bm
    [42, [61, 66, 69, 73, 78, 73, 69, 66], 73], // F#m
    [43, [62, 67, 71, 74, 79, 74, 71, 67], 71], // G
    [50, [62, 66, 69, 74, 78, 74, 69, 66], 69], // D
    [43, [62, 67, 71, 74, 79, 74, 71, 67], 71], // G
    [45, [61, 64, 69, 73, 76, 73, 69, 64], 73], // A
];

const freq = (midi) => 440 * 2 ** ((midi - 69) / 12);

export function createMusicBox() {
    let ctx = null;
    let master = null;
    let timer = null;
    let nextTime = 0;
    let bar = 0;
    let playing = false;

    function setup() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        ctx = new AudioContext();

        master = ctx.createGain();
        master.gain.value = 0;

        // A soft echo gives the box a small-room shimmer.
        const delay = ctx.createDelay();
        delay.delayTime.value = STEP * 1.5;
        const feedback = ctx.createGain();
        feedback.gain.value = 0.28;
        const tone = ctx.createBiquadFilter();
        tone.type = 'lowpass';
        tone.frequency.value = 2600;

        master.connect(ctx.destination);
        master.connect(delay);
        delay.connect(tone);
        tone.connect(feedback);
        feedback.connect(delay);
        tone.connect(ctx.destination);
    }

    function bell(midi, time, { volume = 0.16, decay = 1.6, type = 'sine' } = {}) {
        const osc = ctx.createOscillator();
        const shimmer = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = type;
        osc.frequency.value = freq(midi);
        shimmer.type = 'sine';
        shimmer.frequency.value = freq(midi) * 4.01; // bright metallic partial

        const shimmerGain = ctx.createGain();
        shimmerGain.gain.value = 0.12;

        gain.gain.setValueAtTime(0.0001, time);
        gain.gain.exponentialRampToValueAtTime(volume, time + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.0001, time + decay);

        osc.connect(gain);
        shimmer.connect(shimmerGain).connect(gain);
        gain.connect(master);

        osc.start(time);
        shimmer.start(time);
        osc.stop(time + decay + 0.05);
        shimmer.stop(time + decay + 0.05);
    }

    function scheduleBar(time) {
        const [bass, arpeggio, melody] = PROGRESSION[bar % PROGRESSION.length];
        const withMelody = bar >= PROGRESSION.length; // the melody joins on the second pass

        bell(bass, time, { volume: 0.12, decay: BAR * 1.1, type: 'triangle' });
        arpeggio.forEach((note, i) => bell(note, time + i * STEP, { volume: 0.07, decay: 1.2 }));
        if (withMelody) {
            bell(melody + 12, time, { volume: 0.11, decay: BAR });
            bell(melody + 12 - (bar % 2 ? 3 : 5), time + STEP * 4, { volume: 0.07, decay: BAR * 0.8 });
        }

        bar++;
    }

    function scheduler() {
        while (nextTime < ctx.currentTime + 0.25) {
            scheduleBar(nextTime);
            nextTime += BAR;
        }
    }

    return {
        get playing() {
            return playing;
        },

        async play() {
            if (!ctx) setup();
            if (ctx.state === 'suspended') await ctx.resume();
            if (playing) return;

            playing = true;
            nextTime = ctx.currentTime + 0.1;
            master.gain.cancelScheduledValues(ctx.currentTime);
            master.gain.setTargetAtTime(0.9, ctx.currentTime, 0.6);
            scheduler();
            timer = setInterval(scheduler, 80);
        },

        pause() {
            if (!ctx || !playing) return;

            playing = false;
            master.gain.setTargetAtTime(0, ctx.currentTime, 0.25);
            clearInterval(timer);
        },

        destroy() {
            clearInterval(timer);
            ctx?.close();
            ctx = null;
            playing = false;
        },
    };
}
