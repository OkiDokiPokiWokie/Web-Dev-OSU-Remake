/**
 * Beatmap File Parser
 * Project: osu! Web Clone
 * * Parses the raw text of a .osu file and extracts standard hit circles.
 */

/**
 * Parses a .osu file string and returns an array of hit circle objects.
 * * @param {string} osuText - The raw text content of the .osu file
 * @param {number} canvasWidth - The width of the game canvas to scale X coordinates
 * @param {number} canvasHeight - The height of the game canvas to scale Y coordinates
 * @returns {Array} Array of hit objects sorted by time: [{x, y, time}, ...]
 */
export function parseBeatmap(osuText, canvasWidth, canvasHeight) {
    const lines = osuText.split('\n');
    const hitObjects = [];
    let isHitObjectSection = false;

    for (let i = 0; i < lines.length; i++) {
        // Remove whitespace and carriage returns
        const line = lines[i].trim();

        // Skip empty lines
        if (line === '') continue;

        // Check if we are entering a new section
        if (line.startsWith('[')) {
            // We only care about the [HitObjects] section
            isHitObjectSection = (line === '[HitObjects]');
            continue;
        }

        // If we are currently reading lines inside [HitObjects]
        if (isHitObjectSection) {
            const parts = line.split(',');

            // Ensure the line has enough data (osu hit objects have at least 5 parts)
            if (parts.length < 5) continue;

            // Extract the required values (Indices 0, 1, 2, 3 as per PRD)
            const x = parseFloat(parts[0]);
            const y = parseFloat(parts[1]);
            const time = parseInt(parts[2], 10);
            const type = parseInt(parts[3], 10);

            // Check the bitmask: if (type & 1 === 1), it is a standard hit circle
            if ((type & 1) === 1) {
                // osu! uses a 512x384 coordinate grid. 
                // We must scale this to whatever our actual canvas size is.
                const screenX = (x / 512) * canvasWidth;
                const screenY = (y / 384) * canvasHeight;

                hitObjects.push({
                    x: screenX,
                    y: screenY,
                    time: time
                });
            }
        }
    }

    // Sort the hit objects by time ascending, just in case the .osu file was out of order
    hitObjects.sort((a, b) => a.time - b.time);

    return hitObjects;
}