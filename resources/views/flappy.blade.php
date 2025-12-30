<x-layout title="Flappy">
    <body class="bg-zinc-900 min-h-screen flex items-center justify-center">
        <canvas id="game" width="400" height="600" class="border-2 border-zinc-700"></canvas>

        <script>
            const canvas = document.getElementById('game');
            const ctx = canvas.getContext('2d');

            const GRAVITY = 0.4;
            const FLAP_STRENGTH = -8;
            const PIPE_SPEED = 3;
            const PIPE_GAP = 150;
            const PIPE_WIDTH = 60;
            const PIPE_SPACING = 200;

            let gameState = 'menu';
            let score = 0;
            let highScore = 0;

            const bird = {
                x: 80,
                y: 300,
                vy: 0,
                radius: 15
            };

            let pipes = [];

            function resetGame() {
                bird.y = 300;
                bird.vy = 0;
                pipes = [];
                score = 0;
                spawnPipe();
            }

            function spawnPipe() {
                const minY = 80;
                const maxY = canvas.height - PIPE_GAP - 80;
                const gapY = minY + Math.random() * (maxY - minY);

                pipes.push({
                    x: canvas.width,
                    gapY: gapY,
                    scored: false
                });
            }

            function flap() {
                if (gameState === 'menu') {
                    gameState = 'playing';
                    resetGame();
                }
                if (gameState === 'playing') {
                    bird.vy = FLAP_STRENGTH;
                }
                if (gameState === 'gameover') {
                    gameState = 'playing';
                    resetGame();
                }
            }

            function update() {
                if (gameState !== 'playing') return;

                // Bird physics
                bird.vy += GRAVITY;
                bird.y += bird.vy;

                // Spawn new pipes
                if (pipes.length === 0 || pipes[pipes.length - 1].x < canvas.width - PIPE_SPACING) {
                    spawnPipe();
                }

                // Move pipes
                for (const pipe of pipes) {
                    pipe.x -= PIPE_SPEED;

                    // Score when passing pipe
                    if (!pipe.scored && pipe.x + PIPE_WIDTH < bird.x) {
                        pipe.scored = true;
                        score++;
                        if (score > highScore) highScore = score;
                    }
                }

                // Remove off-screen pipes
                pipes = pipes.filter(p => p.x > -PIPE_WIDTH);

                // Collision detection
                // Ground and ceiling
                if (bird.y + bird.radius > canvas.height || bird.y - bird.radius < 0) {
                    gameState = 'gameover';
                }

                // Pipes
                for (const pipe of pipes) {
                    // Check if bird is in pipe's x range
                    if (bird.x + bird.radius > pipe.x && bird.x - bird.radius < pipe.x + PIPE_WIDTH) {
                        // Check if bird hits top or bottom pipe
                        if (bird.y - bird.radius < pipe.gapY || bird.y + bird.radius > pipe.gapY + PIPE_GAP) {
                            gameState = 'gameover';
                        }
                    }
                }
            }

            function draw() {
                // Sky gradient
                const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
                gradient.addColorStop(0, '#1e3a5f');
                gradient.addColorStop(1, '#0c1929');
                ctx.fillStyle = gradient;
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Pipes
                ctx.fillStyle = '#22c55e';
                for (const pipe of pipes) {
                    // Top pipe
                    ctx.fillRect(pipe.x, 0, PIPE_WIDTH, pipe.gapY);
                    // Bottom pipe
                    ctx.fillRect(pipe.x, pipe.gapY + PIPE_GAP, PIPE_WIDTH, canvas.height - pipe.gapY - PIPE_GAP);

                    // Pipe caps
                    ctx.fillStyle = '#16a34a';
                    ctx.fillRect(pipe.x - 5, pipe.gapY - 20, PIPE_WIDTH + 10, 20);
                    ctx.fillRect(pipe.x - 5, pipe.gapY + PIPE_GAP, PIPE_WIDTH + 10, 20);
                    ctx.fillStyle = '#22c55e';
                }

                // Bird
                ctx.fillStyle = '#fbbf24';
                ctx.beginPath();
                ctx.arc(bird.x, bird.y, bird.radius, 0, Math.PI * 2);
                ctx.fill();

                // Bird eye
                ctx.fillStyle = '#fff';
                ctx.beginPath();
                ctx.arc(bird.x + 5, bird.y - 3, 5, 0, Math.PI * 2);
                ctx.fill();
                ctx.fillStyle = '#000';
                ctx.beginPath();
                ctx.arc(bird.x + 7, bird.y - 3, 2, 0, Math.PI * 2);
                ctx.fill();

                // Bird beak
                ctx.fillStyle = '#f97316';
                ctx.beginPath();
                ctx.moveTo(bird.x + bird.radius, bird.y);
                ctx.lineTo(bird.x + bird.radius + 10, bird.y + 3);
                ctx.lineTo(bird.x + bird.radius, bird.y + 6);
                ctx.closePath();
                ctx.fill();

                // Score
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 48px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(score, canvas.width / 2, 80);

                // Menu
                if (gameState === 'menu') {
                    ctx.fillStyle = 'rgba(0,0,0,0.5)';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);

                    ctx.fillStyle = '#fff';
                    ctx.font = 'bold 40px sans-serif';
                    ctx.fillText('FLAPPY', canvas.width / 2, 250);

                    ctx.font = '20px sans-serif';
                    ctx.fillStyle = '#aaa';
                    ctx.fillText('Press SPACE to start', canvas.width / 2, 320);
                    ctx.fillText('SPACE or CLICK to flap', canvas.width / 2, 350);
                }

                // Game over
                if (gameState === 'gameover') {
                    ctx.fillStyle = 'rgba(0,0,0,0.6)';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);

                    ctx.fillStyle = '#ef4444';
                    ctx.font = 'bold 40px sans-serif';
                    ctx.fillText('GAME OVER', canvas.width / 2, 250);

                    ctx.fillStyle = '#fff';
                    ctx.font = '24px sans-serif';
                    ctx.fillText('Score: ' + score, canvas.width / 2, 310);
                    ctx.fillText('Best: ' + highScore, canvas.width / 2, 345);

                    ctx.font = '18px sans-serif';
                    ctx.fillStyle = '#aaa';
                    ctx.fillText('Press SPACE to retry', canvas.width / 2, 400);
                }
            }

            document.addEventListener('keydown', e => {
                if (e.key === ' ') {
                    e.preventDefault();
                    flap();
                }
            });

            canvas.addEventListener('click', flap);

            function gameLoop() {
                update();
                draw();
                requestAnimationFrame(gameLoop);
            }
            gameLoop();
        </script>
    </body>
</x-layout>

