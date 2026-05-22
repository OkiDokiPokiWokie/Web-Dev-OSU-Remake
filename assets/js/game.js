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
            this.hitObjects = parseBeatmap(osuText, this.canvas.width, this.canvas.height);
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
        // Start game on click if waiting
        this.canvas.addEventListener('click', (e) => {
            if (!this.audioHandler.isPlaying && !this.isGameEnded && this.hitObjects.length > 0) {
                this.startGame();
                return;
            }

            if (this.audioHandler.isPlaying && !this.isGameEnded) {
                this.handleGameplayClick(e);
            }
        });
    }

    drawLoading() {
        this.ctx.fillStyle = "#121216";
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        this.ctx.fillStyle = "#ff66aa";
        this.ctx.font = "24px sans-serif";
        this.ctx.textAlign = "center";
        this.ctx.fillText("Loading beatmap...", this.canvas.width / 2, this.canvas.height / 2);
    }

    drawStartScreen() {
        this.ctx.fillStyle = "#121216";
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        this.ctx.fillStyle = "#ffffff";
        this.ctx.font = "30px sans-serif";
        this.ctx.fillText(`Click anywhere to start`, this.canvas.width / 2, this.canvas.height / 2);
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
        this.ctx.fillStyle = "#121216";
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

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
        // Draw in reverse order so newer circles appear underneath older ones
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

    handleGameplayClick(e) {
        const rect = this.canvas.getBoundingClientRect();
        // Scale mouse coordinates to actual canvas resolution
        const scaleX = this.canvas.width / rect.width;
        const scaleY = this.canvas.height / rect.height;
        const clickX = (e.clientX - rect.left) * scaleX;
        const clickY = (e.clientY - rect.top) * scaleY;
        const currentTime = this.audioHandler.getCurrentTime();

        // Find the earliest visible object the user clicked on
        let clickedObject = null;
        for (let i = 0; i < this.visibleObjects.length; i++) {
            const obj = this.visibleObjects[i];
            const dist = Math.sqrt(Math.pow(clickX - obj.x, 2) + Math.pow(clickY - obj.y, 2));
            if (dist <= this.circleRadius) {
                clickedObject = obj;
                break; // Break on the first (oldest) one found
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
            // Simple combo multiplier formula as per PRD
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
            color: hitValue === 0 ? "#ff4d4d" : "#32cd32",
            spawnTime: this.audioHandler.getCurrentTime()
        });
    }

    drawHUD() {
        this.ctx.fillStyle = "#ffffff";
        this.ctx.font = "24px sans-serif";
        this.ctx.textAlign = "left";
        this.ctx.fillText(`Score: ${this.score}`, 20, 40);
        this.ctx.fillText(`Combo: ${this.combo}x`, 20, 70);
    }

    drawHitTexts(currentTime) {
        this.ctx.textAlign = "center";
        this.ctx.font = "bold 20px sans-serif";

        // Remove texts older than 500ms
        this.hitTexts = this.hitTexts.filter(ht => currentTime - ht.spawnTime < 500);

        this.hitTexts.forEach(ht => {
            const age = currentTime - ht.spawnTime;
            const alpha = 1 - (age / 500); // Fade out
            this.ctx.fillStyle = ht.color;
            this.ctx.globalAlpha = alpha;
            // Float up slightly
            this.ctx.fillText(ht.text, ht.x, ht.y - (age * 0.05));
            this.ctx.globalAlpha = 1.0;
        });
    }

    endMap() {
        this.isGameEnded = true;
        this.audioHandler.stop();
        cancelAnimationFrame(this.animationFrameId);

        // Calculate accuracy safely
        let accuracy = 0;
        if (this.totalCircles > 0) {
            accuracy = (this.totalHitValueAwarded / (this.totalCircles * 300)) * 100;
        }
        accuracy = Math.round(accuracy * 10) / 10; // Round to 1 decimal place

        // Calculate Rank locally for display
        let rank = "D";
        if (accuracy === 100) rank = "SS";
        else if (accuracy >= 95) rank = "S";
        else if (accuracy >= 90) rank = "A";
        else if (accuracy >= 80) rank = "B";
        else if (accuracy >= 70) rank = "C";

        // Draw basic results to canvas while UI overlay builds
        this.ctx.fillStyle = "rgba(18, 18, 22, 0.9)";
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        this.ctx.fillStyle = "#ff66aa";
        this.ctx.font = "40px sans-serif";
        this.ctx.fillText("Map Completed!", this.canvas.width/2, this.canvas.height/2 - 50);

        this.showResultsScreen(accuracy, rank);
    }

    async showResultsScreen(accuracy, rank) {
        // Create Results DOM Overlay
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

        // Is it a new PB?
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

        // The canvas parent should be positioned relative so absolute overlay covers it perfectly
        this.canvas.parentElement.style.position = "relative";
        this.canvas.parentElement.appendChild(overlay);

        // Execute API calls in parallel
        this.saveScore(accuracy);
        this.fetchAiAnalysis(accuracy, isPB);
    }

    async saveScore(accuracy) {
        try {
            await fetch('/api/save_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    map: this.beatmapFolder.split('/')[1], // Extracts map ID from path
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