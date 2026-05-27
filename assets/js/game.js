/**
 * Main Game Engine
 * Project: osu! Web Clone
 */

import { parseBeatmap } from './parser.js';
import { AudioHandler } from './audio.js';

export class GameEngine {
    constructor(canvasId, beatmapFolder, mapTitle, difficulty, prevBestScore, prevBestAccuracy) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');

        // Configuration
        this.beatmapFolder = beatmapFolder;
        this.mapTitle = mapTitle;
        this.difficulty = difficulty;
        this.prevBestScore = prevBestScore;
        this.prevBestAccuracy = prevBestAccuracy;

        // Game State
        this.audioHandler = new AudioHandler();
        this.hitObjects = [];
        this.visibleObjects = [];
        this.totalCircles = 0;
        this.score = 0;
        this.combo = 0;
        this.maxCombo = 0;
        this.misses = 0;
        this.totalHitValueAwarded = 0;

        // Interactive Tracking Coordinates (1920x1080 Space)
        this.mouseX = 0;
        this.mouseY = 0;

        // Gameplay Constants
        this.circleRadius = 40;
        this.approachDuration = 800; // ms before hit time that circle appears
        this.hitWindow = 150; // ms grace period

        // Animation references
        this.animationFrameId = null;
        this.isGameEnded = false;

        // Feedback Text (e.g., "300", "Miss")
        this.hitTexts = [];

