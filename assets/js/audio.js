/**
 * Web Audio API Handler
 * Project: osu! Web Clone
 * * Manages loading, decoding, precise timestamp tracking, 
 * and playback controls for beatmap audio tracks.
 */

export class AudioHandler {
    constructor() {
        // Initialize the standard Web Audio API context
        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        this.audioBuffer = null;
        this.sourceNode = null;
        this.startTime = 0;
        this.isPlaying = false;
    }

    /**
     * Fetches and decodes an audio file from a specific URL.
     * @param {string} audioUrl - Path to the audio file (e.g., 'beatmaps/blue_zenith/easy/audio.mp3')
     * @returns {Promise<void>} Resolves when audio is fully loaded and ready to play
     */
    async load(audioUrl) {
        try {
            // Fetch the raw audio file as binary data
            const response = await fetch(audioUrl);
            if (!response.ok) {
                throw new Error(`Failed to fetch audio file from path: ${audioUrl}`);
            }

            const arrayBuffer = await response.arrayBuffer();

            // Decode the audio binary data into a usable PCM audio buffer
            this.audioBuffer = await this.audioContext.decodeAudioData(arrayBuffer);
        } catch (error) {
            console.error("Error loading or decoding audio:", error);
            throw error;
        }
    }

    /**
     * Starts audio playback and establishes the precise baseline timestamp.
     */
    play() {
        if (!this.audioBuffer) {
            console.error("Cannot play audio: Buffer has not been loaded yet.");
            return;
        }

        // If the context was suspended by browser autoplay policy, resume it
        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }

        // A source node can only be played ONCE in Web Audio API. 
        // We create a fresh one each time play() is explicitly called.
        this.sourceNode = this.audioContext.createBufferSource();
        this.sourceNode.buffer = this.audioBuffer;

        // Connect the source node directly to the audio output (speakers)
        this.sourceNode.connect(this.audioContext.destination);

        // Record the exact baseline context time when playback begins
        this.startTime = this.audioContext.currentTime;

        // Start playback immediately (offset 0)
        this.sourceNode.start(0);
        this.isPlaying = true;
    }

    /**
     * Captures the exact current audio playback position.
     * Called every single frame inside the core game loop.
     * @returns {number} The current playback time elapsed in milliseconds
     */
    getCurrentTime() {
        if (!this.isPlaying) return 0;

        // Calculate how many seconds have elapsed since play() was triggered
        const elapsedSeconds = this.audioContext.currentTime - this.startTime;

        // Convert to milliseconds as required by the rhythm game engine
        return elapsedSeconds * 1000;
    }

    /**
     * Stops the audio track immediately and performs clean up.
     */
    stop() {
        if (this.isPlaying && this.sourceNode) {
            try {
                this.sourceNode.stop();
            } catch (e) {
                // Catches edge cases where the node might have already naturally ended
            }
            this.sourceNode.disconnect();
            this.sourceNode = null;
        }
        this.isPlaying = false;
    }
}