        this.bindEvents();
    }

    async init() {
        this.drawLoading();

        try {
            // 1. Fetch and parse beatmap
            const osuFileResponse = await fetch(`/${this.beatmapFolder}/beatmap.osu`);
            const osuText = await osuFileResponse.text();

            // osu! uses a 512x384 playfield centered inside a 640x480 standard 4:3 screen.
            // To scale this to our 1920x1080 (16:9) canvas without clipping edges or stretching horizontally:
            // Scale factor: 1080 / 480 = 2.25.
            // Playfield Size: 512 * 2.25 = 1152, 384 * 2.25 = 864.
            const playfieldWidth = 1152;
            const playfieldHeight = 864;

            // Parse to the strictly scaled 4:3 bounds instead of the full canvas
            this.hitObjects = parseBeatmap(osuText, playfieldWidth, playfieldHeight);

            // Shift all objects to perfectly center the playfield inside the 16:9 space
            const offsetX = (this.canvas.width - playfieldWidth) / 2; // (1920 - 1152) / 2 = 384
            const offsetY = (this.canvas.height - playfieldHeight) / 2; // (1080 - 864) / 2 = 108

            this.hitObjects.forEach((obj, index) => {
                obj.x += offsetX;
                obj.y += offsetY;
                // Assign a sequence number to each circle (1 to total circles)
                obj.number = index + 1; 
            });

            this.totalCircles = this.hitObjects.length;

            // 2. Load audio
            await this.audioHandler.load(`/${this.beatmapFolder}/audio.mp3`);

            // 3. Ready to play
            this.drawStartScreen();
        } catch (e) {
            console.error(e);
            this.ctx.fillStyle = "white";
            this.ctx.fillText("Failed to load map data.", this.canvas.width/2, this.canvas.height/2);
        }
    }

    bindEvents() {
        // High-precision tracking function that calculates true internal game canvas positions 
        // by factoring out CSS object-fit letterbox/pillarbox padding offsets.
        const updateCursorPosition = (clientX, clientY) => {
            const rect = this.canvas.getBoundingClientRect();
            const canvasRatio = this.canvas.width / this.canvas.height; // 1920 / 1080 (1.777)
            const elementRatio = rect.width / rect.height;

            let actualRenderingWidth = rect.width;
            let actualRenderingHeight = rect.height;
            let letterboxLeft = 0;
            let letterboxTop = 0;

            if (elementRatio > canvasRatio) {
                // Pillarboxed (Black padding bars added on the Left & Right sides)
                actualRenderingHeight = rect.height;
                actualRenderingWidth = actualRenderingHeight * canvasRatio;
                letterboxLeft = (rect.width - actualRenderingWidth) / 2;
            } else {
                // Letterboxed (Black padding bars added on the Top & Bottom sides)
                actualRenderingWidth = rect.width;
                actualRenderingHeight = actualRenderingWidth / canvasRatio;
                letterboxTop = (rect.height - actualRenderingHeight) / 2;
            }

            // Isolate layout offsets to map localized mouse coordinates
            const normalizedX = clientX - rect.left - letterboxLeft;
            const normalizedY = clientY - rect.top - letterboxTop;

            // Project safely into the target internal scaling domain
            this.mouseX = (normalizedX / actualRenderingWidth) * this.canvas.width;
            this.mouseY = (normalizedY / actualRenderingHeight) * this.canvas.height;
        };

        // Continually track mouse movements across the window surface
        this.canvas.addEventListener('mousemove', (e) => {
            updateCursorPosition(e.clientX, e.clientY);
        });

        // Combined macro executor evaluating raw player hits
        const executeHitAction = () => {
            if (!this.audioHandler.isPlaying && !this.isGameEnded && this.hitObjects.length > 0) {
                this.startGame();
                return;
            }

            if (this.audioHandler.isPlaying && !this.isGameEnded) {
                this.checkHitAtCursor();
            }
        };

        // Fire instantly on Mousedown instead of waiting for full Click release
        this.canvas.addEventListener('mousedown', (e) => {
            e.preventDefault(); 
            updateCursorPosition(e.clientX, e.clientY);
            executeHitAction();
        });

        // Implement native Z / X keyboard hitting options
        window.addEventListener('keydown', (e) => {
            const key = e.key.toLowerCase();
            if (key === 'z' || key === 'x') {
                executeHitAction();
            }
        });
    }

    drawLoading() {
        // Clear canvas cleanly so the element's CSS background image shows through
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.ctx.fillStyle = "#ff66aa";
        this.ctx.font = "24px sans-serif";
        this.ctx.textAlign = "center";
        this.ctx.fillText("Loading beatmap...", this.canvas.width / 2, this.canvas.height / 2);
    }

    drawStartScreen() {
        // Clear canvas cleanly so the element's CSS background image shows through
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.ctx.fillStyle = "#ffffff";
        this.ctx.font = "30px sans-serif";
        this.ctx.textAlign = "center";
        this.ctx.fillText(`Click anywhere or press Z/X to start`, this.canvas.width / 2, this.canvas.height / 2);
        this.ctx.font = "20px sans-serif";
        this.ctx.fillStyle = "#888899";
        this.ctx.fillText(`${this.mapTitle} [${this.difficulty}]`, this.canvas.width / 2, this.canvas.height / 2 + 40);
    }

    startGame() {
        this.audioHandler.play();
        this.gameLoop();
    }

    gameLoop() {
        if (this.isGameEnded) return;

        const currentTime = this.audioHandler.getCurrentTime();

        // Clear the canvas cleanly so the CSS background image shows through!
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Manage visible objects
        this.visibleObjects = this.hitObjects.filter(obj => {
            return !obj.resolved && 
                   currentTime >= obj.time - this.approachDuration && 
                   currentTime <= obj.time + this.hitWindow;
        });

        // Check for Auto-misses (> 150ms in the past)
        this.hitObjects.forEach(obj => {
            if (!obj.resolved && currentTime > obj.time + this.hitWindow) {
                this.registerHit(obj, 0); // Miss
            }
        });

        // Draw hit circles and approach rings
        for (let i = this.visibleObjects.length - 1; i >= 0; i--) {
            const circle = this.visibleObjects[i];

            // Draw Main Circle
            this.ctx.beginPath();
            this.ctx.arc(circle.x, circle.y, this.circleRadius, 0, Math.PI * 2);
            this.ctx.fillStyle = "#2a2a35";
            this.ctx.fill();
            this.ctx.lineWidth = 3;
            this.ctx.strokeStyle = "#ffffff";
            this.ctx.stroke();

            // Draw Number inside the Circle
            this.ctx.fillStyle = "#ffffff";
            this.ctx.font = "bold 26px sans-serif";
            this.ctx.textAlign = "center";
            this.ctx.textBaseline = "middle";
            this.ctx.fillText(circle.number, circle.x, circle.y);

            // Calculate and Draw Approach Ring
            const approachRadius = this.circleRadius * (1 + 2 * ((circle.time - currentTime) / this.approachDuration));
            if (approachRadius >= this.circleRadius) {
                this.ctx.beginPath();
                this.ctx.arc(circle.x, circle.y, approachRadius, 0, Math.PI * 2);
                this.ctx.strokeStyle = "#ff66aa";
                this.ctx.lineWidth = 3;
                this.ctx.stroke();
            }
        }

        // Draw HUD (Score & Combo)
        this.drawHUD();
        // Draw floating text (300, 100, Miss)
        this.drawHitTexts(currentTime);

        // End Game Check
        if (this.hitObjects.every(obj => obj.resolved)) {
            this.endMap();
            return;
        }

        this.animationFrameId = requestAnimationFrame(() => this.gameLoop());
    }

    checkHitAtCursor() {
        const currentTime = this.audioHandler.getCurrentTime();

        // Find the earliest visible object the user's mapped position intersects
        let clickedObject = null;
        for (let i = 0; i < this.visibleObjects.length; i++) {
            const obj = this.visibleObjects[i];
            const dist = Math.sqrt(Math.pow(this.mouseX - obj.x, 2) + Math.pow(this.mouseY - obj.y, 2));
            if (dist <= this.circleRadius) {
                clickedObject = obj;
                break; // Break on the oldest valid object found
            }
        }

        if (clickedObject) {
            const timingDiff = Math.abs(currentTime - clickedObject.time);
            let hitValue = 0;

            if (timingDiff <= 50) hitValue = 300;
            else if (timingDiff <= 100) hitValue = 100;
            else if (timingDiff <= 150) hitValue = 50;

            this.registerHit(clickedObject, hitValue);
        }
    }

    registerHit(circle, hitValue) {
        circle.resolved = true;

        let label = "Miss";
        if (hitValue > 0) {
            this.combo++;
            if (this.combo > this.maxCombo) this.maxCombo = this.combo;
            this.score += Math.floor(hitValue * Math.max(1, this.combo * 0.1 + 1));
            this.totalHitValueAwarded += hitValue;
            label = hitValue.toString();
        } else {
            this.combo = 0;
            this.misses++;
        }

        // Add floating text
        this.hitTexts.push({
            x: circle.x,
            y: circle.y,
            text: label,
            color: hitValue === 0 ? "#ff4d4d" : (hitValue === 300 ? "#ffcc11" : "#32cd32"),
            spawnTime: this.audioHandler.getCurrentTime()
        });
    }

    drawHUD() {
        this.ctx.fillStyle = "#ffffff";
        this.ctx.font = "24px sans-serif";
        this.ctx.textAlign = "left";
        this.ctx.textBaseline = "alphabetic"; // Reset baseline for HUD text
        this.ctx.fillText(`Score: ${this.score}`, 20, 40);
        this.ctx.fillText(`Combo: ${this.combo}x`, 20, 70);
    }

    drawHitTexts(currentTime) {
        this.ctx.textAlign = "center";
        this.ctx.font = "bold 20px sans-serif";
        this.ctx.textBaseline = "alphabetic"; // Reset baseline for hit texts

        // Remove texts older than 500ms
        this.hitTexts = this.hitTexts.filter(ht => currentTime - ht.spawnTime < 500);

        this.hitTexts.forEach(ht => {
            const age = currentTime - ht.spawnTime;
            const alpha = 1 - (age / 500); // Fade out
            this.ctx.fillStyle = ht.color;
            this.ctx.globalAlpha = alpha;
            this.ctx.fillText(ht.text, ht.x, ht.y - (age * 0.05));
            this.ctx.globalAlpha = 1.0;
        });
    }

    endMap() {
        this.isGameEnded = true;
        this.audioHandler.stop();
        cancelAnimationFrame(this.animationFrameId);

        let accuracy = 0;
        if (this.totalCircles > 0) {
            accuracy = (this.totalHitValueAwarded / (this.totalCircles * 300)) * 100;
        }
        accuracy = Math.round(accuracy * 10) / 10;

        let rank = "D";
        if (accuracy === 100) rank = "SS";
        else if (accuracy >= 95) rank = "S";
        else if (accuracy >= 90) rank = "A";
        else if (accuracy >= 80) rank = "B";
        else if (accuracy >= 70) rank = "C";

        this.ctx.fillStyle = "rgba(18, 18, 22, 0.9)";
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        this.ctx.fillStyle = "#ff66aa";
        this.ctx.font = "40px sans-serif";
        this.ctx.textAlign = "center";
        this.ctx.textBaseline = "alphabetic"; // Reset baseline
        this.ctx.fillText("Map Completed!", this.canvas.width/2, this.canvas.height/2 - 50);

        this.showResultsScreen(accuracy, rank);
    }

    async showResultsScreen(accuracy, rank) {
        const overlay = document.createElement('div');
        overlay.id = "results-overlay";
        overlay.style.position = "absolute";
        overlay.style.top = "0";
        overlay.style.left = "0";
        overlay.style.width = "100%";
        overlay.style.height = "100%";
        overlay.style.background = "rgba(25, 25, 35, 0.95)";
        overlay.style.color = "#fff";
        overlay.style.display = "flex";
        overlay.style.flexDirection = "column";
        overlay.style.alignItems = "center";
        overlay.style.justifyContent = "center";
        overlay.style.fontFamily = "sans-serif";
        overlay.style.zIndex = "100";

        const isPB = (this.prevBestScore === null || this.score > this.prevBestScore);

        overlay.innerHTML = `
            <h1 style="color: #ff66aa; margin-bottom: 5px;">Results</h1>
            <h2 style="margin: 0 0 20px 0; color: #888899; font-weight: normal;">${this.mapTitle} [${this.difficulty}]</h2>

            <div style="display: flex; gap: 40px; margin-bottom: 30px; text-align: center;">
                <div><div style="font-size: 0.9em; color: #888899;">Score</div><div style="font-size: 2em; font-weight: bold;">${this.score}</div></div>
                <div><div style="font-size: 0.9em; color: #888899;">Accuracy</div><div style="font-size: 2em; font-weight: bold;">${accuracy}%</div></div>
                <div><div style="font-size: 0.9em; color: #888899;">Max Combo</div><div style="font-size: 2em; font-weight: bold;">${this.maxCombo}x</div></div>
                <div><div style="font-size: 0.9em; color: #888899;">Rank</div><div style="font-size: 2em; font-weight: bold; color: #ff66aa;">${rank}</div></div>
            </div>

            <div style="background: #1a1a24; padding: 20px; border-radius: 8px; border: 1px solid #3a3a45; width: 80%; max-width: 600px; text-align: center; margin-bottom: 30px;">
                <h3 style="margin-top: 0; color: #ff66aa;">AI Coach</h3>
                <p id="ai-feedback" style="line-height: 1.5; color: #bbbbcc;">Loading analysis... ⏳</p>
            </div>

            <div style="display: flex; gap: 20px;">
                <button onclick="window.location.reload()" style="padding: 12px 24px; background: #2a2a35; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1.1em;">Play Again</button>
                <button onclick="window.location.href='/home.php'" style="padding: 12px 24px; background: #ff66aa; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1.1em;">Back to Home</button>
            </div>
        `;

        this.canvas.parentElement.style.position = "relative";
        this.canvas.parentElement.appendChild(overlay);

        this.saveScore(accuracy);
        this.fetchAiAnalysis(accuracy, isPB);
    }

    async saveScore(accuracy) {
        try {
            await fetch('/api/save_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    map: this.beatmapFolder.split('/')[1],
                    difficulty: this.difficulty,
                    score: this.score,
                    accuracy: accuracy
                })
            });
        } catch (e) {
            console.error("Failed to save score:", e);
        }
    }

    async fetchAiAnalysis(accuracy, isPB) {
        try {
            const response = await fetch('/api/ai_analysis.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    map_title: this.mapTitle,
                    difficulty: this.difficulty,
                    score: this.score,
                    accuracy: accuracy,
                    misses: this.misses,
                    total_circles: this.totalCircles,
                    max_combo: this.maxCombo,
                    is_new_personal_best: isPB,
                    previous_best_score: this.prevBestScore,
                    previous_best_accuracy: this.prevBestAccuracy
                })
            });
            const data = await response.json();
            document.getElementById('ai-feedback').innerText = data.analysis || "AI analysis unavailable right now.";
        } catch (e) {
            console.error("AI Analysis Fetch Error:", e);
            document.getElementById('ai-feedback').innerText = "AI analysis unavailable right now.";
        }
    }
}